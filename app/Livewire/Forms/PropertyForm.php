<?php

namespace App\Livewire\Forms;

use App\Enums\Operation;
use App\Enums\PropertyType;
use App\Models\Property;
use Illuminate\Validation\Rule;
use Livewire\Form;

class PropertyForm extends Form
{
    public string $reference = '';

    public string $title = '';

    public string $operation = 'sale';

    public string $type = 'flat';

    public string $zone = '';

    public ?int $price = null;

    public int $bedrooms = 2;

    public ?int $area = null;

    protected function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'max:20', Rule::unique('properties', 'reference')],
            'title' => ['required', 'string', 'max:120'],
            'operation' => ['required', Rule::enum(Operation::class)],
            'type' => ['required', Rule::enum(PropertyType::class)],
            'zone' => ['required', Rule::in(config('crm.zones'))],
            'price' => ['required', 'integer', 'min:1'],
            'bedrooms' => ['required', 'integer', 'min:0', 'max:20'],
            'area' => ['required', 'integer', 'min:1'],
        ];
    }

    public function store(): Property
    {
        $property = Property::create($this->validate());

        $this->reset();

        return $property;
    }
}
