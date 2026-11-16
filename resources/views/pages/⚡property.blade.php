<?php

use App\Enums\PropertyStatus;
use App\Models\Contact;
use App\Models\Demand;
use App\Models\Property;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * La ficha de un inmueble: sus datos y sus fotos, los clientes cuya demanda encaja con él y
 * las visitas, con un formulario para programar la siguiente.
 */
new class extends Component
{
    use WithFileUploads;

    public Property $property;

    /** Las fotos elegidas en el formulario, todavía sin guardar. */
    public array $uploads = [];

    public string $status = '';

    public ?int $contact_id = null;

    public string $scheduled_at = '';

    public function mount(): void
    {
        $this->status = $this->property->status->value;
    }

    public function render()
    {
        return $this->view()->title($this->property->title);
    }

    #[Computed]
    public function interested()
    {
        return Demand::matching($this->property)->with('contact')->get();
    }

    #[Computed]
    public function contacts()
    {
        return Contact::orderBy('name')->get();
    }

    /** Los días que el inmueble ya tiene visitas, para marcarlos en el calendario. */
    #[Computed]
    public function busyDays(): array
    {
        return $this->property->visits()->with('contact')
            ->where('scheduled_at', '>=', today())
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy(fn ($visit) => $visit->scheduled_at->toDateString())
            ->map(fn ($visits) => $visits->map(fn ($visit) => $visit->scheduled_at->format('H:i').' '.$visit->contact->name)->all())
            ->all();
    }

    public function updatedStatus(): void
    {
        $this->validate(['status' => [Rule::enum(PropertyStatus::class)]]);

        $this->property->update(['status' => $this->status]);

        $this->notify('Estado cambiado a '.mb_strtolower($this->property->status->label()).'.', 'info');
    }

    public function addPhotos(): void
    {
        $this->validate([
            'uploads' => ['required', 'array', 'max:6'],
            'uploads.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $position = (int) $this->property->photos()->max('position');

        foreach ($this->uploads as $upload) {
            $this->property->photos()->create([
                'path' => $upload->store("properties/{$this->property->id}", 'public'),
                'position' => ++$position,
            ]);
        }

        $this->notify(count($this->uploads) === 1 ? 'Foto añadida.' : count($this->uploads).' fotos añadidas.');
        $this->reset('uploads');
    }

    public function deletePhoto(int $photoId): void
    {
        $this->property->photos()->findOrFail($photoId)->delete();

        $this->notify('Foto eliminada.', 'info');
    }

    public function propose(int $contactId): void
    {
        $this->contact_id = $contactId;
    }

    public function schedule(): void
    {
        $data = $this->validate([
            'contact_id' => ['required', Rule::exists('contacts', 'id')],
            'scheduled_at' => ['required', 'date', 'after:now'],
        ]);

        $visit = $this->property->visits()->create($data);

        $this->reset('contact_id', 'scheduled_at');
        $this->notify('Visita con '.$visit->contact->name.' el '.$visit->scheduled_at->isoFormat('dddd D [de] MMMM [a las] HH:mm').'.');
    }
};
?>

<div class="grid gap-8 lg:grid-cols-3">
    <section class="lg:col-span-2">
        <a href="{{ route('properties') }}" wire:navigate class="text-sm text-slate-500 hover:text-teal-700">← Inmuebles</a>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h1 class="mr-auto text-2xl font-semibold">{{ $property->title }}</h1>
            <a href="{{ route('valuation', ['property' => $property->id]) }}" wire:navigate class="rounded border border-teal-700 px-3 py-1 text-sm text-teal-700 hover:bg-teal-50">Valorar</a>
        </div>
        <p class="mt-1 text-slate-600">
            <span class="font-mono text-xs">{{ $property->reference }}</span> ·
            {{ $property->operation->label() }} · {{ $property->type->label() }} en {{ $property->zone }} ·
            @if ($property->bedrooms) {{ $property->bedrooms }} dorm. · @endif {{ $property->area }} m² ·
            <strong>{{ $property->priceLabel() }}</strong>
        </p>

        <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach ($property->photos as $photo)
                <figure wire:key="photo-{{ $photo->id }}" class="group relative">
                    <img src="{{ $photo->url() }}" alt="{{ $property->title }}" class="aspect-[3/2] w-full rounded-lg object-cover">
                    @if ($loop->first)
                        <span class="absolute left-2 top-2 rounded bg-white/90 px-2 py-0.5 text-xs font-medium">Portada</span>
                    @endif
                    <button type="button" x-on:click="Wiremodal.open('delete-photo').then(result => result === 'confirmed' && $wire.deletePhoto({{ $photo->id }}))"
                        class="absolute right-2 top-2 rounded bg-white/90 px-2 py-0.5 text-xs text-red-700 opacity-0 group-hover:opacity-100">
                        Eliminar
                    </button>
                </figure>
            @endforeach
        </div>

        <form wire:submit="addPhotos" class="mt-3 flex flex-wrap items-center gap-3 text-sm">
            <input type="file" wire:model="uploads" multiple accept="image/jpeg,image/png,image/webp"
                class="text-sm file:mr-3 file:rounded file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 hover:file:bg-slate-200">
            <span wire:loading wire:target="uploads" class="text-slate-500">Subiendo…</span>
            @if ($uploads)
                <button wire:loading.attr="disabled" class="rounded bg-teal-700 px-3 py-1.5 font-medium text-white hover:bg-teal-800">
                    Guardar {{ count($uploads) === 1 ? 'foto' : count($uploads).' fotos' }}
                </button>
            @endif
            @error('uploads') <span class="w-full text-xs text-red-600">{{ $message }}</span> @enderror
            @error('uploads.*') <span class="w-full text-xs text-red-600">{{ $message }}</span> @enderror
        </form>

        <x-wiremodal name="delete-photo" title="¿Eliminar esta foto?" size="sm">
            <x-slot:body>
                <p class="text-sm text-slate-600">Se borrará también el archivo. Si era la portada, la siguiente foto ocupará su lugar.</p>
            </x-slot:body>
            <x-slot:footer>
                <button type="button" data-wm-dismiss class="rounded border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Cancelar</button>
                <button type="button" onclick="Wiremodal.close('delete-photo', 'confirmed')" class="rounded bg-red-700 px-4 py-2 text-sm font-medium text-white hover:bg-red-800">Eliminar</button>
            </x-slot:footer>
        </x-wiremodal>

        @if ($uploads)
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($uploads as $upload)
                    {{-- Un PDF elegido por error no tiene vista previa, solo las imágenes. --}}
                    @if ($upload->isPreviewable())
                        <img src="{{ $upload->temporaryUrl() }}" alt="" class="h-16 w-24 rounded object-cover opacity-80">
                    @endif
                @endforeach
            </div>
        @endif

        <label class="mt-6 inline-flex items-center gap-2 text-sm">Estado
            <select wire:model.live="status" class="rounded border border-slate-300 px-2 py-1.5 text-sm">
                @foreach (PropertyStatus::cases() as $case)
                    <option value="{{ $case->value }}">{{ $case->label() }}</option>
                @endforeach
            </select>
        </label>

        <h2 class="mt-8 mb-3 text-lg font-semibold">Clientes que buscan algo así</h2>
        <ul class="divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white">
            @forelse ($this->interested as $demand)
                <li wire:key="demand-{{ $demand->id }}" class="flex items-center gap-4 px-4 py-3 text-sm">
                    <div class="mr-auto">
                        <div class="font-medium">{{ $demand->contact->name }}</div>
                        <div class="text-xs text-slate-500">
                            Hasta {{ number_format($demand->max_price, 0, ',', '.') }} € · {{ $demand->zone ?? 'cualquier zona' }}
                        </div>
                    </div>
                    <button wire:click="propose({{ $demand->contact_id }})" class="rounded border border-teal-700 px-3 py-1 text-teal-700 hover:bg-teal-50">
                        Programar visita
                    </button>
                </li>
            @empty
                <li class="px-4 py-3 text-sm text-slate-500">
                    {{ $property->status === PropertyStatus::Available ? 'Ningún cliente busca algo así todavía.' : 'Solo se cruzan los inmuebles disponibles.' }}
                </li>
            @endforelse
        </ul>
    </section>

    <aside>
        <form wire:submit="schedule" class="rounded-lg border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-lg font-semibold">Programar visita</h2>
            <label class="block text-sm">Cliente
                <select wire:model="contact_id" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                    <option value="">Elige uno</option>
                    @foreach ($this->contacts as $contact)
                        <option value="{{ $contact->id }}">{{ $contact->name }}</option>
                    @endforeach
                </select>
                @error('contact_id') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </label>
            <label for="scheduled-at" class="mt-3 block text-sm">Día y hora</label>
            <x-wirepicker id="scheduled-at" wire:model="scheduled_at" time :step="30" default-time="10:00"
                :min="today()->toDateString()" :marked="$this->busyDays" trigger-class="mt-1 rounded border border-slate-300 px-2 py-1.5 text-sm" />
            @error('scheduled_at') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            <button class="mt-4 w-full rounded bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Guardar visita</button>
        </form>

        <h2 class="mt-8 mb-3 text-lg font-semibold">Visitas</h2>
        <ul class="space-y-2 text-sm">
            @forelse ($property->visits()->with('contact')->orderBy('scheduled_at')->get() as $visit)
                <li wire:key="visit-{{ $visit->id }}" class="rounded border border-slate-200 bg-white px-3 py-2">
                    {{ $visit->scheduled_at->isoFormat('ddd D MMM, HH:mm') }} · {{ $visit->contact->name }}
                    <span class="text-xs text-slate-500">({{ $visit->outcome->label() }})</span>
                </li>
            @empty
                <li class="text-slate-500">Sin visitas todavía.</li>
            @endforelse
        </ul>
    </aside>
</div>
