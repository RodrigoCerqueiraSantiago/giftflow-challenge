<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Endpoint que recebe o webhook do job DispatchWebhookJob
     * Valida assinatura e garante idempotência
     */
    public function handle(Request $request): JsonResponse
    {
        // Pega o corpo raw da requisição (importante para validar assinatura)
        $body = $request->getContent();

        // Calcula a assinatura esperada
        $secret = env('GIFTFLOW_WEBHOOK_SECRET');
        $expectedSignature = hash_hmac('sha256', $body, $secret);

        // Pega a assinatura enviada
        $receivedSignature = $request->header('X-GiftFlow-Signature');

        // Validação da assinatura
        if (!hash_equals($expectedSignature, $receivedSignature)) {
            Log::warning('Webhook signature invalid', [
                'received' => $receivedSignature,
                'expected' => $expectedSignature
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Decodifica o payload
        $payload = json_decode($body, true);

        if (!$payload || !isset($payload['event_id'])) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $eventId = $payload['event_id'];

        // Arquivo de eventos recebidos (para idempotência)
        $file = 'received_events.json';
        $received = Storage::disk('local')->exists($file)
            ? json_decode(Storage::disk('local')->get($file), true)
            : [];

        // Se o event_id já foi processado, responde 200 mas não faz nada extra
        if (in_array($eventId, $received)) {
            Log::info('Duplicate webhook event ignored', ['event_id' => $eventId]);
            return response()->json(['status' => 'already processed'], 200);
        }

        // Registra o novo event_id
        $received[] = $eventId;
        Storage::disk('local')->put($file, json_encode($received, JSON_PRETTY_PRINT));

        Log::info('Webhook processed successfully', [
            'event_id' => $eventId,
            'type' => $payload['type'] ?? 'unknown'
        ]);

        return response()->json(['status' => 'ok'], 200);
    }
}
