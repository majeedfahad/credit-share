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
        // Device (for your iPhone shortcut)
        $api = bin2hex(random_bytes(40));
        Device::create(['name' => 'iPhone-Abdul', 'api_token' => $api, 'device_type' => 'iphone', 'is_active' => true]);
        echo "\n=== DEVICE API TOKEN ===\n$api\n";
    }
}
