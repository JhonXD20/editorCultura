<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Revisão #{{ $review->revision->versao }} · {{ $review->pagina->pagina_titulo }}</title>
    <style>html,body{margin:0;min-height:100%;background:#dfe6e2}.review-banner{position:sticky;top:0;z-index:99999;padding:10px 18px;background:#20252a;color:#fff;font:600 14px system-ui}.review-canvas{width:1366px;min-height:768px;margin:20px auto;background:#fff;box-shadow:0 8px 30px #0002;overflow:hidden}</style>
    <style>{!! $review->revision->css !!}</style>
</head>
<body><header class="review-banner">Prévia imutável · revisão #{{ $review->revision->versao }} · {{ $review->pagina->pagina_titulo }}</header><main class="review-canvas">{!! $review->revision->html !!}</main></body>
</html>
