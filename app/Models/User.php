<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $versao_sessao
 * @property Carbon|null $data_nascimento
 * @property string $perfil
 * @property bool $ativo
 * @property bool $convite_pendente
 * @property Carbon|null $ultimo_acesso_em
 * @property bool $troca_senha_obrigatoria
 * @property Carbon|null $senha_temporaria_expira_em
 */
#[Fillable(['name', 'email', 'password', 'perfil', 'ativo', 'ultimo_acesso_em', 'data_nascimento'])]
#[Hidden(['password', 'remember_token', 'data_nascimento', 'senha_temporaria_expira_em'])]
class User extends Authenticatable
{
    protected $attributes = ['versao_sessao' => 1, 'ativo' => true, 'perfil' => 'editor'];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'ativo' => 'boolean',
            'convite_pendente' => 'boolean',
            'ultimo_acesso_em' => 'datetime',
            'versao_sessao' => 'integer',
            'data_nascimento' => 'date',
            'troca_senha_obrigatoria' => 'boolean',
            'senha_temporaria_expira_em' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->perfil === 'admin';
    }

    public function defaultPassword(): ?string
    {
        return $this->data_nascimento?->format('dmY');
    }

    public function canEditContent(): bool
    {
        return in_array($this->perfil, ['admin', 'editor'], true);
    }
}
