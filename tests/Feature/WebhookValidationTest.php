<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebhookValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::disk('local')->put('received_events.json', json_encode([], JSON_PRETTY_PRINT));
    }

    #[Test]
    public function it_accepts_webhook_with_valid_signature()
    {
        $payload = json_encode([
            'event_id' => 'evt_test_123',
            'type'     => 'giftcard.redeemed',
            'data'     => ['code' => 'TEST'],
            'sent_at'  => now()->toIso8601String(),
        ]);

        $secret = env('GIFTFLOW_WEBHOOK_SECRET');
        $signature = hash_hmac('sha256', $payload, $secret);

        // Use call to send raw body exactly as we signed it
        $response = $this->call(
            'POST', 
            '/webhook/issuer-platform', 
            [], 
            [], 
            [], 
            [
                'HTTP_X-GiftFlow-Signature' => $signature,
                'CONTENT_TYPE' => 'application/json'
            ], 
            $payload
        );

        $response->assertStatus(200)
            ->assertJson(['status' => 'ok']);
            
        // Verifica se salvou o event_id
        $received = json_decode(Storage::disk('local')->get('received_events.json'), true);
        $this->assertContains('evt_test_123', $received);
    }

    #[Test]
    public function it_rejects_webhook_with_invalid_signature()
    {
        $payload = json_encode([
            'event_id' => 'evt_invalid_sig',
            'type'     => 'giftcard.redeemed',
        ]);

        $response = $this->withHeaders([
            'X-GiftFlow-Signature' => 'invalid_signature_hash',
            'Content-Type'         => 'application/json',
        ])->post('/webhook/issuer-platform', json_decode($payload, true));

        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid signature']);
    }

    #[Test]
    public function it_is_idempotent_for_duplicate_events()
    {
        $eventId = 'evt_duplicate_001';
        $payload = json_encode(['event_id' => $eventId, 'type' => 'test']);
        $secret = env('GIFTFLOW_WEBHOOK_SECRET');
        $signature = hash_hmac('sha256', $payload, $secret);

        // Primeiro envio
        $this->withHeaders(['X-GiftFlow-Signature' => $signature])
             ->postJson('/webhook/issuer-platform', json_decode($payload, true))
             ->assertStatus(200);

        // Segundo envio (mesmo event_id)
        $response = $this->withHeaders(['X-GiftFlow-Signature' => $signature])
             ->postJson('/webhook/issuer-platform', json_decode($payload, true));

        $response->assertStatus(200)
            ->assertJson(['status' => 'already processed']);
    }
}
