<?php

namespace App\Http\Controllers;

use App\Models\Pagina;
use App\Support\GrapesProjectGuard;
use App\Support\PublishedPageSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VisualEditorController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp,gif',
                'max:10240',
            ],
        ]);

        $destination = public_path('totem/img/uploads');

        if (! is_dir($destination) && ! mkdir($destination, 0755, true) && ! is_dir($destination)) {
            Log::error('Nao foi possivel criar a pasta de uploads do editor visual.');

            return response()->json([
                'message' => 'Não foi possível armazenar a imagem.',
            ], 500);
        }

        $assets = [];

        foreach ($validated['files'] as $file) {
            $filename = Str::uuid().'.'.strtolower($file->extension());
            $file->move($destination, $filename);

            $assets[] = [
                'src' => '/totem/img/uploads/'.$filename,
                'name' => $file->getClientOriginalName(),
                'type' => 'image',
            ];
        }

        return response()->json(['data' => $assets]);
    }

    public function save(Request $request, Pagina $pagina): JsonResponse
    {
        $validated = $request->validate([
            'project_data' => ['required', 'array'],
        ]);

        GrapesProjectGuard::validate($validated['project_data']);

        $pagina->update([
            'editor_project_data' => $validated['project_data'],
            'editor_version' => 'grapesjs-0.23',
        ]);

        return response()->json([
            'success' => true,
            'saved_at' => $pagina->fresh()->updated_at?->toIso8601String(),
        ]);
    }

    public function publish(Request $request, Pagina $pagina): JsonResponse
    {
        $validated = $request->validate([
            'project_data' => ['required', 'array'],
            'html' => ['required', 'string', 'max:2000000'],
            'css' => ['nullable', 'string', 'max:1000000'],
        ]);

        GrapesProjectGuard::validate($validated['project_data']);

        $html = PublishedPageSanitizer::html($validated['html']);
        $css = PublishedPageSanitizer::css($validated['css'] ?? '');

        DB::transaction(function () use ($pagina, $validated, $html, $css): void {
            $pagina->update([
                'editor_project_data' => $validated['project_data'],
                'published_html' => $html,
                'published_css' => $css,
                'editor_version' => 'grapesjs-0.23',
                'published_at' => now(),
            ]);

            DB::table('totens')->update([
                'updated_at' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'published_at' => $pagina->fresh()->published_at?->toIso8601String(),
            'public_url' => route('totem.exibir', $pagina),
        ]);
    }
}
