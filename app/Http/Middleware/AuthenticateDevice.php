<?php

namespace App\Http\Middleware;

use App\Models\DeviceCredential;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateDevice
{
    public const COOKIE = 'totemsync_device';

    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) ($request->cookie(self::COOKIE) ?: $request->bearerToken() ?: '');
        $credential = $token === '' ? null : DeviceCredential::with('totem')
            ->where('hash_token', hash('sha256', $token))
            ->whereNull('revogado_em')
            ->first();

        if (! $credential) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Dispositivo não pareado ou revogado.'], 401)
                : response()->view('device-unpaired', status: 401);
        }

        $credential->forceFill(['ultimo_contato_em' => now()])->saveQuietly();
        $request->attributes->set('device_credential', $credential);
        $request->attributes->set('totem', $credential->totem);

        return $next($request);
    }
}
