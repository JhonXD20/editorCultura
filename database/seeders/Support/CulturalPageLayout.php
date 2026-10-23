<?php

namespace Database\Seeders\Support;

use Database\Seeders\CulturalMediaSeeder;

/** One component tree and one style sheet feed both GrapesJS and the player. */
final class CulturalPageLayout
{
    public static function article(array $page, string $section, int $position): array
    {
        $header = self::node('header', [
            self::text('p', $section.' · '.$position.' / 3', 'ts-kicker'),
            self::text('h1', $page['title']),
            self::text('p', $page['summary'], 'ts-summary'),
        ], ['class' => 'ts-header']);
        $body = self::node('div', [
            self::node('figure', [
                self::node('img', [], ['src' => CulturalMediaSeeder::url($page['image']),
                    'alt' => CulturalMediaSeeder::ASSETS[$page['image']]['caption']]),
                self::text('figcaption', CulturalMediaSeeder::ASSETS[$page['image']]['caption']),
            ], ['class' => 'ts-figure']),
            self::node('div', array_map(fn (array $block): array => self::node('section', [
                self::text('h2', $block[0]), self::text('p', $block[1]),
            ]), $page['blocks']), ['class' => 'ts-grid']),
        ], ['class' => 'ts-body']);
        $footer = self::node('footer', [
            self::text('p', $page['prompt'], 'ts-prompt'),
            self::text('p', 'Referências: '.implode(' · ', array_column($page['sources'], 0)), 'ts-source'),
        ], ['class' => 'ts-footer']);

        return self::document([$header, $body, $footer], $page['title']);
    }

    public static function home(array $sections): array
    {
        $header = self::node('header', [
            self::text('p', 'MEMORIAL · PORTO NACIONAL', 'ts-kicker'),
            self::text('h1', 'Tocantins: caminhos de memória'),
            self::text('p', 'Explore a formação do Estado e o patrimônio vivo de suas comunidades. Escolha um percurso para começar.', 'ts-summary'),
        ], ['class' => 'ts-header']);
        $body = self::node('div', [
            self::node('nav', array_map(fn (array $section): array => self::node('button', [
                self::text('span', 'PERCURSO · 3 PÁGINAS', 'ts-kicker'),
                self::text('span', $section['name'], 'ts-card-title'),
                self::text('span', 'Explorar percurso →', 'ts-card-action'),
            ], ['type' => 'button', 'class' => 'ts-card', 'data-cultura-navigation' => '1',
                'data-section-id' => (string) $section['id']]), $sections), ['class' => 'ts-routes', 'aria-label' => 'Percursos culturais']),
            self::node('figure', [
                self::node('img', [], ['src' => CulturalMediaSeeder::url('rio'), 'alt' => CulturalMediaSeeder::ASSETS['rio']['caption']]),
                self::text('figcaption', CulturalMediaSeeder::ASSETS['rio']['caption']),
            ], ['class' => 'ts-figure']),
        ], ['class' => 'ts-body ts-home-body']);
        $footer = self::node('footer', [self::text('p', 'Toque em um percurso. Para continuar, use a navegação do player; para trocar de tema, volte ao início.', 'ts-prompt')], ['class' => 'ts-footer']);

        return self::document([$header, $body, $footer], 'Tocantins: caminhos de memória');
    }

    private static function document(array $children, string $title): array
    {
        $component = self::node('main', $children, ['class' => 'ts-page', 'data-cultura-responsive-version' => '2', 'data-cultura-layout' => 'fixed-screen']);
        $styles = self::styles();
        $css = '';
        foreach ($styles as $rule) {
            $declarations = '';
            foreach ($rule['style'] as $key => $value) {
                $declarations .= $key.':'.$value.';';
            }
            $block = implode(',', $rule['selectors']).'{'.$declarations.'}';
            $css .= isset($rule['mediaText']) ? '@media '.$rule['mediaText'].'{'.$block.'}' : $block;
        }

        return [
            'project' => ['pages' => [['id' => 'pagina-principal', 'name' => $title, 'component' => $component]],
                'styles' => $styles, 'assets' => array_map(fn (string $key): array => ['type' => 'image', 'src' => CulturalMediaSeeder::url($key)], array_keys(CulturalMediaSeeder::ASSETS))],
            'html' => self::html($component), 'css' => $css,
        ];
    }

    private static function node(string $tag, array $children = [], array $attributes = []): array
    {
        return ['tagName' => $tag, 'attributes' => $attributes, 'components' => $children];
    }

