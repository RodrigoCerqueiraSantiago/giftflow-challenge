<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GiftflowSeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'giftflow:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed initial gift codes for GiftFlow challenge';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $codesFile = 'gift_codes.json';
        $redemptionsFile = 'redemptions.json';
        $receivedEventsFile = 'received_events.json';

        // Evita sobrescrever se já existir (comportamento idempotente)
        if (Storage::disk('local')->exists($codesFile)) {
            $this->info('Gift codes already seeded. Skipping...');
            return self::SUCCESS;
        }

        $initialCodes = [
            'GFLOW-TEST-0001' => [
                'status'     => 'available',
                'product_id' => 'product_abc',
                'creator_id' => 'creator_123',
            ],
            'GFLOW-TEST-0002' => [
                'status'     => 'available',
                'product_id' => 'product_def',
                'creator_id' => 'creator_456',
            ],
            'GFLOW-USED-0003' => [
                'status'     => 'redeemed',
                'product_id' => 'product_ghi',
                'creator_id' => 'creator_789',
            ],
        ];

        // Cria os arquivos vazios/iniciais
        Storage::disk('local')->put($codesFile, json_encode($initialCodes, JSON_PRETTY_PRINT));
        Storage::disk('local')->put($redemptionsFile, json_encode([], JSON_PRETTY_PRINT));
        Storage::disk('local')->put($receivedEventsFile, json_encode([], JSON_PRETTY_PRINT));

        $this->info('GiftFlow initial codes seeded successfully!');
        $this->info("Files created: storage/app/{$codesFile}, {$redemptionsFile}, {$receivedEventsFile}");

        return self::SUCCESS;
    }
}
