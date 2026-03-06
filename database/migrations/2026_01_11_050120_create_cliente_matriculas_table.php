<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cliente_matriculas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('restrict');
            $table->enum('tipo', ['membresia', 'clase'])->default('membresia');
            $table->foreignId('membresia_id')->nullable()->constrained('membresias')->onDelete('restrict');
            $table->foreignId('clase_id')->nullable()->constrained('clases')->onDelete('restrict');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable(); // Nullable para clases que pueden no tener fecha fin
            $table->enum('estado', ['activa', 'vencida', 'cancelada', 'congelada', 'completada'])->default('activa');
            $table->decimal('precio_lista', 10, 2);
            $table->decimal('descuento_monto', 10, 2)->default(0);
            $table->decimal('precio_final', 10, 2);
            $table->foreignId('asesor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('canal_venta')->nullable();
            $table->json('fechas_congelacion')->nullable();
            $table->text('motivo_cancelacion')->nullable();
            // Campos específicos para clases
            $table->integer('sesiones_totales')->nullable(); // Para clases tipo paquete
            $table->integer('sesiones_usadas')->default(0); // Sesiones utilizadas
            $table->timestamps();

            $table->index('cliente_id');
            $table->index('tipo');
            $table->index('membresia_id');
            $table->index('clase_id');
            $table->index('estado');
            $table->index('fecha_inicio');
            $table->index('fecha_fin');
        });
        
        // Asegurar que solo uno de membresia_id o clase_id esté presente según el tipo
        // Usar DB::statement para agregar el constraint CHECK
        DB::statement('ALTER TABLE cliente_matriculas ADD CONSTRAINT check_tipo_matricula CHECK ((tipo = \'membresia\' AND membresia_id IS NOT NULL AND clase_id IS NULL) OR (tipo = \'clase\' AND clase_id IS NOT NULL AND membresia_id IS NULL))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cliente_matriculas');
    }
};
