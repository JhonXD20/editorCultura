<?php

namespace App\Http\Controllers;

use App\Models\PageRevision;
use App\Models\Pagina;
use App\Models\ReviewRequest;
use App\Models\User;
use App\Notifications\ContentReviewNotification;
use App\Support\Audit;
use App\Support\GrapesProjectGuard;
use App\Support\MediaReferences;
use App\Support\MediaStorage;
use App\Support\PublishedPageSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class VisualEditorController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        $this->authorize('edit-content');
        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'],
        ]);
        $assets = [];

        foreach ($validated['files'] as $file) {
            $media = MediaStorage::store($file, $request->user()->id);
            $assets[] = [
                'src' => '/storage/'.$media->caminho,
                'name' => $file->getClientOriginalName(),
                'type' => 'image',
                'media_id' => $media->id,
            ];
        }

        Audit::record('media.uploaded', null, ['media_ids' => collect($assets)->pluck('media_id')->all()]);

        return response()->json(['data' => $assets]);
    }

    public function save(Request $request, Pagina $pagina): JsonResponse
    {
        $this->authorize('edit-content');
        $validated = $request->validate([
            'project_data' => ['required', 'array'],
            'base_version' => ['required', 'integer', 'min:1'],
            'manual' => ['sometimes', 'boolean'],
            'html' => ['nullable', 'string', 'max:2000000'],
            'css' => ['nullable', 'string', 'max:1000000'],
        ]);
        GrapesProjectGuard::validate($validated['project_data']);

        DB::transaction(function () use ($pagina, $validated): void {
            $locked = Pagina::whereKey($pagina->id)->lockForUpdate()->firstOrFail();
            $this->assertVersion($locked, $validated['base_version']);
            $locked->update([
                'pagina_dados_editor' => $validated['project_data'],
                'pagina_versao_editor' => 'grapesjs-0.23',
                'pagina_versao_rascunho' => $locked->pagina_versao_rascunho + 1,
                'pagina_estado_editorial' => 'draft',
            ]);
            if ($validated['manual'] ?? false) {
                $this->createRevision($locked, [
                    'project_data' => $validated['project_data'],
                    'html' => PublishedPageSanitizer::html($validated['html'] ?? ''),
                    'css' => PublishedPageSanitizer::css($validated['css'] ?? ''),
                ], 'draft');
            }
            MediaReferences::sync($locked);
            Audit::record('content.draft.saved', $locked, ['pagina_versao_rascunho' => $locked->pagina_versao_rascunho]);
        });

        return response()->json([
            'success' => true,
            'saved_at' => $pagina->fresh()->updated_at?->toIso8601String(),
            'draft_version' => $pagina->fresh()->pagina_versao_rascunho,
        ]);
    }

    public function submit(Request $request, Pagina $pagina): JsonResponse
    {
        $this->authorize('edit-content');
        $payload = $this->validatedPublication($request);
        $review = null;

        DB::transaction(function () use ($pagina, $payload, &$review): void {
            $locked = Pagina::whereKey($pagina->id)->lockForUpdate()->firstOrFail();
            $this->assertVersion($locked, $payload['base_version']);
            $revision = $this->createRevision($locked, $payload, 'pending_review');
            $review = ReviewRequest::create([
                'pagina_id' => $locked->id,
                'revisao_pagina_id' => $revision->id,
                'enviado_por' => auth()->id(),
                'situacao' => 'pending',
                'enviado_em' => now(),
            ]);
            $locked->update([
                'pagina_dados_editor' => $payload['project_data'],
                'pagina_versao_rascunho' => $locked->pagina_versao_rascunho + 1,
                'pagina_estado_editorial' => 'pending_review',
            ]);
            MediaReferences::sync($locked);
            Audit::record('content.review.submitted', $locked, ['review_id' => $review->id]);
        });

        $review->load('pagina');
        Notification::send(
            User::where('perfil', 'admin')->where('ativo', true)->get(),
            new ContentReviewNotification($review, 'submitted')
        );

        return response()->json([
            'success' => true,
            'message' => 'Conteúdo enviado para revisão.',
            'draft_version' => $pagina->fresh()->pagina_versao_rascunho,
        ]);
    }

    public function publish(Request $request, Pagina $pagina): JsonResponse
    {
        $this->authorize('publish', $pagina);
        $payload = $this->validatedPublication($request);

        DB::transaction(function () use ($pagina, $payload): void {
            $locked = Pagina::whereKey($pagina->id)->lockForUpdate()->firstOrFail();
            $this->assertVersion($locked, $payload['base_version']);
            $revision = $this->createRevision($locked, $payload, 'published');
            $locked->update([
                'pagina_dados_editor' => $payload['project_data'],
                'pagina_html_publicado' => $payload['html'],
                'pagina_css_publicado' => $payload['css'],
                'revisao_publicada_id' => $revision->id,
                'pagina_versao_editor' => 'grapesjs-0.23',
                'pagina_versao_rascunho' => $locked->pagina_versao_rascunho + 1,
                'pagina_versao_publicacao' => $locked->pagina_versao_publicacao + 1,
                'pagina_estado_editorial' => 'published',
                'pagina_publicada_em' => now(),
            ]);
            MediaReferences::sync($locked);
            DB::table('totens')->update(['updated_at' => now()]);
            Audit::record('content.published', $locked, ['revision_id' => $revision->id]);
        });

        return response()->json([
            'success' => true,
            'published_at' => $pagina->fresh()->pagina_publicada_em?->toIso8601String(),
            'draft_version' => $pagina->fresh()->pagina_versao_rascunho,
            'publication_version' => $pagina->fresh()->pagina_versao_publicacao,
            'public_url' => route('totem.exibir', $pagina),
        ]);
    }

    private function validatedPublication(Request $request): array
    {
        $validated = $request->validate([
            'project_data' => ['required', 'array'],
            'html' => ['required', 'string', 'max:2000000'],
            'css' => ['nullable', 'string', 'max:1000000'],
            'base_version' => ['required', 'integer', 'min:1'],
        ]);
        GrapesProjectGuard::validate($validated['project_data']);
        $validated['html'] = PublishedPageSanitizer::html($validated['html']);
        $validated['css'] = PublishedPageSanitizer::css($validated['css'] ?? '');

        return $validated;
    }

    private function assertVersion(Pagina $pagina, int $baseVersion): void
    {
        abort_if($baseVersion !== $pagina->pagina_versao_rascunho, 409,
            'Este conteúdo foi alterado em outra sessão. Recarregue a página antes de continuar.');
    }

    private function createRevision(Pagina $pagina, array $payload, string $state): PageRevision
    {
        $version = ((int) $pagina->revisions()->max('versao')) + 1;
        $checksum = hash('sha256', json_encode(
            [$payload['project_data'], $payload['html'], $payload['css']],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));

        return PageRevision::create([
            'pagina_id' => $pagina->id,
            'criado_por' => auth()->id(),
            'versao' => $version,
            'estado' => $state,
            'dados_projeto' => $payload['project_data'],
            'html' => $payload['html'],
            'css' => $payload['css'],
            'hash_verificacao' => $checksum,
        ]);
    }
}