    private static function text(string $tag, string $text, string $class = ''): array
    {
        return ['tagName' => $tag, 'type' => 'text', 'attributes' => $class ? ['class' => $class] : [], 'content' => e($text)];
    }

    private static function html(array $node): string
    {
        $attributes = '';
        foreach ($node['attributes'] as $name => $value) {
            $attributes .= ' '.$name.'="'.e($value).'"';
        }
        $open = '<'.$node['tagName'].$attributes.'>';
        if ($node['tagName'] === 'img') {
            return $open;
        }

        return $open.($node['content'] ?? implode('', array_map(self::html(...), $node['components'] ?? []))).'</'.$node['tagName'].'>';
    }

    private static function styles(): array
    {
        $rules = [
            '.ts-page' => ['box-sizing' => 'border-box', 'width' => '1366px', 'height' => '768px', 'display' => 'grid', 'grid-template-rows' => 'auto minmax(0,1fr) auto', 'gap' => '18px', 'overflow' => 'hidden', 'padding' => '30px 40px', 'background' => '#f6f2e9', 'color' => '#20382f', 'font-family' => 'Arial, sans-serif'],
            '.ts-page *' => ['box-sizing' => 'border-box'],
            '.ts-page p' => ['margin' => '0'],
            '.ts-page h1' => ['font-size' => '38px', 'line-height' => '1.12', 'margin' => '8px 0 10px'],
            '.ts-kicker' => ['color' => '#77551c', 'font-size' => '13px', 'line-height' => '1.4', 'font-weight' => '700', 'letter-spacing' => '.1em', 'text-transform' => 'uppercase'],
            '.ts-summary' => ['font-size' => '20px', 'line-height' => '1.4'],
            '.ts-body' => ['display' => 'grid', 'grid-template-columns' => 'minmax(0,1fr) minmax(0,2fr)', 'gap' => '22px', 'min-height' => '0'],
            '.ts-figure' => ['display' => 'grid', 'grid-template-rows' => 'minmax(0,1fr) auto', 'gap' => '10px', 'min-height' => '0', 'margin' => '0', 'padding' => '12px', 'background' => '#fff', 'border-radius' => '14px'],
            '.ts-figure img' => ['display' => 'block', 'width' => '100%', 'height' => '100%', 'min-height' => '0', 'object-fit' => 'contain', 'background' => '#e9e3d8', 'border-radius' => '8px'],
            '.ts-figure figcaption' => ['font-size' => '14px', 'line-height' => '1.35', 'color' => '#536057'],
            '.ts-grid' => ['display' => 'grid', 'grid-template-rows' => 'repeat(3,minmax(0,1fr))', 'gap' => '12px', 'min-height' => '0'],
            '.ts-grid section' => ['padding' => '14px 18px', 'background' => '#fff', 'border-left' => '4px solid #b58a38', 'border-radius' => '12px'],
            '.ts-grid h2' => ['font-size' => '21px', 'line-height' => '1.2', 'margin' => '0 0 8px'],
            '.ts-grid p' => ['font-size' => '19px', 'line-height' => '1.4'],
            '.ts-footer' => ['display' => 'grid', 'gap' => '8px', 'color' => '#536057'],
            '.ts-prompt' => ['padding' => '10px 16px', 'border-left' => '4px solid #b58a38', 'background' => '#ece6d8', 'font-size' => '18px', 'line-height' => '1.3'],
            '.ts-source' => ['font-size' => '12px', 'line-height' => '1.3'],
            '.ts-home-body' => ['grid-template-columns' => 'minmax(0,3fr) minmax(0,2fr)'],
            '.ts-routes' => ['display' => 'grid', 'grid-template-rows' => 'repeat(2,minmax(0,1fr))', 'gap' => '20px', 'min-height' => '0'],
            '.ts-card' => ['display' => 'flex', 'flex-direction' => 'column', 'justify-content' => 'center', 'gap' => '16px', 'padding' => '28px', 'background' => '#204c3c', 'color' => '#fff', 'border' => '0', 'border-radius' => '16px', 'text-align' => 'left', 'font-size' => '19px', 'line-height' => '1.4'],
            '.ts-card .ts-kicker' => ['color' => '#f0cd80'],
            '.ts-card-title' => ['font-size' => '28px', 'font-weight' => '700'],
            '.ts-card-action' => ['font-weight' => '700', 'color' => '#f0cd80'],
            '.ts-card:focus-visible' => ['outline' => '4px solid #b58a38', 'outline-offset' => '4px'],
        ];
        $styles = [];
        foreach ($rules as $selector => $style) {
            $styles[] = ['selectors' => [$selector], 'style' => $style];
        }

        return $styles;
    }
}
