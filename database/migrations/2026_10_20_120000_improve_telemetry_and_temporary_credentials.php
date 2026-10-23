<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interacoes_totem', function (Blueprint $table): void {
            // Null keeps historical events on their original receipt timestamp.
            $table->dateTime('ocorrido_em')->nullable();
            $table->boolean('horario_validado')->default(false);
            $table->index(['tipo', 'ocorrido_em']);
            $table->index(['totem_id', 'ocorrido_em']);
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->dateTime('senha_temporaria_expira_em')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('senha_temporaria_expira_em'));
        Schema::table('interacoes_totem', function (Blueprint $table): void {
            $table->dropIndex(['tipo', 'ocorrido_em']);
            $table->dropIndex(['totem_id', 'ocorrido_em']);
            $table->dropColumn(['ocorrido_em', 'horario_validado']);
        });
    }
};
