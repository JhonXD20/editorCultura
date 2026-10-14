<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Validation\ValidationException;

final class PublishedPageSanitizer
{
    private const ALLOWED_TAGS = [
        'a', 'article', 'b', 'blockquote', 'br', 'button', 'div', 'em',
        'footer', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header', 'hr',
        'i', 'img', 'li', 'main', 'nav', 'ol', 'p', 'section', 'source',
        'span', 'strong', 'sub', 'sup', 'u', 'ul', 'video',
    ];

    private const DROP_WITH_CONTENT = [
        'applet', 'base', 'embed', 'form', 'iframe', 'input', 'link', 'math',
        'meta', 'object', 'script', 'select', 'style', 'svg', 'textarea',
    ];

    private const STYLE_PROPERTIES = [
        'align-items', 'align-self', 'background', 'background-color',
        'border', 'border-bottom', 'border-color', 'border-left',
        'border-radius', 'border-right', 'border-style', 'border-top',
        'border-width', 'bottom', 'box-shadow', 'color', 'column-gap',
        'display', 'flex', 'flex-basis', 'flex-direction', 'flex-grow',
        'flex-shrink', 'flex-wrap', 'font-family', 'font-size', 'font-style',
        'font-weight', 'gap', 'grid-template-columns', 'height',
        'justify-content', 'left', 'letter-spacing', 'line-height',
        'list-style-type', 'margin', 'margin-bottom', 'margin-left',
        'margin-right', 'margin-top', 'max-height', 'max-width', 'min-height',
        'min-width', 'object-fit', 'opacity', 'overflow', 'overflow-x',
        'overflow-y', 'padding', 'padding-bottom', 'padding-left',
        'padding-right', 'padding-top', 'position', 'right', 'text-align',
        'text-decoration', 'text-transform', 'top', 'transform',
        'transform-origin', 'vertical-align', 'white-space', 'width',
        'word-break', 'z-index',
    ];

    public static function html(string $html): string
    {
        if (strlen($html) > 2_000_000) {
            self::fail('O HTML publicado excede o limite permitido.');
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="published-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('published-root');

        if (! $root) {
            return '';
        }

        self::cleanChildren($root);

        $result = '';

        foreach ($root->childNodes as $child) {
            $result .= $document->saveHTML($child);
        }

        return trim($result);
    }

    public static function css(string $css): string
    {
        if (strlen($css) > 1_000_000) {
            self::fail('O CSS publicado excede o limite permitido.');
        }

        $css = preg_replace('~/\*.*?\*/~s', '', $css) ?? '';

        if (preg_match(
            '/(?:@import|@font-face|@namespace|expression\s*\(|url\s*\(|javascript:|vbscript:|behavior\s*:|-moz-binding|<\/?style)/i',
            $css
        )) {
            self::fail('O CSS contém um recurso externo ou uma regra não permitida.');
        }

        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $css)) {
            self::fail('O CSS contém caracteres inválidos.');
        }

        return trim($css);
    }

    private static function cleanChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $parent->removeChild($child);

                continue;
            }

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                self::cleanChildren($child);

                while ($child->firstChild) {
                    $parent->insertBefore($child->firstChild, $child);
                }

                $parent->removeChild($child);

                continue;
            }

            self::cleanAttributes($child, $tag);
            self::cleanChildren($child);
        }
    }

    private static function cleanAttributes(DOMElement $element, string $tag): void
    {
        $global = ['class', 'id', 'role', 'style', 'title'];
        $byTag = [
            'a' => ['href', 'target'],
            'button' => ['type'],
            'img' => ['alt', 'height', 'loading', 'src', 'width'],
            'source' => ['src', 'type'],
            'video' => ['autoplay', 'controls', 'height', 'loop', 'muted', 'playsinline', 'poster', 'preload', 'src', 'width'],
        ];

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->name);
            $isData = str_starts_with($name, 'data-');
            $isAria = str_starts_with($name, 'aria-');
            $allowed = in_array($name, $global, true)
                || in_array($name, $byTag[$tag] ?? [], true)
                || $isData
                || $isAria;

            if (str_starts_with($name, 'on') || ! $allowed) {
                $element->removeAttribute($attribute->name);

                continue;
            }

            if (in_array($name, ['href', 'src', 'poster'], true)
                && ! self::safeUrl($attribute->value, $name === 'href')) {
                $element->removeAttribute($attribute->name);

                continue;
            }

            if ($name === 'style') {
                $style = self::cleanInlineStyle($attribute->value);

                if ($style === '') {
                    $element->removeAttribute('style');
                } else {
                    $element->setAttribute('style', $style);
                }
            }

            if ($name === 'target' && ! in_array($attribute->value, ['_blank', '_self'], true)) {
                $element->removeAttribute('target');
            }
        }

        if ($tag === 'a' && $element->getAttribute('target') === '_blank') {
            $element->setAttribute('rel', 'noopener noreferrer');
        }

        if ($tag === 'button') {
            $element->setAttribute('type', 'button');
        }
    }

    private static function cleanInlineStyle(string $style): string
    {
        if (preg_match('/(?:expression|url\s*\(|@import|javascript:|behavior|-moz-binding)/i', $style)) {
            return '';
        }

        $safe = [];

        foreach (explode(';', $style) as $declaration) {
            [$property, $value] = array_pad(explode(':', $declaration, 2), 2, null);
            $property = strtolower(trim((string) $property));
            $value = trim((string) $value);

            if (in_array($property, self::STYLE_PROPERTIES, true)
                && $value !== ''
                && strlen($value) <= 300
                && ! preg_match('/[{}<>]/', $value)) {
                $safe[] = $property.': '.$value;
            }
        }

        return implode('; ', $safe);
    }

    private static function safeUrl(string $url, bool $allowLink): bool
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($url === '' || str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return true;
        }

        if (! $allowLink) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https', 'mailto', 'tel'], true);
    }

    private static function fail(string $message): never
    {
        throw ValidationException::withMessages([
            'publication' => $message,
        ]);
    }
}
