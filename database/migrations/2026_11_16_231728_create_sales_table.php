<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Las compraventas cerradas en la zona: los comparables con los que se valora un piso.
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('zone');
            $table->string('type');
            $table->unsignedSmallInteger('area');
            $table->unsignedTinyInteger('bedrooms');
            $table->boolean('elevator');
            $table->boolean('terrace');
            $table->unsignedInteger('price');
            $table->date('sold_on');
            $table->timestamps();

            $table->index(['zone', 'type', 'sold_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
