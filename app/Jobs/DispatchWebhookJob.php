<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DispatchWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $email,
        public string $creatorId,
        public string $productId,
        public string $eventId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $payload = [
            'event_id' => $this->eventId,
            'type'     => 'giftcard.redeemed',
            'data'     => [
                'code'       => $this->code,
                'email'      => $this->email,
                'creator_id' => $this->creatorId,
                'product_id' => $this->productId,
            ],
            'sent_at'  => now()->utc()->toIso8601String(),
        ];

        // Precisamos transformar o payload em string crua para gerar a assinatura HMAC correta.
        // Se mudarmos um espaço aqui, a assinatura falha lá na ponta!
        $body = json_encode($payload);

        $secret = env('GIFTFLOW_WEBHOOK_SECRET');
        $signature = hash_hmac('sha256', $body, $secret);

        // BONUS: Structured Logging para monitoramento
        Log::info('Dispatching webhook to issuer', [
            'event_id' => $this->eventId,
            'target'   => 'issuer-platform',
            'code'     => $this->code,
            'context'  => 'redeem_flow'
        ]);

        // Dispara o POST para o endpoint simulado (mock)
        // Incluindo headers de segurança e tipo de conteúdo.
        Http::withHeaders([
            'X-GiftFlow-Signature' => $signature,
            'Content-Type'         => 'application/json',
            'Accept'               => 'application/json',
        ])
        ->withBody($body, 'application/json')
        ->post(config('services.giftflow.webhook_url', 'http://localhost:8000/webhook/issuer-platform'));
    }
}
