<?php

namespace App\Models;

use App\Enums\Operation;
use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use EduLazaro\Larasearch\Concerns\HasSearch;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Property extends Model
{
    use HasSearch;

    protected $fillable = [
        'reference', 'title', 'operation', 'type', 'zone', 'price',
        'bedrooms', 'area', 'status', 'description',
    ];

    // Los mismos valores por defecto que la tabla, para que un inmueble recién creado los tenga
    // ya en memoria sin volver a leerlo de la base de datos.
    protected $attributes = [
        'status' => 'available',
        'bedrooms' => 0,
    ];

    // Los campos que encuentra el buscador del listado.
    protected array $searchable = ['reference', 'title', 'zone'];

    protected function casts(): array
    {
        return [
            'operation' => Operation::class,
            'type' => PropertyType::class,
            'status' => PropertyStatus::class,
            'price' => 'integer',
            'bedrooms' => 'integer',
            'area' => 'integer',
        ];
    }

    /** 289.000 € en venta y 850 €/mes en alquiler. */
    public function priceLabel(): string
    {
        $amount = number_format($this->price, 0, ',', '.').' €';

        return $this->operation === Operation::Rent ? $amount.'/mes' : $amount;
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(PropertyPhoto::class)->orderBy('position');
    }

    /** La portada: la primera foto del inmueble. */
    public function cover(): HasOne
    {
        return $this->hasOne(PropertyPhoto::class)->oldestOfMany('position');
    }

    /**
     * Los inmuebles disponibles que encajan con lo que busca una demanda. Si la demanda no
     * fija tipo o zona, vale cualquiera.
     */
    #[Scope]
    protected function matching(Builder $query, Demand $demand): void
    {
        $query->where('status', PropertyStatus::Available)
            ->where('operation', $demand->operation)
            ->where('price', '<=', $demand->max_price)
            ->where('bedrooms', '>=', $demand->min_bedrooms)
            ->where('area', '>=', $demand->min_area)
            ->when($demand->type, fn (Builder $q) => $q->where('type', $demand->type))
            ->when($demand->zone, fn (Builder $q) => $q->where('zone', $demand->zone));
    }
}
