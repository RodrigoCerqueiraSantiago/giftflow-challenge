<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Storage;
/**
 * Classe responsável por gerenciar códigos de presente.
 *
 * Esta classe é responsável por gerar códigos de presente para os usuários.
 *
 * @package App\Repositories
 * @author Bruno Leite <bruno_leite@hotmail.com>
 */
class RedemptionRepository
{
    protected string $file = 'redemptions.json';

    /**
     * Retorna todos os resgates como array (code => dados do resgate)
     */
    public function all(): array
    {
        $content = Storage::disk('local')->get($this->file);

        return $content ? json_decode($content, true) : [];
    }

    /**
     * Busca o registro de resgate de um código específico
     */
    public function findByCode(string $code): ?array
    {
        $all = $this->all();

        return $all[$code] ?? null;
    }

    /**
     * Cria um novo registro de resgate
     */
    public function create(string $code, array $data): void
    {
        $all = $this->all();
        $all[$code] = $data;
        Storage::disk('local')->put($this->file, json_encode($all, JSON_PRETTY_PRINT));
    }
}
