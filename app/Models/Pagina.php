<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pagina extends Model
{
    use HasFactory;

    protected $table = 'paginas';

    protected $fillable = [
        'pagina_titulo',
        'pagina_tipo_layout',
        'pagina_ativo',
        'grupo_slide',
        'ordem_slide',
        'editor_project_data',
        'published_html',
        'published_css',
        'editor_version',
        'published_at',
    ];

    protected $casts = [
        'pagina_ativo' => 'boolean',
        'editor_project_data' => 'array',
        'published_at' => 'datetime',
    ];

    // Relacionamento 1:N (Uma página possui vários conteúdos)
    public function conteudos()
    {
        return $this->hasMany(Conteudo::class, 'pagina_id')->orderBy('conteudo_ordem_exibicao', 'asc');
    }
}
