<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interacoes_totem', function (Blueprint $table) {
            $table->id();

            // Equipamento utilizado
            $table->foreignId('totem_id')
                ->nullable()
                ->constrained('totens')
                ->nullOnDelete();

            // Página visualizada
            $table->foreignId('pagina_id')
                ->nullable()
                ->constrained('paginas')
                ->nullOnDelete();

            // toque ou tempo_pagina
            $table->string('tipo', 30);

            // Tempo de permanência na página
            $table->unsignedInteger('duracao_segundos')
                ->nullable();

            $table->timestamps();

            // Índices para otimizar os relatórios
            $table->index(['tipo', 'created_at']);
            $table->index(['totem_id', 'created_at']);
            $table->index(['pagina_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interacoes_totem');
    }
};
