<?php

namespace Database\Factories;

use App\Models\Pagina;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Pagina> */
class PaginaFactory extends Factory
{
    protected $model = Pagina::class;

    public function definition(): array
    {
        return [
            'pagina_titulo' => fake('pt_BR')->unique()->sentence(3),
            'pagina_tipo_layout' => 'interna',
            'pagina_ativo' => true,
            'grupo_slide' => null,
            'ordem_slide' => 0,
        ];
    }

    public function home(): static
    {
        return $this->state(fn (): array => [
            'pagina_tipo_layout' => 'home',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'pagina_ativo' => false,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'pagina_html_publicado' => '<main data-cultura-responsive-version="2"><h1>Conteúdo publicado</h1></main>',
            'pagina_css_publicado' => 'h1 { color: #20252a; }',
            'pagina_publicada_em' => now(),
            'pagina_versao_editor' => 'grapesjs-0.23',
        ]);
    }
}
