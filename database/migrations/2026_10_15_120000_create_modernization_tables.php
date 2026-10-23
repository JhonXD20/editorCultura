<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('editor')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_login_at')->nullable();
        });
        $firstUserId = DB::table('users')->orderBy('id')->value('id');
        if ($firstUserId) {
            DB::table('users')->where('id', $firstUserId)->update(['role' => 'admin']);
        }

        Schema::table('paginas', function (Blueprint $table) {
            $table->unsignedBigInteger('draft_version')->default(1);
            $table->unsignedBigInteger('publication_version')->default(0)->index();
            $table->string('workflow_status', 30)->default('draft')->index();
        });

        $homeId = DB::table('paginas')->where('pagina_ativo', true)
            ->orderByRaw("CASE WHEN pagina_tipo_layout = 'home' THEN 0 ELSE 1 END")
            ->orderBy('id')->value('id');
        if ($homeId) {
            DB::table('paginas')->where('pagina_tipo_layout', 'home')->where('id', '!=', $homeId)
                ->update(['pagina_tipo_layout' => 'interna']);
            DB::table('paginas')->where('id', $homeId)->update(['pagina_tipo_layout' => 'home']);
        }

        Schema::table('totens', function (Blueprint $table) {
            $table->timestamp('reload_requested_at')->nullable();
        });

        Schema::create('page_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pagina_id')->constrained('paginas')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('version');
            $table->string('state', 30)->default('draft')->index();
            $table->json('project_data')->nullable();
            $table->longText('html')->nullable();
            $table->longText('css')->nullable();
            $table->char('checksum', 64);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['pagina_id', 'version']);
        });

        Schema::table('paginas', function (Blueprint $table) {
            $table->foreignId('published_revision_id')
                ->nullable()
                ->constrained('page_revisions')
                ->nullOnDelete();
        });

        Schema::create('review_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pagina_id')->constrained('paginas')->cascadeOnDelete();
            $table->foreignId('page_revision_id')->constrained('page_revisions')->cascadeOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('pending')->index();
            $table->text('review_comment')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk', 40);
            $table->string('path', 500)->unique();
            $table->string('original_name', 255);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->char('checksum', 64);
            $table->string('kind', 20)->index();
            $table->json('metadata')->nullable();
            $table->timestamp('last_referenced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('media_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_asset_id')->constrained('media_assets')->cascadeOnDelete();
            $table->foreignId('pagina_id')->constrained('paginas')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['media_asset_id', 'pagina_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80)->index();
            $table->nullableMorphs('auditable');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('device_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('totem_id')->constrained('totens')->cascadeOnDelete();
            $table->string('name', 100)->default('Dispositivo principal');
            $table->char('token_hash', 64)->unique();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('pairing_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('totem_id')->constrained('totens')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->char('code_hash', 64)->unique();
            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::table('interacoes_totem', function (Blueprint $table) {
            $table->uuid('event_uuid')->nullable()->unique();
        });

        Schema::create('daily_metrics', function (Blueprint $table) {
            $table->id();
            $table->date('metric_date')->index();
            $table->foreignId('totem_id')->nullable()->constrained('totens')->nullOnDelete();
            $table->foreignId('pagina_id')->nullable()->constrained('paginas')->nullOnDelete();
            $table->string('tipo', 30);
            $table->unsignedBigInteger('event_count')->default(0);
            $table->unsignedBigInteger('duration_seconds')->default(0);
            $table->timestamps();
            $table->unique(['metric_date', 'totem_id', 'pagina_id', 'tipo'], 'daily_metrics_unique');
        });

        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('daily_metrics');
        Schema::table('interacoes_totem', function (Blueprint $table): void {
            $table->dropUnique(['event_uuid']);
            $table->dropColumn('event_uuid');
        });
        Schema::dropIfExists('pairing_codes');
        Schema::dropIfExists('device_credentials');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('media_references');
        Schema::dropIfExists('media_assets');
        Schema::dropIfExists('review_requests');
        Schema::table('paginas', fn (Blueprint $table) => $table->dropConstrainedForeignId('published_revision_id'));
        Schema::dropIfExists('page_revisions');
        Schema::table('paginas', function (Blueprint $table) {
            $table->dropIndex(['publication_version']);
            $table->dropIndex(['workflow_status']);
            $table->dropColumn(['draft_version', 'publication_version', 'workflow_status']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['is_active']);
            $table->dropColumn(['role', 'is_active', 'last_login_at']);
        });
        Schema::table('totens', fn (Blueprint $table) => $table->dropColumn('reload_requested_at'));
    }
};
