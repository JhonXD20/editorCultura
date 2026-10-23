<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secoes', function (Blueprint $table): void {
            $table->id();
            $table->string('nome', 100)->unique();
            $table->unsignedInteger('versao')->default(1);
            $table->timestamps();
        });
        Schema::table('paginas', function (Blueprint $table): void {
            $table->foreignId('secao_id')->nullable()->constrained('secoes')->restrictOnDelete();
            $table->index(['secao_id', 'ordem_slide', 'id']);
        });

        // Keep legacy fields and content intact, including for a rollback.
        $groups = DB::table('paginas')->where('pagina_tipo_layout', '!=', 'home')
            ->whereNotNull('grupo_slide')->where('grupo_slide', '!=', '')
            ->distinct()->pluck('grupo_slide');
        foreach ($groups as $group) {
            $name = mb_substr($group, 0, 100);
            $suffix = 1;
            while (DB::table('secoes')->where('nome', $name)->exists()) {
                $name = mb_substr($group, 0, 85).' ('.($suffix++).')';
            }
            $id = DB::table('secoes')->insertGetId([
                'nome' => $name, 'versao' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('paginas')->where('grupo_slide', $group)->where('pagina_tipo_layout', '!=', 'home')
                ->update(['secao_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('paginas', function (Blueprint $table): void {
            $table->dropIndex(['secao_id', 'ordem_slide', 'id']);
            $table->dropConstrainedForeignId('secao_id');
        });
        Schema::dropIfExists('secoes');
    }
};
