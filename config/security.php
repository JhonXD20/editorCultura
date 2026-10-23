<?php

return [
    'csp_enabled' => env('SECURITY_CSP_ENABLED', env('APP_ENV') === 'production'),
    'csp' => "default-src 'self'; img-src 'self' data: blob:; media-src 'self' blob:; style-src 'self' 'unsafe-inline'; script-src 'self'; font-src 'self' data:; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'",
];
