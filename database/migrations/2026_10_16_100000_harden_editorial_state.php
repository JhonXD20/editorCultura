<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->unsignedInteger('session_version')->default(1));
        Schema::table('paginas', fn (Blueprint $table) => $table->timestamp('legacy_validated_at')->nullable());
        Schema::table('daily_metrics', fn (Blueprint $table) => $table->char('aggregation_key', 64)->nullable()->unique());
    }

    public function down(): void
    {
        Schema::table('daily_metrics', function (Blueprint $table): void {
            $table->dropUnique(['aggregation_key']);
            $table->dropColumn('aggregation_key');
        });
        Schema::table('paginas', fn (Blueprint $table) => $table->dropColumn('legacy_validated_at'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('session_version'));
    }
};
