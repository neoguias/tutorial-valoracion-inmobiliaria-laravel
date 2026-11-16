<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/inmuebles');

Route::livewire('/inmuebles', 'pages::properties')->name('properties');
Route::livewire('/inmuebles/{property}', 'pages::property')->name('property');
Route::livewire('/clientes', 'pages::contacts')->name('contacts');
Route::livewire('/visitas', 'pages::visits')->name('visits');
Route::livewire('/valorar', 'pages::valuation')->name('valuation');
