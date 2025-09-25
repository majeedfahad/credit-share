<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Device;
use App\Models\Card;
use App\Models\ViewToken;

class InitSeeder extends Seeder
{
    public function run(): void
    {
        // Create a card
        $card = Card::create([
            'name' => 'Visa - Apple Pay',
            'last4' => '2568',
            'type' => 'visa-applepay',
            'current_balance' => 1000.00,
            'currency' => 'SAR',
        ]);
        echo "\nCard created: ID={$card->id}, last4={$card->last4}\n";

        // Device (for your iPhone shortcut)
        $api = bin2hex(random_bytes(40));
        Device::create(['name' => 'iPhone-Abdul', 'api_token' => $api, 'device_type' => 'iphone', 'is_active' => true]);
        echo "\n=== DEVICE API TOKEN ===\n$api\n";

        // View token for Wife (bound to card)
        $wifeToken = bin2hex(random_bytes(64));
        ViewToken::create(['name' => 'Wife - Visa', 'token' => $wifeToken, 'card_id' => $card->id, 'is_active' => true]);
        echo "\n=== WIFE VIEW LINK ===\n/family/view/{$card->id}?token=$wifeToken\n";

        // Your view token
        $youToken = bin2hex(random_bytes(64));
        ViewToken::create(['name' => 'Owner - Visa', 'token' => $youToken, 'card_id' => $card->id, 'is_active' => true]);
        echo "\n=== OWNER VIEW LINK ===\n/family/view/{$card->id}?token=$youToken\n\n";
    }
}
