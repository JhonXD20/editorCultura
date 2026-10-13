<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pagina;
use App\Models\Conteudo;

class TotemInicialSeeder extends Seeder
{
    public function run()
    {
        // 1. Cria a Página Inicial FORÇANDO O ID 1
        $home = Pagina::updateOrCreate(
            ['id' => 1], // A âncora: garante que a Home será sempre a página 1
            [
                'pagina_titulo' => 'Tela Inicial do Totem',
                'pagina_tipo_layout' => 'home',
                'pagina_ativo' => true,
            ]
        );

        // 2. Cria a Página de História FORÇANDO O ID 2
        $historia = Pagina::updateOrCreate(
            ['id' => 2],
            [
                'pagina_titulo' => 'História de Porto Nacional',
                'pagina_tipo_layout' => 'padrao',
                'pagina_ativo' => true,
            ]
        );

        // Limpeza de segurança: Apaga os conteúdos destas duas páginas 
        // para não duplicar botões se rodar o comando --seed mais de uma vez.
        Conteudo::whereIn('pagina_id', [$home->id, $historia->id])->delete();

        // 3. Cria o Botão na Página Inicial
        Conteudo::create([
            'pagina_id' => $home->id,
            'conteudo_tipo_componente' => 'botao_navegacao',
            'conteudo_ordem_exibicao' => 1,
            'conteudo_dados_conteudo' => [
                'titulo' => 'HISTÓRIA DE<br>PORTO NACIONAL',
                'icone' => 'totem/img/botoes/BTN_Historia.png',
                'pagina_destino_id' => $historia->id,
                // Opcional: Já deixa as proporções perfeitas na criação do botão
                'largura' => 260,
                'altura' => 140,
                'tamanho_icone' => 65,
                'formato_icone' => '50%',
                'cor_fundo' => '#ffffff'
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
