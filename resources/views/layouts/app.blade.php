<!DOCTYPE html>
<html lang="es" data-wire-theme="megaflow">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'CRM' }} · {{ config('crm.agency') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
    <header class="border-b border-slate-200 bg-white">
        <nav class="mx-auto flex max-w-6xl items-center gap-6 px-4 py-3">
            <span class="font-semibold text-teal-700">{{ config('crm.agency') }}</span>
            <a href="{{ route('properties') }}" wire:navigate class="text-sm hover:text-teal-700" wire:current="font-semibold text-teal-700">Inmuebles</a>
            <a href="{{ route('contacts') }}" wire:navigate class="text-sm hover:text-teal-700" wire:current="font-semibold text-teal-700">Clientes</a>
            <a href="{{ route('visits') }}" wire:navigate class="text-sm hover:text-teal-700" wire:current="font-semibold text-teal-700">Visitas</a>
            <a href="{{ route('valuation') }}" wire:navigate class="text-sm hover:text-teal-700" wire:current="font-semibold text-teal-700">Valorar</a>
        </nav>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8">
        {{ $slot }}
    </main>

    <x-wiretoast theme="megaflow" />
</body>
</html>
