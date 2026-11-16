<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Property;
use App\Models\Visit;
use Illuminate\Database\Seeder;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

/**
 * Datos de ejemplo de Atalaya Inmobiliaria: una cartera pequeña de inmuebles, unos cuantos
 * clientes con lo que buscan y alguna visita, para tener algo que cruzar desde el principio.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $properties = [
            ['AT-101', 'Piso luminoso junto al mercado', 'sale', 'flat', 'Ruzafa', 289000, 3, 92],
            ['AT-102', 'Ático con terraza', 'sale', 'flat', 'El Carmen', 415000, 2, 78],
            ['AT-103', 'Piso para reformar', 'sale', 'flat', 'Benimaclet', 168000, 3, 85],
            ['AT-104', 'Casa de pueblo con patio', 'sale', 'house', 'Malvarrosa', 352000, 4, 140],
            ['AT-105', 'Estudio reformado', 'rent', 'flat', 'Ruzafa', 850, 1, 45],
            ['AT-106', 'Piso familiar con garaje', 'rent', 'flat', 'Campanar', 1250, 3, 110],
            ['AT-107', 'Local a pie de calle', 'rent', 'commercial', 'Patraix', 1100, 0, 120],
            ['AT-108', 'Piso exterior de cuatro dormitorios', 'sale', 'flat', 'Campanar', 325000, 4, 124],
            ['AT-109', 'Oficina con vistas', 'rent', 'office', 'El Carmen', 1450, 0, 95],
            ['AT-110', 'Bajo con jardín', 'sale', 'flat', 'Patraix', 239000, 2, 88],
        ];

        // Las fotos de ejemplo están en database/seeders/photos. Antes de copiarlas borramos las de
        // una carga anterior.
        Storage::disk('public')->deleteDirectory('properties');

        foreach ($properties as [$reference, $title, $operation, $type, $zone, $price, $bedrooms, $area]) {
            $property = Property::create(compact('reference', 'title', 'operation', 'type', 'zone', 'price', 'bedrooms', 'area'));

            $path = Storage::disk('public')->putFile("properties/{$property->id}", new File(__DIR__."/photos/{$reference}.jpg"));
            $property->photos()->create(['path' => $path]);
        }

        $lucia = Contact::create(['name' => 'Lucía Ferrer', 'email' => 'lucia@example.test', 'phone' => '600 111 222']);
        $lucia->demands()->create(['operation' => 'sale', 'type' => 'flat', 'zone' => 'Ruzafa', 'max_price' => 300000, 'min_bedrooms' => 2]);

        $marc = Contact::create(['name' => 'Marc Soler', 'email' => 'marc@example.test']);
        $marc->demands()->create(['operation' => 'sale', 'max_price' => 340000, 'min_bedrooms' => 3, 'min_area' => 100]);

        $ana = Contact::create(['name' => 'Ana Ruiz', 'phone' => '611 333 444']);
        $ana->demands()->create(['operation' => 'rent', 'type' => 'flat', 'max_price' => 1000]);

        $jorge = Contact::create(['name' => 'Jorge Martí', 'email' => 'jorge@example.test', 'notes' => 'Tiene un negocio de cerámica y busca local.']);
        $jorge->demands()->create(['operation' => 'rent', 'type' => 'commercial', 'max_price' => 1200, 'min_area' => 80]);

        $elena = Contact::create(['name' => 'Elena Gil', 'email' => 'elena@example.test']);
        $elena->demands()->create(['operation' => 'sale', 'zone' => 'El Carmen', 'max_price' => 450000]);

        Visit::create([
            'property_id' => Property::where('reference', 'AT-101')->value('id'),
            'contact_id' => $lucia->id,
            'scheduled_at' => now()->addDay()->setTime(10, 30),
        ]);
        Visit::create([
            'property_id' => Property::where('reference', 'AT-108')->value('id'),
            'contact_id' => $marc->id,
            'scheduled_at' => now()->addDays(2)->setTime(17, 0),
        ]);

        $this->call(SaleSeeder::class);
    }
}
