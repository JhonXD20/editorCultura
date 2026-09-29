<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Totem extends Model
{
    protected $table = 'totens';
    protected $fillable = [
        'totem_nome_identificacao',
        'totem_localizacao',
        'totem_ping',
    ];
}
