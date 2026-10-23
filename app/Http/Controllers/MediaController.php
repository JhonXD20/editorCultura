<?php

namespace App\Http\Controllers;

use App\Models\MediaAsset;
use App\Support\Audit;
use App\Support\MediaReferences;
use App\Support\MediaStorage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function index(): View
    {
        $this->authorize('edit-content');

        return view('media.index', ['media' => MediaAsset::withCount('paginas')->latest()->paginate(40)]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorize('edit-content');
        $file = $request->validate([
            'file' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm', 'max:204800'],
        ])['file'];
        $asset = MediaStorage::store($file, $request->user()->id);
        Audit::record('media.uploaded', $asset);

        return $request->expectsJson()
            ? response()->json(['message' => 'Arquivo adicionado à biblioteca.', 'redirect' => route('midias.index')], 201)
            : back()->with('success', 'Arquivo adicionado à biblioteca.');
    }

    public function destroy(Request $request, MediaAsset $medium): JsonResponse|RedirectResponse
    {
        $this->authorize('edit-content');
        MediaReferences::rebuild();
        if ($medium->paginas()->exists()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'O arquivo está em uso e não pode ser excluído.'], 422)
                : back()->with('error', 'O arquivo está em uso e não pode ser excluído.');
        }
        if (! Storage::disk($medium->disco)->delete($medium->caminho)) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Não foi possível remover o arquivo. Tente novamente.'], 503)
                : back()->with('error', 'Não foi possível remover o arquivo.');
        }
        Audit::record('media.deleted', $medium, ['caminho' => $medium->caminho]);
        $medium->delete();

        return $request->expectsJson()
            ? response()->json(['message' => 'Arquivo removido.', 'deleted_id' => $medium->id])
            : back()->with('success', 'Arquivo removido.');
    }
}
