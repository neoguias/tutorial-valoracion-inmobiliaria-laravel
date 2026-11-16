<?php

use App\Enums\Operation;
use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Livewire\Forms\PropertyForm;
use App\Models\Demand;
use App\Models\Property;
use EduLazaro\Wiretables\Concerns\WithSorting;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * La cartera de la agencia: los inmuebles con su buscador y sus filtros, el alta de uno nuevo
 * y, en cada fila, cuántos clientes buscan justo algo así.
 */
new #[Title('Inmuebles')] class extends Component
{
    use WithSorting;

    #[Url]
    public string $search = '';

    #[Url]
    public string $operation = '';

    #[Url]
    public string $zone = '';

    public PropertyForm $form;

    /** Las columnas por las que se puede ordenar desde la cabecera de la tabla. */
    protected function sortable(): array
    {
        return ['price', 'area'];
    }

    #[Computed]
    public function properties()
    {
        return Property::query()
            ->with('cover')
            ->searchText($this->search)
            ->when($this->operation, fn ($q) => $q->where('operation', $this->operation))
            ->when($this->zone, fn ($q) => $q->where('zone', $this->zone))
            ->when($this->sort, fn ($q) => $q->orderBy($this->sort, $this->direction), fn ($q) => $q->orderBy('reference'))
            ->get();
    }

    public function interested(Property $property): int
    {
        return Demand::matching($property)->count();
    }

    public function save(): void
    {
        $property = $this->form->store();
        $interested = $this->interested($property);

        $this->form->reset();
        $this->closeModal('new-property');
        $this->notify(match ($interested) {
            0 => "{$property->reference} guardado. Todavía no lo busca ningún cliente.",
            1 => "{$property->reference} guardado. Lo busca un cliente.",
            default => "{$property->reference} guardado. Lo buscan {$interested} clientes.",
        });
    }

    public function setStatus(int $propertyId, string $status): void
    {
        $property = Property::findOrFail($propertyId);
        $property->update(['status' => PropertyStatus::from($status)]);

        $this->notify("{$property->reference} ahora está ".mb_strtolower($property->status->label()).'.', 'info');
    }
};
?>

<div>
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <h1 class="mr-auto text-2xl font-semibold">Inmuebles</h1>

        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar por referencia, título o zona"
            class="w-64 rounded border border-slate-300 px-2 py-1.5 text-sm">

        <select wire:model.live="operation" class="rounded border border-slate-300 px-2 py-1.5 text-sm">
            <option value="">Venta y alquiler</option>
            @foreach (Operation::cases() as $case)
                <option value="{{ $case->value }}">{{ $case->label() }}</option>
            @endforeach
        </select>

        <select wire:model.live="zone" class="rounded border border-slate-300 px-2 py-1.5 text-sm">
            <option value="">Todas las zonas</option>
            @foreach (config('crm.zones') as $name)
                <option>{{ $name }}</option>
            @endforeach
        </select>

        <button type="button" x-on:click="Wiremodal.open('new-property')" class="rounded bg-teal-700 px-3 py-2 text-sm font-medium text-white hover:bg-teal-800">
            Nuevo inmueble
        </button>
    </div>

    <x-wiretable>
        <x-slot:head>
            <x-wiretable.th>Inmueble</x-wiretable.th>
            <x-wiretable.th hide="md" shrink>Zona</x-wiretable.th>
            <x-wiretable.th shrink align="right" sortable="price" :sort="$sort" :direction="$direction">Precio</x-wiretable.th>
            <x-wiretable.th hide="lg" shrink align="right" sortable="area" :sort="$sort" :direction="$direction">Superficie</x-wiretable.th>
            <x-wiretable.th hide="md" shrink>Estado</x-wiretable.th>
            <x-wiretable.th shrink align="right">Interesados</x-wiretable.th>
            <x-wiretable.th actions />
        </x-slot:head>

        @forelse ($this->properties as $property)
            <x-wiretable.row :href="route('property', $property)" navigate wire:key="property-{{ $property->id }}">
                <x-wiretable.td>
                    <x-wiretable.primary :title="$property->title" :subtitle="$property->reference.' · '.$property->type->label().($property->bedrooms ? ' · '.$property->bedrooms.' dorm.' : '')">
                        <x-slot:leading>
                            @if ($property->cover)
                                <img src="{{ $property->cover->url() }}" alt="" class="h-12 w-18 rounded object-cover">
                            @else
                                <div class="h-12 w-18 rounded bg-slate-100"></div>
                            @endif
                        </x-slot:leading>
                        <p class="text-xs text-slate-500 md:hidden">{{ $property->zone }} · {{ $property->status->label() }}</p>
                    </x-wiretable.primary>
                </x-wiretable.td>
                <x-wiretable.td hide="md" shrink>{{ $property->zone }}</x-wiretable.td>
                <x-wiretable.td shrink align="right">{{ $property->priceLabel() }}</x-wiretable.td>
                <x-wiretable.td hide="lg" shrink align="right">{{ $property->area }} m²</x-wiretable.td>
                <x-wiretable.td hide="md" shrink>{{ $property->status->label() }}</x-wiretable.td>
                <x-wiretable.td shrink align="right">{{ $this->interested($property) }}</x-wiretable.td>
                <x-wiretable.td actions>
                    <x-wiretable.menu :label="'Acciones de '.$property->reference">
                        <x-wiretable.menu-item :href="route('property', $property)">Ver ficha</x-wiretable.menu-item>
                        <x-wiretable.menu-separator />
                        @foreach (PropertyStatus::cases() as $case)
                            @continue($case === $property->status)
                            <x-wiretable.menu-item wire:click="setStatus({{ $property->id }}, '{{ $case->value }}')">
                                Marcar como {{ mb_strtolower($case->label()) }}
                            </x-wiretable.menu-item>
                        @endforeach
                    </x-wiretable.menu>
                </x-wiretable.td>
            </x-wiretable.row>
        @empty
            <x-wiretable.empty colspan="7">No hay inmuebles con esa búsqueda.</x-wiretable.empty>
        @endforelse
    </x-wiretable>

    <x-wiremodal name="new-property" title="Nuevo inmueble" size="3xl" as="form" wire:submit="save">
        <x-slot:body>
            <div class="grid gap-4 sm:grid-cols-4">
                <label class="text-sm">Referencia
                    <input wire:model="form.reference" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                    @error('form.reference') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm sm:col-span-3">Título
                    <input wire:model="form.title" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                    @error('form.title') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm">Operación
                    <select wire:model="form.operation" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                        @foreach (Operation::cases() as $case)
                            <option value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm">Tipo
                    <select wire:model="form.type" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                        @foreach (PropertyType::cases() as $case)
                            <option value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm sm:col-span-2">Zona
                    <select wire:model="form.zone" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                        <option value="">Elige una</option>
                        @foreach (config('crm.zones') as $name)
                            <option>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('form.zone') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm sm:col-span-2">Precio (€)
                    <input type="number" wire:model="form.price" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                    @error('form.price') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm">Dormitorios
                    <input type="number" wire:model="form.bedrooms" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                    @error('form.bedrooms') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm">Superficie (m²)
                    <input type="number" wire:model="form.area" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                    @error('form.area') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
            </div>
        </x-slot:body>
        <x-slot:footer>
            <button type="button" data-wm-dismiss class="rounded border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Cancelar</button>
            <button class="rounded bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Guardar inmueble</button>
        </x-slot:footer>
    </x-wiremodal>
</div>
