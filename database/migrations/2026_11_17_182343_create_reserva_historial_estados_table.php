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
        Schema::create('reserva_historial_estados', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reserva_id')->constrained('reservas')->cascadeOnDelete();
            $table->string('estado_anterior', 30);
            $table->string('estado_nuevo', 30);
            $table->timestamp('ocurrio_at');
            $table->timestamps();

            $table->index(['reserva_id', 'ocurrio_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reserva_historial_estados');
    }
};
