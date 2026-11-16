<?php

namespace App\Models;

use EduLazaro\Larasearch\Concerns\HasSearch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use HasSearch;

    protected $fillable = ['name', 'email', 'phone', 'notes'];

    // Los campos que encuentra el buscador de clientes.
    protected array $searchable = ['name', 'email', 'phone'];

    public function demands(): HasMany
    {
        return $this->hasMany(Demand::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }
}
