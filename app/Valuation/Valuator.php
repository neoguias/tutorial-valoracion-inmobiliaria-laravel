<?php

namespace App\Valuation;

use App\Models\Sale;
use Illuminate\Support\Collection;

/**
 * Estima el precio de un piso a partir de las ventas parecidas de su barrio. Todo el cálculo es
 * determinista: con los mismos comparables sale siempre la misma cifra, y se puede explicar
 * paso a paso a un propietario.
 */
final class Valuator
{
    // Lo que añaden un ascensor y una terraza al precio, para igualar los comparables.
    public const ELEVATOR = 0.06;
    public const TERRACE = 0.05;

    // Con menos comparables que esto no damos cifra. Son los mismos seis que pide la Orden
    // ECO/805/2003 a las tasaciones oficiales que usan el método de comparación.
    public const MIN_COMPARABLES = 6;

    /**
     * @param  array{zone: string, type: string, area: int, elevator: bool, terrace: bool}  $subject
     */
    public function value(array $subject): ?Valuation
    {
        $comparables = $this->comparables($subject, 0.25);

        // Si hay pocas ventas tan parecidas, abrimos la horquilla de superficie.
        if ($comparables->count() < self::MIN_COMPARABLES) {
            $comparables = $this->comparables($subject, 0.40);
        }
        if ($comparables->count() < self::MIN_COMPARABLES) {
            return null;
        }

        $rows = $comparables->map(fn (Sale $sale) => ['sale' => $sale, 'adjusted' => $this->adjust($sale, $subject)]);

        [$slope, $intercept, $r2, $error] = $this->regression($rows);

        // La recta solo vale con ocho ventas o más y si el precio sube con la superficie. Si no,
        // usamos la mediana del metro cuadrado, que aguanta mejor con pocos datos.
        if ($rows->count() >= 8 && $slope > 0) {
            $estimate = $intercept + $slope * $subject['area'];
            $method = 'regression';
        } else {
            $estimate = $rows->map(fn (array $row) => $row['adjusted'] / $row['sale']->area)->median() * $subject['area'];
            $method = 'median';
        }

        return new Valuation(
            estimate: (int) round($estimate, -3),
            low: (int) round($estimate - $error, -3),
            high: (int) round($estimate + $error, -3),
            comparables: $rows->values(),
            method: $method,
            slope: $slope,
            intercept: $intercept,
            r2: $r2,
        );
    }

    /** Las ventas de los últimos 18 meses en el mismo barrio, del mismo tipo y de superficie parecida. */
    private function comparables(array $subject, float $margin): Collection
    {
        return Sale::query()
            ->where('zone', $subject['zone'])
            ->where('type', $subject['type'])
            ->whereBetween('area', [(int) floor($subject['area'] * (1 - $margin)), (int) ceil($subject['area'] * (1 + $margin))])
            ->where('sold_on', '>=', now()->subMonths(18))
            ->orderBy('area')
            ->get();
    }

    /**
     * El precio que habría tenido la venta con el ascensor y la terraza del piso que valoramos.
     * Si el comparable tenía ascensor y el nuestro no, le quitamos lo que vale el ascensor.
     */
    private function adjust(Sale $sale, array $subject): int
    {
        $factor = 1.0;
        $factor *= ($subject['elevator'] ? 1 + self::ELEVATOR : 1) / ($sale->elevator ? 1 + self::ELEVATOR : 1);
        $factor *= ($subject['terrace'] ? 1 + self::TERRACE : 1) / ($sale->terrace ? 1 + self::TERRACE : 1);

        return (int) round($sale->price * $factor);
    }

    /**
     * Regresión lineal simple del precio sobre la superficie, por mínimos cuadrados. Devuelve la
     * pendiente (euros por cada metro más), el corte con el eje, el R² y el error típico.
     *
     * @return array{0: float, 1: float, 2: float, 3: float}
     */
    private function regression(Collection $rows): array
    {
        $n = $rows->count();
        $x = $rows->map(fn (array $row) => $row['sale']->area);
        $y = $rows->pluck('adjusted');
        $meanX = $x->avg();
        $meanY = $y->avg();

        $sxy = $x->zip($y)->sum(fn ($pair) => ($pair[0] - $meanX) * ($pair[1] - $meanY));
        $sxx = $x->sum(fn ($value) => ($value - $meanX) ** 2);
        $slope = $sxx > 0 ? $sxy / $sxx : 0.0;
        $intercept = $meanY - $slope * $meanX;

        $residuals = $x->zip($y)->sum(fn ($pair) => ($pair[1] - ($intercept + $slope * $pair[0])) ** 2);
        $total = $y->sum(fn ($value) => ($value - $meanY) ** 2);

        return [
            $slope,
            $intercept,
            $total > 0 ? 1 - $residuals / $total : 0.0,
            // El error típico de la estimación, o cuánto se separan de media las ventas de la recta.
            sqrt($residuals / max($n - 2, 1)),
        ];
    }
}
