<?php

namespace Database\Seeders;

use App\Models\Sale;
use Illuminate\Database\Seeder;

/**
 * Compraventas de ejemplo de los últimos dos años en los barrios de la agencia. El precio sale
 * del metro cuadrado de cada barrio, con un recargo por ascensor y por terraza y un margen de
 * azar, como en la vida real. La semilla fija hace que cada carga produzca las mismas ventas.
 */
class SaleSeeder extends Seeder
{
    // El precio orientativo del metro cuadrado en venta de cada barrio.
    private const PRICE_PER_METER = [
        'Benimaclet' => 2400, 'Campanar' => 2700, 'El Carmen' => 3800,
        'Malvarrosa' => 2800, 'Patraix' => 2500, 'Ruzafa' => 3300,
    ];

    public function run(): void
    {
        mt_srand(805);

        foreach (self::PRICE_PER_METER as $zone => $perMeter) {
            for ($i = 0; $i < 60; $i++) {
                $house = mt_rand(1, 100) <= 12;
                $bedrooms = $house ? mt_rand(3, 5) : mt_rand(1, 4);
                $area = $house ? 90 + $bedrooms * 20 + mt_rand(0, 40) : 30 + $bedrooms * 22 + mt_rand(-8, 18);
                $elevator = ! $house && mt_rand(1, 100) <= 70;
                $terrace = mt_rand(1, 100) <= ($house ? 70 : 30);

                $price = $area * $perMeter * mt_rand(88, 112) / 100;
                $price *= ($elevator ? 1.06 : 1) * ($terrace ? 1.05 : 1);

                Sale::create([
                    'zone' => $zone,
                    'type' => $house ? 'house' : 'flat',
                    'area' => $area,
                    'bedrooms' => $bedrooms,
                    'elevator' => $elevator,
                    'terrace' => $terrace,
                    'price' => (int) round($price, -3),
                    'sold_on' => now()->subDays(mt_rand(1, 730))->toDateString(),
                ]);
            }
        }
    }
}
