<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Secao extends Model
{
    protected $table = 'secoes';

    protected $fillable = ['nome'];

    protected $casts = ['versao' => 'integer'];

    public function paginas(): HasMany
    {
        return $this->hasMany(Pagina::class)->orderBy('ordem_slide')->orderBy('id');
    }
}
