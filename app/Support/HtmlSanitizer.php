<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

final class HtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'a', 'b', 'blockquote', 'br', 'div', 'em', 'h1', 'h2', 'h3',
        'h4', 'h5', 'h6', 'hr', 'i', 'li', 'ol', 'p', 's', 'span',
        'strike', 'strong', 'sub', 'sup', 'table', 'tbody', 'td', 'th',
        'thead', 'tr', 'u', 'ul',
    ];

    private const DROP_WITH_CONTENT = [
        'base', 'button', 'embed', 'form', 'iframe', 'input', 'link',
        'math', 'meta', 'object', 'option', 'script', 'select', 'style',
        'svg', 'textarea',
    ];

    private const STYLE_PROPERTIES = [
        'background-color', 'color', 'font-family', 'font-size',
        'font-style', 'font-weight', 'letter-spacing', 'line-height',
        'list-style-type', 'margin-left', 'margin-right', 'padding-left',
        'text-align', 'text-decoration',
    ];

    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="sanitizer-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('sanitizer-root');

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

    private static function cleanChildren(DOMNode $parent): void
    {
        $children = iterator_to_array($parent->childNodes);

        foreach ($children as $child) {
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
        $allowed = ['class', 'style', 'title'];

        if ($tag === 'a') {
            $allowed = [...$allowed, 'href', 'target'];
        }

        if (in_array($tag, ['td', 'th'], true)) {
            $allowed = [...$allowed, 'colspan', 'rowspan'];
        }

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->name);

            if (str_starts_with($name, 'on') || ! in_array($name, $allowed, true)) {
                $element->removeAttribute($attribute->name);

                continue;
            }

            if ($name === 'href' && ! self::isSafeUrl($attribute->value)) {
                $element->removeAttribute($attribute->name);
            }

            if ($name === 'style') {
                $style = self::cleanStyle($attribute->value);

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
    }

    private static function isSafeUrl(string $url): bool
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($url === '' || str_starts_with($url, '#') || str_starts_with($url, '/')) {
            return true;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https', 'mailto', 'tel'], true);
    }

    private static function cleanStyle(string $style): string
    {
        if (preg_match('/(?:expression|url\s*\(|@import|javascript:|behavior|-moz-binding)/i', $style)) {
            return '';
        }

        $safe = [];

        foreach (explode(';', $style) as $declaration) {
            [$property, $value] = array_pad(explode(':', $declaration, 2), 2, null);
            $property = strtolower(trim((string) $property));
            $value = trim((string) $value);

            if (
                in_array($property, self::STYLE_PROPERTIES, true)
                && $value !== ''
                && strlen($value) <= 200
                && ! preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $value)
            ) {
                $safe[] = $property.': '.$value;
            }
        }

        return implode('; ', $safe);
    }
}
