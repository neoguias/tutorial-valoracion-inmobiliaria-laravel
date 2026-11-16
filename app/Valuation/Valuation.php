<?php

namespace App\Valuation;

use Illuminate\Support\Collection;

/**
 * El resultado de una valoración: la cifra estimada, la horquilla en la que probablemente está
 * el precio real y los comparables con los que se ha calculado, para poder explicarla.
 */
final readonly class Valuation
{
    /**
     * @param  Collection<int, array{sale: \App\Models\Sale, adjusted: int}>  $comparables
     */
    public function __construct(
        public int $estimate,
        public int $low,
        public int $high,
        public Collection $comparables,
        public string $method,
        public float $slope,
        public float $intercept,
        public float $r2,
    ) {}

    public function perMeter(int $area): int
    {
        return (int) round($this->estimate / $area);
    }
}
