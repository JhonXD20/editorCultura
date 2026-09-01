<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('slug')->unique(); // URL amigável (ex: /noticias)
            $table->text('descricao')->nullable();
            $table->timestamps(); // Cria os campos created_at e updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
