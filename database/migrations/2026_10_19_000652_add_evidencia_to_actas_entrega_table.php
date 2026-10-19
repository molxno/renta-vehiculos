<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actas_entrega', function (Blueprint $table): void {
            $table->string('numero')->nullable()->after('reserva_id');
            $table->foreignId('coordinador_id')->nullable()->after('numero')->constrained('users')->nullOnDelete();
            $table->string('firmante_nombre')->nullable()->after('coordinador_id');
            $table->timestamp('firmado_at')->nullable()->after('firmante_nombre');
            $table->string('licencia_resultado')->nullable()->after('firmado_at');
        });

        $prefijo = (string) config('entrega.prefijo_acta', 'ACT');

        DB::table('actas_entrega')->orderBy('id')->get()->each(function (object $acta) use ($prefijo): void {
            DB::table('actas_entrega')->where('id', $acta->id)->update([
                'numero' => $prefijo.'-'.$acta->id,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('actas_entrega', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('coordinador_id');
            $table->dropColumn(['numero', 'firmante_nombre', 'firmado_at', 'licencia_resultado']);
        });
    }
};
