<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paginas', function (Blueprint $table) {
            $table->json('editor_project_data')
                ->nullable()
                ->after('pagina_ativo');

            $table->longText('published_html')
                ->nullable()
                ->after('editor_project_data');

            $table->longText('published_css')
                ->nullable()
                ->after('published_html');

            $table->string('editor_version', 30)
                ->nullable()
                ->after('published_css');

            $table->timestamp('published_at')
                ->nullable()
                ->after('editor_version');
        });
    }

    public function down(): void
    {
        Schema::table('paginas', function (Blueprint $table) {
            $table->dropColumn([
                'editor_project_data',
                'published_html',
                'published_css',
                'editor_version',
                'published_at',
            ]);
        });
    }
};
