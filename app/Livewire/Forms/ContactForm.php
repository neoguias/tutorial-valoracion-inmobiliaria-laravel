<?php

namespace App\Livewire\Forms;

use App\Enums\Operation;
use App\Enums\PropertyType;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Un cliente y lo que busca, en un solo formulario: en una agencia casi nadie entra en la
 * ficha si no es porque quiere comprar o alquilar algo.
 */
class ContactForm extends Form
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $operation = 'sale';

    public string $type = '';

    public string $zone = '';

    public ?int $max_price = null;

    public int $min_bedrooms = 0;

    public int $min_area = 0;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:30'],
            'operation' => ['required', Rule::enum(Operation::class)],
            'type' => ['nullable', Rule::enum(PropertyType::class)],
            'zone' => ['nullable', Rule::in(config('crm.zones'))],
            'max_price' => ['required', 'integer', 'min:1'],
            'min_bedrooms' => ['required', 'integer', 'min:0', 'max:20'],
            'min_area' => ['required', 'integer', 'min:0'],
        ];
    }

    public function store(): Contact
    {
        $data = $this->validate();

        $contact = DB::transaction(function () use ($data) {
            $contact = Contact::create([
                'name' => $data['name'],
                'email' => $data['email'] ?: null,
                'phone' => $data['phone'] ?: null,
            ]);

            $contact->demands()->create([
                'operation' => $data['operation'],
                'type' => $data['type'] ?: null,
                'zone' => $data['zone'] ?: null,
                'max_price' => $data['max_price'],
                'min_bedrooms' => $data['min_bedrooms'],
                'min_area' => $data['min_area'],
            ]);

            return $contact;
        });

        $this->reset();

        return $contact;
    }
}
