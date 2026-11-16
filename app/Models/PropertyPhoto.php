<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PropertyPhoto extends Model
{
    protected $fillable = ['path', 'position'];

    protected static function booted(): void
    {
        // Al borrar la foto se borra también el archivo, para no dejar imágenes huérfanas.
        static::deleted(fn (PropertyPhoto $photo) => Storage::disk('public')->delete($photo->path));
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
