<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('paginas', function (Blueprint $table) {
            // Cria a coluna para guardar o nome do Grupo (Ex: "História", "Cultura")
            $table->string('grupo_slide')->nullable()->after('pagina_titulo');

            // Cria a coluna para guardar a ordem numérica (Ex: 1, 2, 3...)
            $table->integer('ordem_slide')->default(0)->after('grupo_slide');
        });
    }

    public function down()
    {
        Schema::table('paginas', function (Blueprint $table) {
            $table->dropColumn(['grupo_slide', 'ordem_slide']);
        });
    }
};
