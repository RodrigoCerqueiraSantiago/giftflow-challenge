<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Storage;

/**
 * Repositório de Gift Codes (Baseado em Arquivo)
 *
 * Como o requisito pedia "No Database", implementamos este repositório
 * para ler e escrever diretamente no arquivo gift_codes.json.
 * É simples, mas funciona perfeitamente para este desafio!
 */
class GiftCodeRepository
{
    protected string $file = 'gift_codes.json';

    /**
     * Retorna todos os códigos como array associativo (code => dados)
     */
    public function all(): array
    {
        $content = Storage::disk('local')->get($this->file);

        return $content ? json_decode($content, true) : [];
    }

    /**
     * Busca um código específico pelo seu valor
     */
    public function find(string $code): ?array
    {
        $all = $this->all();

        return $all[$code] ?? null;
    }

    /**
     * Atualiza os dados de um código específico
     */
    public function update(string $code, array $data): void
    {
        $all = $this->all();

        if (isset($all[$code])) {
            $all[$code] = array_merge($all[$code], $data);
            Storage::disk('local')->put($this->file, json_encode($all, JSON_PRETTY_PRINT));
        }
    }
}
