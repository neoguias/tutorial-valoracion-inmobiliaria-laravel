<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('title');
            $table->string('operation');
            $table->string('type');
            $table->string('zone');
            $table->unsignedInteger('price');
            $table->unsignedTinyInteger('bedrooms')->default(0);
            $table->unsignedSmallInteger('area');
            $table->string('status')->default('available');
            $table->text('description')->nullable();
            // El texto en el que busca Larasearch: referencia, título y zona.
            $table->searchable();
            $table->timestamps();

            $table->index(['operation', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
