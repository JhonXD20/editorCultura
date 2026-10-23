<?php

namespace Database\Seeders;

use App\Models\Pagina;
use App\Models\PairingCode;
use App\Models\Totem;
use App\Models\User;
use Illuminate\Database\Seeder;

class TotemSeeder extends Seeder
{
    public const UNITS = [
        ['Totem 01 – Entrada & Recepção (Hall Principal)', 'Hall Principal — Entrada & Recepção'],
        ['Totem 02 – Galeria Histórica (Pavilhão Central)', 'Pavilhão Central — Galeria Histórica'],
        ['Totem 03 – Sala Multimídia (Espaço Memória & Tradições)', 'Espaço Memória & Tradições — Sala Multimídia'],
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $home = Pagina::where('pagina_tipo_layout', 'home')->whereNotNull('pagina_publicada_em')->firstOrFail();
        foreach (self::UNITS as [$name, $location]) {
            $totem = Totem::firstOrCreate(['totem_nome_identificacao' => $name], [
                'totem_localizacao' => $location, 'pagina_inicial_id' => $home->id,
                'totem_orientacao' => 'landscape', 'totem_inatividade_segundos' => 120,
                'questionario_ativo' => false,
                // Monitoring reflects real heartbeats; no fabricated online activity.
                'totem_ping' => null,
            ]);
            if ($totem->credentials()->whereNull('revogado_em')->exists()) {
                continue;
            }
            $pairing = PairingCode::where('totem_id', $totem->id)->whereNull('usado_em')->where('expira_em', '>', now())->first();
            if ($pairing) {
                continue;
            }
            do {
                $code = (string) random_int(100000, 999999);
            } while (PairingCode::where('hash_codigo', hash('sha256', $code))->exists());

            PairingCode::create([
                'totem_id' => $totem->id, 'criado_por' => User::where('email', 'demo@editorcultura.test')->value('id'),
                'hash_codigo' => hash('sha256', $code), 'expira_em' => now()->addMinutes(10),
            ]);
            // The existing pairing endpoint issues the persistent hashed device token.
            $this->command?->line($name.' | Pareamento (10 min): '.url('/parear/'.$code));
        }
        $this->command?->line('Pareamento permanente por conta administradora: '.url('/pairing'));
    }
}
