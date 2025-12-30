# 🧾 Evidências de Teste (Test Evidence)

Este documento comprova o funcionamento do sistema e os testes realizados antes da entrega.

---

## 1. Testes Automatizados (PHPUnit)
Output real executado no comando:
`docker-compose exec app php artisan test`

```
   PASS  Tests\Feature\RedeemTest
  ✓ it redeems available code successfully                 7.82s  
  ✓ it returns 404 if code not found                       0.37s  
  ✓ idempotency same user gets 200 and no duplicate job    0.41s  
  ✓ conflict different user gets 409                       0.41s  

   PASS  Tests\Feature\WebhookValidationTest
  ✓ it accepts webhook with valid signature                1.08s  
  ✓ it rejects webhook with invalid signature              0.33s  
  ✓ it is idempotent for duplicate events                  0.36s  

  Tests:    7 passed (18 assertions)
  Duration: 12.86s
```
**(Status: Todos os 7 testes passaram com 100% de sucesso)**

---

## 2. Teste de API (cURL)

### Cenário: Resgate com Sucesso
Requisição:
```bash
curl -X POST http://localhost:8081/api/redeem \
-H "Content-Type: application/json" \
-d '{"code": "GFLOW-TEST-0001", "user": {"email": "tester@example.com"}}'
```

Resposta Esperada (200 OK):
```json
{
  "status": "redeemed",
  "code": "GFLOW-TEST-0001",
  "creator_id": "creator_123",
  "product_id": "product_abc",
  "webhook": {
    "status": "queued",
    "event_id": "evt_..."
  }
}
```

### Cenário: Idempotência (Mesmo Usuário)
Ao repetir a requisição acima, o sistema retorna **200 OK** (idêntico), mas **NÃO** dispara um novo Webhook (evitando duplicação de eventos).

---

## 3. Webhook Assíncrono (Logs)
Log do worker (`docker-compose logs queue`) mostrando o processamento do webhook:

```log
INFO  Processing jobs.
INFO  App\Jobs\DispatchWebhookJob ...................... RUNNING
INFO  Dispatching webhook to issuer {"event_id":"evt_...","target":"issuer-platform"}
INFO  Webhook processed successfully {"event_id":"evt_...","type":"giftcard.redeemed"}
INFO  App\Jobs\DispatchWebhookJob ...................... DONE 0.45s 
```

---

## 4. Interface Demo
Foi disponibilizada uma interface visual em `/demo` para facilitar a validação manual dos cenários pelo recrutador.

Acesse: `http://localhost:8081/demo`
