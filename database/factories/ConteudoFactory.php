<?php

namespace Database\Factories;

use App\Models\Conteudo;
use App\Models\Pagina;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Conteudo> */
class ConteudoFactory extends Factory
{
    protected $model = Conteudo::class;

    public function definition(): array
    {
        return [
            'pagina_id' => Pagina::factory(),
            'conteudo_tipo_componente' => 'texto',
            'conteudo_ordem_exibicao' => 1,
            'conteudo_dados_conteudo' => [
                'texto' => '<p>'.fake('pt_BR')->paragraph().'</p>',
            ],
        ];
    }
}
