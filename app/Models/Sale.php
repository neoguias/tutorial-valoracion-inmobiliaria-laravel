<?php

namespace App\Models;

use App\Enums\PropertyType;
use Illuminate\Database\Eloquent\Model;

/**
 * Una compraventa ya cerrada: qué se vendió, dónde y por cuánto. Son los comparables (los
 * «testigos», en el argot de las tasaciones) con los que se estima el valor de un piso.
 */
class Sale extends Model
{
    protected $fillable = ['zone', 'type', 'area', 'bedrooms', 'elevator', 'terrace', 'price', 'sold_on'];

    protected function casts(): array
    {
        return [
            'type' => PropertyType::class,
            'area' => 'integer',
            'bedrooms' => 'integer',
            'elevator' => 'boolean',
            'terrace' => 'boolean',
            'price' => 'integer',
            'sold_on' => 'date',
        ];
    }

    public function pricePerMeter(): int
    {
        return (int) round($this->price / $this->area);
    }
}
