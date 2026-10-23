<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;
use JsonException;

final class GrapesProjectGuard
{
    private const MAX_BYTES = 5_000_000;

    private const MAX_DEPTH = 35;

    private const MAX_NODES = 20_000;

    private const FORBIDDEN_COMPONENT_TYPES = [
        'iframe',
        'script',
        'svg',
    ];

    private const FORBIDDEN_TAGS = [
        'applet', 'base', 'embed', 'form', 'iframe', 'input', 'link', 'meta',
        'object', 'script', 'select', 'style', 'svg', 'textarea',
    ];

    public static function validate(array $project): void
    {
        try {
            $json = json_encode($project, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            self::fail('O documento do editor possui dados inválidos.');
        }

        if (strlen($json) > self::MAX_BYTES) {
            self::fail('O documento excede o limite de 5 MB.');
        }

        $nodes = 0;
        self::inspect($project, 0, $nodes);
    }

    private static function inspect(mixed $value, int $depth, int &$nodes): void
    {
        if ($depth > self::MAX_DEPTH) {
            self::fail('O documento possui níveis demais.');
        }

        if (! is_array($value)) {
            if (is_string($value) && strlen($value) > 500_000) {
                self::fail('Um dos conteúdos do documento é grande demais.');
            }

            return;
        }

        $nodes++;

        if ($nodes > self::MAX_NODES) {
            self::fail('O documento possui componentes demais.');
        }

        $type = strtolower((string) ($value['type'] ?? ''));
        $tag = strtolower((string) ($value['tagName'] ?? ''));

        if (in_array($type, self::FORBIDDEN_COMPONENT_TYPES, true)
            || in_array($tag, self::FORBIDDEN_TAGS, true)) {
            self::fail('O documento contém um componente não permitido.');
        }

        foreach (['script', 'script-export', 'script-props'] as $key) {
            if (! empty($value[$key])) {
                self::fail('Scripts personalizados não são permitidos.');
            }
        }

        if (isset($value['attributes']) && is_array($value['attributes'])) {
            foreach ($value['attributes'] as $name => $attributeValue) {
                $name = strtolower((string) $name);

                if (str_starts_with($name, 'on') || in_array($name, ['srcdoc', 'formaction'], true)) {
                    self::fail('O documento contém um atributo não permitido.');
                }

                if (is_string($attributeValue)
                    && preg_match('/^\s*(?:javascript|vbscript|data):/i', $attributeValue)) {
                    self::fail('O documento contém uma URL não permitida.');
                }
            }
        }

        foreach ($value as $key => $child) {
            if (is_string($child) && in_array($key, ['content', 'component', 'components'], true)
                && preg_match('~<\s*(?:script|iframe|svg|object|embed|form|input|style|link|meta)\b|\bon[a-z]+\s*=|(?:javascript|vbscript)\s*:~i', html_entity_decode($child, ENT_QUOTES | ENT_HTML5, 'UTF-8'))) {
                self::fail('O documento contém HTML executável ou não permitido.');
            }
            if (is_string($child) && in_array($key, ['src', 'poster'], true)
                && $child !== '' && (! str_starts_with($child, '/') || str_starts_with($child, '//') || str_contains($child, '\\'))) {
                self::fail('Imagens e vídeos devem vir da biblioteca local.');
            }
            if ($key === 'style' && is_array($child)) {
                foreach ($child as $styleValue) {
                    if (is_string($styleValue) && preg_match('~url\s*\(|@import|expression\s*\(|javascript:|\\\\~i', $styleValue)) {
                        self::fail('O estilo contém recursos externos ou executáveis.');
                    }
                }
            }
            if (is_string($key) && preg_match('/^on[a-z]+$/i', $key)) {
                self::fail('O documento contém comportamento executável.');
            }

            self::inspect($child, $depth + 1, $nodes);
        }
    }

    private static function fail(string $message): never
    {
        throw ValidationException::withMessages([
            'project_data' => $message,
        ]);
    }
}
