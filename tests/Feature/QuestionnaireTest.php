<?php

namespace Tests\Feature;

use App\Models\DeviceCredential;
use App\Models\Pagina;
use App\Models\Totem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class QuestionnaireTest extends TestCase
{
    use RefreshDatabase;

    private function device(bool $enabled = true): Totem
    {
        $totem = Totem::factory()->create(['questionario_ativo' => $enabled]);
        DeviceCredential::create(['totem_id' => $totem->id, 'hash_token' => hash('sha256', 'survey-token')]);
        $this->withToken('survey-token');

        return $totem;
    }

    private function payload(): array
    {
        return ['uuid' => (string) Str::uuid(), 'version' => '1', 'participate' => true,
            'occurred_at' => now()->subHour()->toIso8601String(), 'answers' => ['q1' => '5', 'q2' => 'na']];
    }

    public function test_questionnaire_is_disabled_by_default_and_requires_a_paired_device(): void
    {
        $this->postJson(route('device.questionnaire'), $this->payload())->assertUnauthorized();
        $totem = $this->device(false);
        $this->postJson(route('device.questionnaire'), $this->payload())->assertForbidden();
        $page = Pagina::factory()->published()->create();
        $this->withoutVite()->get(route('totem.exibir', $page))->assertOk()->assertDontSee('id="openQuestionnaire"', false);
        $totem->update(['questionario_ativo' => true]);
        $this->get(route('totem.exibir', $page))->assertOk()->assertSee('id="openQuestionnaire"', false);
    }

    public function test_response_is_idempotent_and_does_not_store_supplied_personal_data(): void
    {
        $totem = $this->device();
        $payload = $this->payload() + ['name' => 'Must not persist', 'email' => 'private@example.test'];
        $this->postJson(route('device.questionnaire'), $payload)->assertCreated()->assertJsonPath('acknowledged', $payload['uuid']);
        $this->postJson(route('device.questionnaire'), $payload)->assertOk();
        $this->assertDatabaseCount('respostas_questionario', 1);
        $this->assertDatabaseHas('respostas_questionario', ['totem_id' => $totem->id, 'q1' => 5, 'q2' => 0, 'q3' => null, 'versao_instrumento' => '1']);
        $stored = DB::table('respostas_questionario')->first();
        $this->assertObjectNotHasProperty('email', $stored);
        $this->assertObjectNotHasProperty('endereco_ip', $stored);
    }

    public function test_participation_answers_and_instrument_version_are_validated(): void
    {
        $this->device();
        $payload = $this->payload();
        foreach ([['participate' => false], ['version' => '99'], ['answers' => []], ['answers' => ['q1' => 6]], ['answers' => ['name' => 'Private']]] as $invalid) {
            $this->postJson(route('device.questionnaire'), array_replace($payload, $invalid))->assertUnprocessable();
        }
        $this->assertDatabaseCount('respostas_questionario', 0);
    }

    public function test_only_admin_sees_aggregate_results_and_can_enable_collection(): void
    {
        $totem = $this->device();
        $this->postJson(route('device.questionnaire'), $this->payload())->assertCreated();
        $this->withoutVite()->actingAs(User::factory()->create(['perfil' => 'editor']))->get(route('questionnaires.index'))->assertForbidden();
        $this->put(route('admin.totens.update', $totem), ['totem_nome_identificacao' => 'Survey', 'questionario_ativo' => false])->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('questionnaires.index'))->assertOk()->assertViewHas('total', 1)
            ->assertViewHas('results', fn ($rows) => $rows[0]['distribution'][5] === 1 && $rows[1]['distribution'][0] === 1 && $rows[2]['distribution']['skip'] === 1);
        $this->put(route('admin.totens.update', $totem), ['totem_nome_identificacao' => 'Survey', 'questionario_ativo' => false])->assertRedirect();
        $this->assertFalse($totem->fresh()->questionario_ativo);
    }
}
