# GiftFlow - Gift Card Redemption Service

Desafio técnico implementado em **Laravel 11** com **Docker**.
O projeto simula um sistema de resgate de Gift Cards com disparos de Webhooks assíncronos e garantia de idempotência.

## 🚀 Como Rodar o Projeto (Docker)

Certifique-se de ter **Docker** e **Docker Compose** instalados.

1. **Clone o repositório e configure o ambiente:**

```bash
# Copie o arquivo de exemplo
cp .env.example .env

# Suba os containers (Nginx, App, MySQL, Redis, Queue)
docker-compose up -d --build
```

2. **Instale dependências e configure a aplicação:**

```bash
# Instala pacotes PHP
docker-compose exec app composer install

# Gera a chave da aplicação
docker-compose exec app php artisan key:generate

# Cria tabelas necessárias (jobs, failed_jobs)
docker-compose exec app php artisan migrate --force
```

3. **Popule os dados iniciais (Seed):**

O sistema utiliza armazenamento em arquivo JSON para os códigos de presente (conforme permitido nos requisitos), mas utiliza Redis para filas.

```bash
# Cria os códigos iniciais em storage/app/gift_codes.json
docker-compose exec app php artisan giftflow:seed
```

---

## 🧪 Como Rodar os Testes

Para garantir que tudo está funcionando (Resgate, Idempotência, Webhooks):

```bash
docker-compose exec app php artisan test
```

---

## 🏗 Arquitetura e Decisões Técnicas

### Persistência ("No Database Required")
Embora o ambiente Docker possua um container MySQL (para robustez e tabelas de sistema do Laravel como `failed_jobs`), a persistência de negcio (**Gift Codes** e **Redemptions**) foi implementada utilizando **arquivos JSON** (`storage/app/*.json`), acessados através do padrão **Repository**.
- `GiftCodeRepository`: Lê e escreve em `gift_codes.json`.
- `RedemptionRepository`: Lê e escreve em `redemptions.json`.

Essa escolha cumpre o requisito de "minimal persistence" sem necessidade de complexidade de banco de dados para o domínio principal.

### Idempotência
A idempotência é garantida no `RedeemController`. Antes de processar um resgate, verificamos se o código já foi marcado como `redeemed`.
- Se foi resgatado pelo **mesmo e-mail**: Retornamos `200 OK` com os dados originais (idempotente).
- Se foi resgatado por **outro e-mail**: Retornamos `409 Conflict`.

### Webhook & Filas (Queue)
Ao resgatar com sucesso, um Job (`DispatchWebhookJob`) é despachado para a fila.
- Configuramos o container `redis` para gerenciar a fila.
- Um container dedicado `queue` (`php artisan queue:work`) processa os jobs em segundo plano.
- O webhook inclui um header `X-GiftFlow-Signature` (HMAC SHA256) para segurança.

### Docker Environment
O ambiente foi configurado simulando produção:
- **Nginx**: Servidor web atuando como proxy reverso.
- **App (PHP-FPM)**: Aplicação principal.
- **Queue**: Worker dedicado para processamento assíncrono.
- **Redis**: Driver de fila e cache.
- **MySQL**: Disponível para necessidades do framework (opcional para o domínio).

---

## 📌 Endpoints

### 1. Resgatar Código
`POST /api/redeem`

Payload:
```json
{
  "code": "GFLOW-TEST-0001",
  "user": {
    "email": "teste@example.com"
  }
}
```

### 2. Mock Webhook Receiver
`POST /webhook/issuer-platform`
Endpoint interno usado para validar o recebimento do webhook.

### 3. Interface de Demo (Bônus Visual)
`GET /demo`
Criamos uma interface visual simples para facilitar o teste manual da API sem necessidade de Postman.
Acesse `http://localhost:8081/demo` no seu navegador.
Lá você pode:
- Testar resgate com sucesso.
- Ver o comportamento de erro (404, 409).
- Visualizar os logs de requisição/resposta em tempo real.
- Alterar o e-mail para validar regras de concorrência.

---
---

## ✅ Checklist de Entregas (Requisitos do Teste)

| Requisito | Status | Implementação |
| :--- | :---: | :--- |
| **Seed Codes** | ✅ | Comando `giftflow:seed` cria códigos iniciais. |
| **Redeem API** | ✅ | `POST /api/redeem` (Validação, Sucesso, Erro 404/409). |
| **Webhook Dispatcher** | ✅ | Job Assíncrono (`DispatchWebhookJob`) via Redis. |
| **Idempotency** | ✅ | Tratamento de concorrência e respostas cacheadas para mesmo usuário. |
| **Webhook Signing** | ✅ | Assinatura HMAC SHA256 (`X-GiftFlow-Signature`). |
| **Docker** | ✅ | Setup completo com Nginx, PHP-FPM, MySQL e Redis. |
| **Testes** | ✅ | Cobertura de Feature para Fluxo de Resgate, Webhook e Segurança. |

---
Desenvolvido por Rodrigo Santiago como parte do Teste Técnico.
