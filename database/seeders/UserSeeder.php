<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public const ACCOUNTS = [
        ['Administrador de Teste', 'demo@editorcultura.test', 'password', 'admin'],
        ['Helena Martins — Administração Institucional', 'helena.martins@totemsync.test', 'Memorial@2026', 'admin'],
        ['Rafael Nunes — Curadoria Histórica', 'rafael.nunes@totemsync.test', 'Curadoria@2026', 'editor'],
        ['Marina Alves — Patrimônio e Tradições', 'marina.alves@totemsync.test', 'Patrimonio@2026', 'editor'],
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        foreach (self::ACCOUNTS as [$name, $email, $password, $role]) {
            // Repeating db:seed must never reset a password changed through the CMS.
            if (User::where('email', $email)->exists()) {
                continue;
            }

            $user = new User;
            $user->forceFill([
                'name' => $name, 'email' => $email, 'password' => $password,
                'perfil' => $role, 'ativo' => true, 'email_verified_at' => now(),
                'convite_pendente' => false, 'troca_senha_obrigatoria' => false,
            ])->save();
        }
    }
}
