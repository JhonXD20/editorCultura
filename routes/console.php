<?php

use App\Models\MediaAsset;
use App\Models\Pagina;
use App\Support\MediaReferences;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    Pagina::with('revisions')->each(function (Pagina $page): void {
        $protected = $page->revisions->whereIn('estado', ['published', 'pending_review'])->pluck('id');
        $keep = $page->revisions->take(30)->pluck('id')->merge($protected)->unique();
        $page->revisions()->whereNotIn('id', $keep)->delete();
        MediaReferences::sync($page);
    });
})->name('prune-page-revisions')->dailyAt('01:15')->withoutOverlapping();

Schedule::call(function (): void {
    MediaReferences::rebuild();
    MediaAsset::doesntHave('paginas')->where('created_at', '<', now()->subDays(30))
        ->where(fn ($query) => $query->whereNull('ultima_referencia_em')->orWhere('ultima_referencia_em', '<', now()->subDays(30)))
        ->each(function (MediaAsset $asset): void {
            Storage::disk($asset->disco)->delete($asset->caminho);
            $asset->delete();
        });
})->name('prune-orphan-media')->dailyAt('01:30')->withoutOverlapping();

Schedule::call(function (): void {
    $cutoff = now()->subMonthsNoOverflow(24)->startOfDay();
    DB::table('registros_auditoria')->where('created_at', '<', $cutoff)->delete();
    DB::table('codigos_pareamento')->where('expira_em', '<', now()->subDay())->delete();
    DB::table('respostas_questionario')->where('created_at', '<', $cutoff)->delete();
})->name('aggregate-and-prune-operational-data')->dailyAt('02:00')->withoutOverlapping();

Schedule::command('telemetry:aggregate')->dailyAt('01:45')->withoutOverlapping();
