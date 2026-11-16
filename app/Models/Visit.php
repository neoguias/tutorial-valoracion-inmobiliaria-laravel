<?php

namespace App\Models;

use App\Enums\VisitOutcome;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visit extends Model
{
    protected $fillable = ['property_id', 'contact_id', 'scheduled_at', 'outcome', 'notes'];

    protected $attributes = [
        'outcome' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'outcome' => VisitOutcome::class,
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
