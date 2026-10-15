<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('totens', function (Blueprint $table) {
            $table->foreignId('pagina_inicial_id')->nullable()
                ->constrained('paginas')->nullOnDelete();
            $table->string('totem_orientacao', 20)->default('landscape');
            $table->unsignedInteger('totem_inatividade_segundos')->default(120);
        });
    }

    public function down(): void
    {
        Schema::table('totens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pagina_inicial_id');
            $table->dropColumn(['totem_orientacao', 'totem_inatividade_segundos']);
        });
    }
};
