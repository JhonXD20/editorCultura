<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Audit;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('testing')) {
            // Browser acceptance tests exercise compiled assets, not a developer's live Vite server.
            Vite::useHotFile(storage_path('framework/testing/disabled-vite.hot'));
            if (config('mail.capture_test_messages')) {
                Event::listen(MessageSent::class, function (MessageSent $event): void {
                    file_put_contents(storage_path('framework/testing/mailbox.jsonl'), json_encode([
                        'to' => array_map(fn ($address) => $address->getAddress(), $event->message->getTo()),
                        'text' => $event->message->getTextBody(),
                    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE).PHP_EOL, FILE_APPEND | LOCK_EX);
                });
            }
        }
        Gate::define('administer', fn (User $user) => $user->ativo && $user->perfil === 'admin');
        Gate::define('publish-content', fn (User $user) => $user->ativo && $user->perfil === 'admin');
        Gate::define('edit-content', fn (User $user) => $user->ativo && $user->canEditContent());
        Gate::define('pair-device', fn (User $user) => $user->ativo && in_array($user->perfil, ['admin', 'pairing'], true));

        Event::listen(Login::class, function (Login $event): void {
            $event->user->forceFill(['ultimo_acesso_em' => now()])->save();
            Audit::record('auth.login', $event->user instanceof Model ? $event->user : null);
        });
    }
}
