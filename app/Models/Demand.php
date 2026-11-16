<?php

namespace App\Models;

use App\Enums\Operation;
use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Demand extends Model
{
    protected $fillable = [
        'contact_id', 'operation', 'type', 'zone', 'max_price',
        'min_bedrooms', 'min_area', 'active',
    ];

    protected $attributes = [
        'min_bedrooms' => 0,
        'min_area' => 0,
        'active' => true,
    ];

    protected function casts(): array
    {
        return [
            'operation' => Operation::class,
            'type' => PropertyType::class,
            'max_price' => 'integer',
            'min_bedrooms' => 'integer',
            'min_area' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Las demandas activas a las que les sirve un inmueble: el cruce visto desde el otro
     * lado. Una demanda sin tipo o sin zona acepta cualquiera.
     */
    #[Scope]
    protected function matching(Builder $query, Property $property): void
    {
        // Un inmueble reservado o vendido ya no le sirve a nadie.
        if ($property->status !== PropertyStatus::Available) {
            $query->whereIn('id', []);

            return;
        }

        $query->where('active', true)
            ->where('operation', $property->operation)
            ->where('max_price', '>=', $property->price)
            ->where('min_bedrooms', '<=', $property->bedrooms)
            ->where('min_area', '<=', $property->area)
            ->where(fn (Builder $q) => $q->whereNull('type')->orWhere('type', $property->type))
            ->where(fn (Builder $q) => $q->whereNull('zone')->orWhere('zone', $property->zone));
    }
}
