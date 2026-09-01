<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conteudos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('slug')->unique();
            $table->longText('texto')->nullable();
            $table->string('imagem_capa')->nullable(); // Caminho para a imagem

            // Status do conteúdo (ex: Rascunho, Publicado, Inativo)
            $table->string('status')->default('rascunho');

            // Relacionamento com a tabela de usuários (Autor)
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade'); // Se deletar o usuário, deleta os conteúdos dele

            // Relacionamento com a tabela de categorias
            $table->foreignId('categoria_id')
                ->nullable()
                ->constrained('categorias')
                ->onDelete('set null'); // Se deletar a categoria, o conteúdo fica sem categoria

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conteudos');
    }
};
