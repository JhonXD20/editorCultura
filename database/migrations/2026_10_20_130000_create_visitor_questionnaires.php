<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('totens', fn (Blueprint $table) => $table->boolean('questionario_ativo')->default(false));
        Schema::create('respostas_questionario', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('totem_id')->nullable()->constrained('totens')->nullOnDelete();
            $table->string('versao_instrumento', 20);
            foreach (range(1, 5) as $number) {
                $table->unsignedTinyInteger('q'.$number)->nullable();
            }
            $table->dateTime('ocorrido_em');
            $table->boolean('horario_validado');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['totem_id', 'ocorrido_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('respostas_questionario');
        Schema::table('totens', fn (Blueprint $table) => $table->dropColumn('questionario_ativo'));
    }
};
