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
        Schema::create('licencias_conduccion', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('numero', 50)->unique();
            $table->date('fecha_vencimiento');
            $table->string('categoria', 20);
            $table->timestamps();
        });

        Schema::create('vehiculos', function (Blueprint $table): void {
            $table->id();
            $table->string('placa', 20)->unique();
            $table->string('marca', 80);
            $table->string('modelo', 80);
            $table->unsignedSmallInteger('anio');
            $table->string('tipo', 50);
            $table->string('estado', 30)->default('disponible')->index();
            $table->timestamps();
        });

        Schema::create('polizas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->cascadeOnDelete();
            $table->string('numero', 80)->unique();
            $table->string('aseguradora', 120);
            $table->date('vigencia_desde');
            $table->date('vigencia_hasta');
            $table->timestamps();
        });

        Schema::create('convenios', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 120);
            $table->unsignedSmallInteger('descuento_porcentaje')->default(0);
            $table->boolean('activo')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('tarifas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehiculo_id')->nullable()->constrained('vehiculos')->nullOnDelete();
            $table->string('tipo_vehiculo', 50);
            $table->unsignedBigInteger('valor_diario_centavos');
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->timestamps();
        });

        Schema::create('trm', function (Blueprint $table): void {
            $table->id();
            $table->date('fecha')->unique();
            $table->decimal('valor', 12, 4);
            $table->timestamps();
        });

        Schema::create('reservas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->restrictOnDelete();
            $table->foreignId('convenio_id')->nullable()->constrained()->nullOnDelete();
            $table->string('estado', 30)->default('confirmada')->index();
            $table->dateTime('inicio');
            $table->dateTime('fin');
            $table->unsignedBigInteger('total_centavos')->default(0);
            $table->string('moneda', 3)->default('COP');
            $table->string('qr_codigo', 255)->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('contratos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reserva_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('numero', 80)->unique();
            $table->json('condiciones')->nullable();
            $table->dateTime('firmado_at')->nullable();
            $table->timestamps();
        });

        Schema::create('actas_entrega', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reserva_id')->unique()->constrained()->cascadeOnDelete();
            $table->dateTime('entregado_at');
            $table->json('checklist')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('actas_devolucion', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reserva_id')->unique()->constrained()->cascadeOnDelete();
            $table->dateTime('devuelto_at');
            $table->json('checklist')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('multas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reserva_id')->constrained()->cascadeOnDelete();
            $table->string('concepto', 160);
            $table->unsignedBigInteger('monto_centavos');
            $table->timestamps();
        });

        Schema::create('danos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reserva_id')->constrained()->cascadeOnDelete();
            $table->string('descripcion', 255);
            $table->unsignedBigInteger('monto_centavos')->default(0);
            $table->timestamps();
        });

        Schema::create('facturas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reserva_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('numero', 80)->unique();
            $table->string('estado', 30)->default('pendiente')->index();
            $table->unsignedBigInteger('subtotal_centavos');
            $table->unsignedBigInteger('impuestos_centavos')->default(0);
            $table->unsignedBigInteger('total_centavos');
            $table->string('moneda', 3)->default('COP');
            $table->json('desglose');
            $table->timestamps();
        });

        Schema::create('pagos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reserva_id')->constrained()->cascadeOnDelete();
            $table->string('estado', 30)->default('pendiente')->index();
            $table->string('metodo', 30);
            $table->unsignedBigInteger('monto_centavos');
            $table->string('referencia', 120)->nullable()->unique();
            $table->string('mensaje')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
        Schema::dropIfExists('facturas');
        Schema::dropIfExists('danos');
        Schema::dropIfExists('multas');
        Schema::dropIfExists('actas_devolucion');
        Schema::dropIfExists('actas_entrega');
        Schema::dropIfExists('contratos');
        Schema::dropIfExists('reservas');
        Schema::dropIfExists('trm');
        Schema::dropIfExists('tarifas');
        Schema::dropIfExists('convenios');
        Schema::dropIfExists('polizas');
        Schema::dropIfExists('vehiculos');
        Schema::dropIfExists('licencias_conduccion');
    }
};
