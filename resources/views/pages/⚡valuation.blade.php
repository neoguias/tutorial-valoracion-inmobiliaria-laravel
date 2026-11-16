<?php

use App\Enums\PropertyType;
use App\Models\Property;
use App\Valuation\Valuation;
use App\Valuation\Valuator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Valora un piso por comparables. Se puede abrir desde la ficha de un inmueble de la cartera,
 * que rellena el formulario, o vacía, para el propietario que pregunta cuánto vale su casa.
 */
new #[Title('Valorar un inmueble')] class extends Component
{
    #[Url]
    public ?int $property = null;

    public string $zone = '';

    public string $type = 'flat';

    public ?int $area = null;

    public bool $elevator = false;

    public bool $terrace = false;

    public function mount(): void
    {
        if ($listing = Property::find($this->property)) {
            $this->zone = $listing->zone;
            $this->type = $listing->type->value;
            $this->area = $listing->area;
        }
    }

    #[Computed]
    public function listing(): ?Property
    {
        return Property::find($this->property);
    }

    #[Computed]
    public function valuation(): ?Valuation
    {
        if (! in_array($this->zone, config('crm.zones'), true) || ! PropertyType::tryFrom($this->type) || $this->area < 20 || $this->area > 1000) {
            return null;
        }

        return app(Valuator::class)->value([
            'zone' => $this->zone,
            'type' => $this->type,
            'area' => $this->area,
            'elevator' => $this->elevator,
            'terrace' => $this->terrace,
        ]);
    }

    /**
     * El gráfico de dispersión en coordenadas del SVG: cada venta es un punto (superficie,
     * precio ajustado), la recta es la regresión y el punto grande, el piso que valoramos.
     */
    #[Computed]
    public function chart(): array
    {
        $valuation = $this->valuation;
        $areas = $valuation->comparables->map(fn (array $row) => $row['sale']->area)->push($this->area);
        $prices = $valuation->comparables->pluck('adjusted')->push($valuation->estimate);

        [$minX, $maxX] = [$areas->min() - 5, $areas->max() + 5];
        [$minY, $maxY] = [$prices->min() * 0.9, $prices->max() * 1.05];
        $x = fn (float $area) => round(40 + ($area - $minX) / ($maxX - $minX) * 540, 1);
        $y = fn (float $price) => round(250 - ($price - $minY) / ($maxY - $minY) * 230, 1);

        return [
            'points' => $valuation->comparables->map(fn (array $row) => [$x($row['sale']->area), $y($row['adjusted'])])->all(),
            'line' => $valuation->method === 'regression'
                ? [$x($minX), $y($valuation->intercept + $valuation->slope * $minX), $x($maxX), $y($valuation->intercept + $valuation->slope * $maxX)]
                : null,
            'subject' => [$x($this->area), $y($valuation->estimate)],
            'labels' => ['minX' => $minX, 'maxX' => $maxX, 'minY' => (int) round($minY, -3), 'maxY' => (int) round($maxY, -3)],
        ];
    }
};
?>

