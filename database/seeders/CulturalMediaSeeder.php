<?php

namespace Database\Seeders;

use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CulturalMediaSeeder extends Seeder
{
    /** Existing collection; no remote download is required to recreate the database. */
    public const ASSETS = [
        'catedral' => [
            'source' => 'totem/imagens-conteudo/atrativos-turisticos/Atrativos Turísticos/Catedral_Dornil.JPG',
            'caption' => 'Catedral Nossa Senhora das Mercês e casario de Porto Nacional. Acervo do projeto; arquivo identificado como Catedral_Dornil.',
        ],
        'cenog' => [
            'source' => 'totem/imagens-conteudo/criacao_do_tocantins/Folder/Painel_CENOG.jpg',
            'caption' => 'Painel do acervo sobre a Casa do Estudante do Norte Goiano e a mobilização da juventude.',
        ],
        'rio' => [
            'source' => 'totem/imagens-conteudo/historia-de-porto/Beira_Rio_foto.JPG',
            'caption' => 'Paisagem da orla de Porto Nacional e do Rio Tocantins. Acervo do projeto.',
        ],
        'casarao' => [
            'source' => 'totem/imagens-conteudo/historia-de-porto/Casarao.jpg',
            'caption' => 'Arquitetura e memória urbana de Porto Nacional. Acervo do projeto.',
        ],
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        foreach (self::ASSETS as $key => $definition) {
            $source = public_path($definition['source']);
            $dimensions = is_file($source) ? @getimagesize($source) : false;
            if ($dimensions === false || $dimensions[0] < 1000) {
                throw new \RuntimeException('Mídia cultural ausente ou sem resolução suficiente: '.$source);
            }
            $path = self::path($key);
            if (! Storage::disk('public')->put($path, file_get_contents($source))) {
                throw new \RuntimeException('Não foi possível preparar a mídia: '.$path);
            }

            MediaAsset::updateOrCreate(['caminho' => $path], [
                'uuid' => MediaAsset::where('caminho', $path)->value('uuid') ?? (string) Str::uuid(),
                'enviado_por' => User::where('email', 'demo@editorcultura.test')->value('id'),
                'disco' => 'public', 'nome_original' => basename($source),
                'tipo_mime' => $dimensions['mime'], 'tamanho' => filesize($source),
                'hash_verificacao' => hash_file('sha256', $source), 'tipo' => 'image',
                'metadados' => ['width' => $dimensions[0], 'height' => $dimensions[1],
                    'caption' => $definition['caption'], 'source' => $definition['source']],
            ]);
        }
    }

    public static function path(string $key): string
    {
        return 'media/cultural/'.$key.'.jpg';
    }

    public static function url(string $key): string
    {
        return '/storage/'.self::path($key);
    }
}
