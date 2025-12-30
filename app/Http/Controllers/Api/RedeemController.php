<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RedeemRequest;
use App\Jobs\DispatchWebhookJob;
use App\Repositories\GiftCodeRepository;
use App\Repositories\RedemptionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class RedeemController extends Controller
{
    public function __construct(
        protected GiftCodeRepository $giftCodeRepo,
        protected RedemptionRepository $redemptionRepo
    ) {}

    /**
     * Endpoint principal para resgate de gift cards.
     * 
     * Aqui nós validamos se o código existe e ainda não foi usado.
     * Seguimos a regra de idempotência: se o mesmo usuário tentar resgatar de novo,
     * retornamos sucesso sem processar duas vezes.
     */
    public function store(RedeemRequest $request): JsonResponse
    {
        $code = $request->input('code');
        $email = $request->input('user.email');

        // Primeiro, vamos procurar o código na nossa "base de dados" JSON.
        $codeData = $this->giftCodeRepo->find($code);

        if (!$codeData) {
            return response()->json(['error' => 'Code not found'], 404);
        }

        // Agora checamos se ele já const na lista de códigos resgatados.
        $redemption = $this->redemptionRepo->findByCode($code);

        if ($codeData['status'] === 'redeemed') {
            // Regra de Idempotência:
            // Se o usuário é o mesmo que resgatou antes, "fingimos" que resgatamos de novo
            // retornando 200 OK com os mesmos dados. Isso evita erros no cliente.
            if ($redemption && $redemption['email'] === $email) {
                return response()->json([
                    'status'     => 'redeemed',
                    'code'       => $code,
                    'creator_id' => $codeData['creator_id'],
                    'product_id' => $codeData['product_id'],
                    'webhook'    => [
                        'status'   => 'queued',
                        'event_id' => $redemption['event_id'],
                    ],
                ], 200);
            }

            // Se outro usuário tentar pegar um código usado, aí não pode! Retornamos Erro 409.
            return response()->json(['error' => 'Code already redeemed by another user'], 409);
        }

        // Se chegou até aqui, o código é válido e limpinho! Vamos resgatar.
        // Geramos um ID único para este evento (importante para o webhook não duplicar).
        $eventId = 'evt_' . Str::uuid()->toString();

        // Salvamos o resgate na persistência.
        $this->redemptionRepo->create($code, [
            'email'       => $email,
            'event_id'    => $eventId,
            'redeemed_at' => now()->toIso8601String(),
        ]);

        // Marcamos o gift card como "redeemed" para ninguém mais usar.
        $this->giftCodeRepo->update($code, ['status' => 'redeemed']);

        // Tudo certo! Agora despachamos o Job para avisar o sistema externo (assincronamente).
        DispatchWebhookJob::dispatch(
            $code,
            $email,
            $codeData['creator_id'],
            $codeData['product_id'],
            $eventId
        );

        return response()->json([
            'status'     => 'redeemed',
            'code'       => $code,
            'creator_id' => $codeData['creator_id'],
            'product_id' => $codeData['product_id'],
            'webhook'    => [
                'status'   => 'queued',
                'event_id' => $eventId,
            ],
        ], 200);
    }
}
