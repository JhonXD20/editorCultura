<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Refuse ambiguous existing data instead of silently changing the selected home.
        if (DB::table('paginas')->where('pagina_tipo_layout', 'home')->count() > 1) {
            throw new RuntimeException('Há mais de uma página inicial. Escolha uma única inicial antes de migrar.');
        }
        Schema::table('paginas', function (Blueprint $table): void {
            $table->unsignedTinyInteger('marcador_pagina_inicial')->nullable()
                ->virtualAs("CASE WHEN pagina_tipo_layout = 'home' THEN 1 ELSE NULL END");
            $table->unique('marcador_pagina_inicial');
        });
    }

    public function down(): void
    {
        Schema::table('paginas', function (Blueprint $table): void {
            $table->dropUnique(['marcador_pagina_inicial']);
            $table->dropColumn('marcador_pagina_inicial');
        });
    }
};
