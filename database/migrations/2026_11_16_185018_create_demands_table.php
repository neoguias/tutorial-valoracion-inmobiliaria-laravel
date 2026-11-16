<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('operation');
            $table->string('type')->nullable();
            $table->string('zone')->nullable();
            $table->unsignedInteger('max_price');
            $table->unsignedTinyInteger('min_bedrooms')->default(0);
            $table->unsignedSmallInteger('min_area')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demands');
    }
};
