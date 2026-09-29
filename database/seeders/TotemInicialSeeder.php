<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pagina;
use App\Models\Conteudo;

class TotemInicialSeeder extends Seeder
{
    public function run()
    {
        // 1. Cria a Página Inicial
        $home = Pagina::create([
            'pagina_titulo' => 'Tela Inicial do Totem',
            'pagina_tipo_layout' => 'home',
            'pagina_ativo' => true,
        ]);

        // 2. Cria a Página de História
        $historia = Pagina::create([
            'pagina_titulo' => 'História de Porto Nacional',
            'pagina_tipo_layout' => 'padrao',
            'pagina_ativo' => true,
        ]);

        // 3. Cria o Botão na Página Inicial
        Conteudo::create([
            'pagina_id' => $home->id,
            'conteudo_tipo_componente' => 'botao_navegacao',
            'conteudo_ordem_exibicao' => 1,
            'conteudo_dados_conteudo' => [
                'titulo' => 'HISTÓRIA DE<br>PORTO NACIONAL',
                'icone' => 'totem/img/botoes/BTN_Historia.png',
                'pagina_destino_id' => $historia->id
            ]
        ]);

        // 4. Cria o Texto no miolo da página de História
        Conteudo::create([
            'pagina_id' => $historia->id,
            'conteudo_tipo_componente' => 'texto',
            'conteudo_ordem_exibicao' => 1,
            'conteudo_dados_conteudo' => [
                'texto' => 'Porto Nacional é um município brasileiro do estado do Tocantins. É o pólo regional da zona de Bacia do Médio Tocantins...'
            ]
        ]);
    }
}
