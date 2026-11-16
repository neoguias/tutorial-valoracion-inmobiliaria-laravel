<?php

use App\Enums\Operation;
use App\Enums\PropertyType;
use App\Livewire\Forms\ContactForm;
use App\Models\Contact;
use App\Models\Demand;
use App\Models\Property;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Los clientes de la agencia con su buscador, lo que busca cada uno y los inmuebles de la
 * cartera que le encajan ahora mismo.
 */
new #[Title('Clientes')] class extends Component
{
    #[Url]
    public string $search = '';

    public ContactForm $form;

    #[Computed]
    public function contacts()
    {
        return Contact::searchText($this->search)->with('demands')->orderBy('name')->get();
    }

    public function matches(Demand $demand)
    {
        return Property::matching($demand)->orderBy('price')->get();
    }

    public function save(): void
    {
        $contact = $this->form->store();
        $matches = $this->matches($contact->demands->first())->count();

        $this->form->reset();
        $this->closeModal('new-contact');
        $this->notify(match ($matches) {
            0 => "{$contact->name} guardado. Todavía no hay nada en cartera que le encaje.",
            1 => "{$contact->name} guardado. Le encaja un inmueble.",
            default => "{$contact->name} guardado. Le encajan {$matches} inmuebles.",
        });
    }
};
?>

<div>
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <h1 class="mr-auto text-2xl font-semibold">Clientes</h1>
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre, email o teléfono"
            class="w-64 rounded border border-slate-300 px-2 py-1.5 text-sm">
        <button type="button" x-on:click="Wiremodal.open('new-contact')" class="rounded bg-teal-700 px-3 py-2 text-sm font-medium text-white hover:bg-teal-800">
            Nuevo cliente
        </button>
    </div>

    <x-wiremodal name="new-contact" title="Nuevo cliente" size="3xl" as="form" wire:submit="save">
        <x-slot:body>
            <div class="grid gap-4 sm:grid-cols-3">
                <label class="text-sm">Nombre
                    <input wire:model="form.name" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                    @error('form.name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm">Email
                    <input type="email" wire:model="form.email" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                    @error('form.email') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm">Teléfono
                    <input wire:model="form.phone" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                </label>

                <p class="text-sm font-semibold sm:col-span-3">Qué busca</p>
                <label class="text-sm">Operación
                    <select wire:model="form.operation" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                        @foreach (Operation::cases() as $case)
                            <option value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm">Tipo
                    <select wire:model="form.type" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                        <option value="">Cualquiera</option>
                        @foreach (PropertyType::cases() as $case)
                            <option value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm">Zona
                    <select wire:model="form.zone" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                        <option value="">Cualquiera</option>
                        @foreach (config('crm.zones') as $name)
                            <option>{{ $name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm">Precio máximo (€)
                    <input type="number" wire:model="form.max_price" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                    @error('form.max_price') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm">Dormitorios mínimos
                    <input type="number" wire:model="form.min_bedrooms" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                </label>
                <label class="text-sm">Superficie mínima (m²)
                    <input type="number" wire:model="form.min_area" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                </label>
            </div>
        </x-slot:body>
        <x-slot:footer>
            <button type="button" data-wm-dismiss class="rounded border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Cancelar</button>
            <button class="rounded bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Guardar cliente</button>
        </x-slot:footer>
    </x-wiremodal>

    <div class="space-y-4">
        @forelse ($this->contacts as $contact)
            <article wire:key="contact-{{ $contact->id }}" class="rounded-lg border border-slate-200 bg-white p-4">
                <div class="flex flex-wrap items-baseline gap-x-3">
                    <h2 class="font-semibold">{{ $contact->name }}</h2>
                    <span class="text-sm text-slate-500">{{ collect([$contact->email, $contact->phone])->filter()->implode(' · ') }}</span>
                </div>

                @foreach ($contact->demands as $demand)
                    <p class="mt-2 text-sm text-slate-600">
                        Busca {{ $demand->type ? mb_strtolower($demand->type->label()) : 'cualquier inmueble' }} en {{ mb_strtolower($demand->operation->label()) }},
                        {{ $demand->zone ? 'en '.$demand->zone : 'en cualquier zona' }},
                        hasta {{ number_format($demand->max_price, 0, ',', '.') }} €
                        @if ($demand->min_bedrooms) · {{ $demand->min_bedrooms }}+ dorm. @endif
                        @if ($demand->min_area) · {{ $demand->min_area }}+ m² @endif
                    </p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @forelse ($this->matches($demand) as $property)
                            <a href="{{ route('property', $property) }}" wire:navigate class="rounded-full bg-teal-50 px-3 py-1 text-xs text-teal-800 hover:bg-teal-100">
                                {{ $property->reference }} · {{ $property->priceLabel() }}
                            </a>
                        @empty
                            <span class="text-xs text-slate-400">Nada en cartera por ahora.</span>
                        @endforelse
                    </div>
                @endforeach
            </article>
        @empty
            <p class="text-sm text-slate-500">Ningún cliente coincide con esa búsqueda.</p>
        @endforelse
    </div>
</div>
