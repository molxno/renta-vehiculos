<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table): void {
            $table->unsignedBigInteger('renta_neta_centavos')->nullable()->after('total_centavos');
        });
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table): void {
            $table->dropColumn('renta_neta_centavos');
        });
    }
};
