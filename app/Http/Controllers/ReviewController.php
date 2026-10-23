<?php

namespace App\Http\Controllers;

use App\Models\PageRevision;
use App\Models\Pagina;
use App\Models\ReviewRequest;
use App\Models\User;
use App\Notifications\ContentReviewNotification;
use App\Support\Audit;
use App\Support\MediaReferences;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function history(Pagina $pagina): View
    {
        $this->authorize('edit-content');

        return view('reviews.history', ['pagina' => $pagina, 'revisions' => $pagina->revisions()->paginate(30)]);
    }

    public function index(): View
    {
        $this->authorize('administer');
        $reviews = ReviewRequest::with(['pagina', 'revision'])->latest('enviado_em')->latest('id')->paginate(30);

        return view('reviews.index', compact('reviews'));
    }

    public function approve(ReviewRequest $review): RedirectResponse
    {
        $this->authorize('publish-content');

        DB::transaction(function () use ($review): void {
            $locked = ReviewRequest::whereKey($review->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->situacao === 'pending', 409, 'Esta revisão já foi processada.');
            $revision = PageRevision::findOrFail($locked->revisao_pagina_id);
            $pagina = Pagina::whereKey($locked->pagina_id)->lockForUpdate()->firstOrFail();

            $revision->update(['estado' => 'published']);
            $pagina->update([
                'pagina_html_publicado' => $revision->html,
                'pagina_css_publicado' => $revision->css,
                'revisao_publicada_id' => $revision->id,
                'pagina_versao_publicacao' => $pagina->pagina_versao_publicacao + 1,
                'pagina_estado_editorial' => $pagina->pagina_dados_editor === $revision->dados_projeto ? 'published' : 'draft',
                'pagina_publicada_em' => now(),
            ]);
            $locked->update([
                'situacao' => 'approved',
                'revisado_por' => auth()->id(),
                'revisado_em' => now(),
            ]);
            MediaReferences::sync($pagina);
            DB::table('totens')->update(['updated_at' => now()]);
            Audit::record('content.review.approved', $pagina, ['review_id' => $locked->id]);
        });

        $review->refresh()->load('pagina');
        User::find($review->enviado_por)?->notify(new ContentReviewNotification($review, 'approved'));

        return back()->with('success', 'Revisão aprovada e publicada.');
    }

    public function preview(ReviewRequest $review): View
    {
        $this->authorize('publish-content');
        $review->load(['pagina', 'revision']);

        return view('reviews.preview', compact('review'));
    }

    public function reject(Request $request, ReviewRequest $review): RedirectResponse
    {
        $this->authorize('publish-content');
        $validated = $request->validate(['comment' => ['required', 'string', 'max:2000']]);

        DB::transaction(function () use ($review, $validated): void {
            $locked = ReviewRequest::whereKey($review->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->situacao === 'pending', 409, 'Esta revisão já foi processada.');
            $locked->update([
                'situacao' => 'changes_requested',
                'comentario_revisao' => $validated['comment'],
                'revisado_por' => auth()->id(),
                'revisado_em' => now(),
            ]);
            $locked->revision()->update(['estado' => 'changes_requested']);
            $locked->pagina()->where('pagina_estado_editorial', 'pending_review')->update(['pagina_estado_editorial' => 'changes_requested']);
            Audit::record('content.review.changes_requested', $locked->pagina, [
                'review_id' => $locked->id,
                'comment' => $validated['comment'],
            ]);
        });

        $review->refresh()->load('pagina');
        User::find($review->enviado_por)?->notify(new ContentReviewNotification($review, 'changes_requested'));

        return back()->with('success', 'Conteúdo devolvido ao editor com as orientações informadas.');
    }

    public function restore(Pagina $pagina, PageRevision $revision): RedirectResponse
    {
        $this->authorize('publish-content');
        abort_unless($revision->pagina_id === $pagina->id, 404);

        DB::transaction(function () use ($pagina, $revision): void {
            $locked = Pagina::whereKey($pagina->id)->lockForUpdate()->firstOrFail();
            $version = ((int) $locked->revisions()->max('versao')) + 1;
            $copy = PageRevision::create([
                'pagina_id' => $locked->id,
                'criado_por' => auth()->id(),
                'versao' => $version,
                'estado' => 'draft',
                'dados_projeto' => $revision->dados_projeto,
                'html' => $revision->html,
                'css' => $revision->css,
                'hash_verificacao' => $revision->hash_verificacao,
                'observacao' => "Restauração da revisão {$revision->versao}",
            ]);
            $locked->update([
                'pagina_dados_editor' => $copy->dados_projeto,
                'pagina_versao_rascunho' => $locked->pagina_versao_rascunho + 1,
                'pagina_estado_editorial' => 'draft',
            ]);
            MediaReferences::sync($locked);
            Audit::record('content.revision.restored', $locked, [
                'source_revision_id' => $revision->id,
                'new_revision_id' => $copy->id,
            ]);
        });

        return redirect()->route('gestao-conteudo.edit', $pagina)
            ->with('success', 'A revisão foi restaurada como um novo rascunho.');
    }
}
