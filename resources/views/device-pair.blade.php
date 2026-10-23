<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Parear dispositivo</title>
    <style>body{font-family:system-ui;background:#f4f6f9;display:grid;place-items:center;min-height:100vh;margin:0}.card{width:min(92%,460px);background:#fff;padding:2rem;border-radius:12px;box-shadow:0 8px 30px #0002}input,button{box-sizing:border-box;width:100%;padding:.8rem;font-size:1.1rem;margin-top:.7rem}button{background:#087f5b;color:#fff;border:0;border-radius:6px;font-weight:700}.error{color:#dc3545}</style>
</head>
<body><main class="card"><h1>Parear TotemSync</h1><p>Confirme o código exibido no painel administrativo.</p><form method="POST" action="{{ route('device.pair') }}">@csrf<label for="code">Código de 6 dígitos</label><input id="code" name="code" value="{{ old('code', $code) }}" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required>@error('code')<p class="error">{{ $message }}</p>@enderror<button type="submit">Parear este dispositivo</button></form></main></body>
</html>
