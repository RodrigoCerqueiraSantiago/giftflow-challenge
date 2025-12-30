<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use App\Jobs\DispatchWebhookJob;
use Tests\TestCase;

class RedeemTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock do sistema de arquivos para não sujar o ambiente real
        Storage::fake('local');

        // Cria dados iniciais para o teste
        $initialCodes = [
            'TEST-CODE-001' => [
                'status'     => 'available',
                'product_id' => 'prod_1',
                'creator_id' => 'creator_1',
            ],
            'TEST-CODE-USED' => [
                'status'     => 'redeemed',
                'product_id' => 'prod_2',
                'creator_id' => 'creator_2',
            ],
        ];

        Storage::disk('local')->put('gift_codes.json', json_encode($initialCodes));
        Storage::disk('local')->put('redemptions.json', json_encode([]));
    }

    public function test_it_redeems_available_code_successfully()
    {
        Queue::fake();

        $response = $this->postJson('/api/redeem', [
            'code' => 'TEST-CODE-001',
            'user' => ['email' => 'user@example.com']
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'redeemed',
                     'code'   => 'TEST-CODE-001',
                 ]);

        // Verifica se o código foi marcado como redeemed no arquivo
        $codes = json_decode(Storage::disk('local')->get('gift_codes.json'), true);
        $this->assertEquals('redeemed', $codes['TEST-CODE-001']['status']);

        // Verifica se o Job foi despachado
        Queue::assertPushed(DispatchWebhookJob::class, function ($job) {
            return $job->code === 'TEST-CODE-001' && $job->email === 'user@example.com';
        });
    }

    public function test_it_returns_404_if_code_not_found()
    {
        $response = $this->postJson('/api/redeem', [
            'code' => 'INVALID-CODE',
            'user' => ['email' => 'user@example.com']
        ]);

        $response->assertStatus(404);
    }

    public function test_idempotency_same_user_gets_200_and_no_duplicate_job()
    {
        Queue::fake();

        // 1. Simula que já foi resgatado pelo MESMO usuário
        // Precisamos popular o redemptions.json para simular o estado passado
        $redemptions = [
            'TEST-CODE-USED' => [
                'email'       => 'owner@example.com',
                'event_id'    => 'evt_existing_123',
                'redeemed_at' => now()->subHour()->toIso8601String(),
            ]
        ];
        Storage::disk('local')->put('redemptions.json', json_encode($redemptions));

        // Tenta resgatar o código que JÁ é 'redeemed' (setup inicial) e pertence ao user
        $response = $this->postJson('/api/redeem', [
            'code' => 'TEST-CODE-USED',
            'user' => ['email' => 'owner@example.com'] // Mesmo email
        ]);

        // Esperado: 200 OK (Idempotência)
        $response->assertStatus(200)
                 ->assertJson([
                     'status'  => 'redeemed',
                     'webhook' => ['event_id' => 'evt_existing_123']
                 ]);

        // Esperado: NÃO despachar novo Job
        Queue::assertNothingPushed();
    }

    public function test_conflict_different_user_gets_409()
    {
        // 1. Simula resgate por User A
        $redemptions = [
            'TEST-CODE-USED' => [
                'email'       => 'userA@example.com',
                'event_id'    => 'evt_1',
                'redeemed_at' => now()->toIso8601String(),
            ]
        ];
        Storage::disk('local')->put('redemptions.json', json_encode($redemptions));

        // 2. Tenta resgatar com User B
        $response = $this->postJson('/api/redeem', [
            'code' => 'TEST-CODE-USED',
            'user' => ['email' => 'userB@example.com'] // Outro email
        ]);

        $response->assertStatus(409)
                 ->assertJson(['error' => 'Code already redeemed by another user']);
    }
}
