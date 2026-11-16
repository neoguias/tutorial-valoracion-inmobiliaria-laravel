<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\Sale;
use App\Valuation\Valuator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * La valoración con ventas inventadas a medida, para que las cifras esperadas se puedan
 * calcular a mano.
 */
class ValuationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /** Ventas en Ruzafa a 3.000 €/m² justos, sin ascensor ni terraza. */
    private function sales(array $areas, array $overrides = []): void
    {
        foreach ($areas as $area) {
            Sale::create($overrides + [
                'zone' => 'Ruzafa', 'type' => 'flat', 'area' => $area, 'bedrooms' => 2,
                'elevator' => false, 'terrace' => false, 'price' => $area * 3000, 'sold_on' => now()->subMonths(3),
            ]);
        }
    }

    private function value(int $area, bool $elevator = false): ?\App\Valuation\Valuation
    {
        return app(Valuator::class)->value(['zone' => 'Ruzafa', 'type' => 'flat', 'area' => $area, 'elevator' => $elevator, 'terrace' => false]);
    }

    public function test_the_regression_line_gives_the_price_of_the_area(): void
    {
        $this->sales([70, 75, 80, 85, 90, 95, 100, 105, 110]);

        $valuation = $this->value(90);

        $this->assertSame('regression', $valuation->method);
        $this->assertSame(270000, $valuation->estimate);
        $this->assertSame($valuation->estimate, $valuation->low);
        $this->assertEqualsWithDelta(1.0, $valuation->r2, 0.0001);
    }

    public function test_comparables_are_adjusted_for_the_elevator(): void
    {
        $this->sales([70, 75, 80, 85, 90, 95, 100, 105, 110]);

        // Ninguna venta tenía ascensor, así que el nuestro vale un 6 % más.
        $this->assertSame(286000, $this->value(90, elevator: true)->estimate);
    }

    public function test_few_comparables_use_the_median_and_too_few_give_no_figure(): void
    {
        $this->sales([85, 88, 90, 92, 95, 97]);

        $this->assertSame('median', $this->value(90)->method);
        $this->assertNull($this->value(200));
    }

    public function test_the_size_range_opens_when_there_are_few_close_sales(): void
    {
        // A 120 m² no hay ninguna venta entre 90 y 150, pero sí entre 72 y 168.
        $this->sales([75, 78, 80, 82, 85, 88]);

        $this->assertCount(6, $this->value(120)->comparables);
    }

    public function test_sales_older_than_eighteen_months_are_ignored(): void
    {
        $this->sales([70, 75, 80, 85, 90, 95, 100, 105, 110], ['sold_on' => now()->subMonths(20)]);

        $this->assertNull($this->value(90));
    }

    public function test_the_page_values_a_property_of_the_portfolio(): void
    {
        $this->sales([70, 75, 80, 85, 90, 95, 100, 105, 110]);
        $property = Property::create([
            'reference' => 'AT-1', 'title' => 'Piso de prueba', 'operation' => 'sale', 'type' => 'flat',
            'zone' => 'Ruzafa', 'price' => 297000, 'bedrooms' => 3, 'area' => 90,
        ]);

        Livewire::withQueryParams(['property' => $property->id])
            ->test('pages::valuation')
            ->assertSet('area', 90)
            ->assertSee('270.000 €')
            ->assertSee('10 % por encima de la estimación');
    }
}
