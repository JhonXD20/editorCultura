<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conteudo extends Model
{
    use HasFactory;

    protected $table = 'conteudos';

    protected $fillable = [
        'pagina_id',
        'conteudo_tipo_componente',
        'conteudo_dados_conteudo',
        'conteudo_ordem_exibicao'
    ];

    // Converte automaticamente o JSON do banco para Array no PHP e vice-versa
    protected $casts = [
        'conteudo_dados_conteudo' => 'array',
    ];

    // Relacionamento N:1 (Vários conteúdos pertencem a uma página)
    public function pagina()
    {
        return $this->belongsTo(Pagina::class, 'pagina_id');
    }
}