<div class="grid gap-8 lg:grid-cols-3">
    <section>
        @if ($this->listing)
            <a href="{{ route('property', $this->listing) }}" wire:navigate class="text-sm text-slate-500 hover:text-teal-700">← {{ $this->listing->title }}</a>
        @endif
        <h1 class="mt-2 text-2xl font-semibold">Valorar un inmueble</h1>
        <p class="mt-1 text-sm text-slate-600">Estimación por comparables, a partir de las ventas cerradas en el barrio en los últimos 18 meses.</p>

        <div class="mt-5 space-y-3 rounded-lg border border-slate-200 bg-white p-5 text-sm">
            <label class="block">Zona
                <select wire:model.live="zone" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                    <option value="">Elige una</option>
                    @foreach (config('crm.zones') as $name)
                        <option>{{ $name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">Tipo
                <select wire:model.live="type" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                    <option value="flat">Piso</option>
                    <option value="house">Casa</option>
                </select>
            </label>
            <label class="block">Superficie (m²)
                <input type="number" wire:model.live.debounce.400ms="area" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
            </label>
            <label class="flex items-center gap-2"><input type="checkbox" wire:model.live="elevator" class="accent-teal-700"> Ascensor</label>
            <label class="flex items-center gap-2"><input type="checkbox" wire:model.live="terrace" class="accent-teal-700"> Terraza</label>
        </div>
    </section>

    <section class="lg:col-span-2">
        @if (! $this->valuation)
            <p class="rounded-lg border border-slate-200 bg-white p-6 text-sm text-slate-500">
                {{ $zone && $area ? 'No hay suficientes ventas parecidas para dar una cifra fiable.' : 'Elige la zona y la superficie para ver la estimación.' }}
            </p>
        @else
            <div class="rounded-lg border border-slate-200 bg-white p-6">
                <p class="text-sm text-slate-500">Valor estimado</p>
                <p class="text-4xl font-bold">{{ number_format($this->valuation->estimate, 0, ',', '.') }} €</p>
                <p class="mt-1 text-slate-600">
                    Entre {{ number_format($this->valuation->low, 0, ',', '.') }} € y {{ number_format($this->valuation->high, 0, ',', '.') }} €
                    · {{ number_format($this->valuation->perMeter($area), 0, ',', '.') }} €/m²
                </p>
                <p class="mt-3 text-sm text-slate-500">
                    Calculado con {{ $this->valuation->comparables->count() }} ventas en {{ $zone }}
                    @if ($this->valuation->method === 'regression')
                        con una recta de regresión (R² {{ number_format($this->valuation->r2, 2, ',', '.') }}).
                    @else
                        con la mediana del metro cuadrado, porque son pocas para trazar una recta.
                    @endif
                </p>

                @if ($this->listing)
                    @php $difference = round(($this->listing->price / $this->valuation->estimate - 1) * 100) @endphp
                    <p class="mt-3 rounded bg-slate-50 px-3 py-2 text-sm">
                        Precio anunciado: <strong>{{ $this->listing->priceLabel() }}</strong>,
                        {{ $difference == 0 ? 'en línea con la estimación' : abs($difference).' % '.($difference > 0 ? 'por encima' : 'por debajo').' de la estimación' }}.
                    </p>
                @endif

                <svg viewBox="0 0 600 280" class="mt-5 w-full" role="img" aria-label="Precio de las ventas comparables según su superficie">
                    <line x1="40" y1="250" x2="580" y2="250" class="stroke-slate-300" />
                    <line x1="40" y1="20" x2="40" y2="250" class="stroke-slate-300" />
                    <text x="40" y="270" class="fill-slate-500 text-[11px]">{{ $this->chart['labels']['minX'] }} m²</text>
                    <text x="580" y="270" text-anchor="end" class="fill-slate-500 text-[11px]">{{ $this->chart['labels']['maxX'] }} m²</text>
                    <text x="36" y="250" text-anchor="end" class="fill-slate-500 text-[11px]">{{ number_format($this->chart['labels']['minY'] / 1000, 0, ',', '.') }}k</text>
                    <text x="36" y="26" text-anchor="end" class="fill-slate-500 text-[11px]">{{ number_format($this->chart['labels']['maxY'] / 1000, 0, ',', '.') }}k</text>
                    @if ($this->chart['line'])
                        @php [$x1, $y1, $x2, $y2] = $this->chart['line'] @endphp
                        <line x1="{{ $x1 }}" y1="{{ $y1 }}" x2="{{ $x2 }}" y2="{{ $y2 }}" class="stroke-teal-700" stroke-width="2" stroke-dasharray="6 4" />
                    @endif
                    @foreach ($this->chart['points'] as [$cx, $cy])
                        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="5" class="fill-slate-400/70" />
                    @endforeach
                    <circle cx="{{ $this->chart['subject'][0] }}" cy="{{ $this->chart['subject'][1] }}" r="9" class="fill-teal-700 stroke-white" stroke-width="3" />
                </svg>
            </div>

            <h2 class="mt-8 mb-3 text-lg font-semibold">Ventas comparables</h2>
            <x-wiretable>
                <x-slot:head>
                    <x-wiretable.th>Vendido</x-wiretable.th>
                    <x-wiretable.th shrink align="right">Superficie</x-wiretable.th>
                    <x-wiretable.th hide="md" shrink>Extras</x-wiretable.th>
                    <x-wiretable.th shrink align="right">Precio</x-wiretable.th>
                    <x-wiretable.th shrink align="right">Ajustado</x-wiretable.th>
                    <x-wiretable.th hide="sm" shrink align="right">€/m²</x-wiretable.th>
                </x-slot:head>
                @foreach ($this->valuation->comparables as $row)
                    <x-wiretable.row wire:key="sale-{{ $row['sale']->id }}">
                        <x-wiretable.td>{{ $row['sale']->sold_on->isoFormat('MMMM YYYY') }} · {{ $row['sale']->bedrooms }} dorm.</x-wiretable.td>
                        <x-wiretable.td shrink align="right">{{ $row['sale']->area }} m²</x-wiretable.td>
                        <x-wiretable.td hide="md" shrink>{{ collect(['ascensor' => $row['sale']->elevator, 'terraza' => $row['sale']->terrace])->filter()->keys()->implode(', ') ?: '·' }}</x-wiretable.td>
                        <x-wiretable.td shrink align="right">{{ number_format($row['sale']->price, 0, ',', '.') }} €</x-wiretable.td>
                        <x-wiretable.td shrink align="right">{{ number_format($row['adjusted'], 0, ',', '.') }} €</x-wiretable.td>
                        <x-wiretable.td hide="sm" shrink align="right">{{ number_format($row['adjusted'] / $row['sale']->area, 0, ',', '.') }}</x-wiretable.td>
                    </x-wiretable.row>
                @endforeach
            </x-wiretable>
        @endif
    </section>
</div>
