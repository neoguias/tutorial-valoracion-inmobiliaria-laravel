<?php

use App\Enums\VisitOutcome;
use App\Models\Visit;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * La agenda: las visitas de hoy en adelante, agrupadas por día, y qué le pareció cada
 * inmueble al cliente cuando ya se ha hecho.
 */
new #[Title('Visitas')] class extends Component
{
    #[Computed]
    public function days()
    {
        return Visit::with(['property', 'contact'])
            ->where('scheduled_at', '>=', today())
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy(fn (Visit $visit) => $visit->scheduled_at->toDateString());
    }

    public function mark(int $visitId, string $outcome): void
    {
        $visit = Visit::with('contact')->findOrFail($visitId);
        $visit->update(['outcome' => VisitOutcome::from($outcome)]);

        $this->notify('Visita de '.$visit->contact->name.' anotada como «'.$visit->outcome->label().'».', 'info');
    }
};
?>

<div>
    <h1 class="mb-6 text-2xl font-semibold">Visitas</h1>

    @forelse ($this->days as $day => $visits)
        <section wire:key="day-{{ $day }}" class="mb-6">
            <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">
                {{ \Illuminate\Support\Carbon::parse($day)->isoFormat('dddd D [de] MMMM') }}
            </h2>
            <ul class="divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white">
                @foreach ($visits as $visit)
                    <li wire:key="visit-{{ $visit->id }}" class="flex flex-wrap items-center gap-3 px-4 py-3 text-sm">
                        <span class="w-12 font-mono">{{ $visit->scheduled_at->format('H:i') }}</span>
                        <div class="mr-auto">
                            <a href="{{ route('property', $visit->property) }}" wire:navigate class="font-medium text-teal-700 hover:underline">{{ $visit->property->title }}</a>
                            <div class="text-xs text-slate-500">{{ $visit->contact->name }} · {{ $visit->property->zone }}</div>
                        </div>
                        @if ($visit->outcome === \App\Enums\VisitOutcome::Pending)
                            <button wire:click="mark({{ $visit->id }}, 'interested')" class="rounded border border-slate-300 px-2 py-1 text-xs hover:bg-slate-50">Le interesa</button>
                            <button wire:click="mark({{ $visit->id }}, 'discarded')" class="rounded border border-slate-300 px-2 py-1 text-xs hover:bg-slate-50">Descartado</button>
                        @else
                            <span @class(['rounded-full px-2 py-0.5 text-xs', 'bg-teal-50 text-teal-800' => $visit->outcome === \App\Enums\VisitOutcome::Interested, 'bg-slate-100 text-slate-500' => $visit->outcome === \App\Enums\VisitOutcome::Discarded])>{{ $visit->outcome->label() }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @empty
        <p class="text-slate-500">No hay visitas programadas.</p>
    @endforelse
</div>
