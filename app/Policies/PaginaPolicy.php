<?php

namespace App\Policies;

use App\Models\Pagina;
use App\Models\User;

class PaginaPolicy
{
    public function update(User $user, Pagina $pagina): bool
    {
        return $user->ativo && $user->canEditContent();
    }

    public function delete(User $user, Pagina $pagina): bool
    {
        return $user->ativo && $user->isAdmin();
    }

    public function publish(User $user, Pagina $pagina): bool
    {
        return $user->ativo && $user->isAdmin();
    }
}
