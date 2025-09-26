<?php
namespace Database\Seeders;

use App\Models\Card;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class FamilyUserSeeder extends Seeder
{
    public function run(): void
    {
//        $this->createCard("غيداء", 2568, '0550033370');
        $this->createCard('عبدالمجيد', 3091, '0540366642');
    }

    private function createCard($userName, $last4, $phone)
    {
        $card = Card::create([
            'name' => "بطاقة $userName",
            'last4' => $last4,
            'type' => 'visa-applepay',
            'current_balance' => 0,
            'currency' => 'SAR',
        ]);

        $plainPassword = bin2hex(random_bytes(5));

        $user = \App\Models\User::create([
            'name' => $userName,
            'email' => 'altaweel.abdulmajeed@gmail.com',
            'phone' => $phone,
            'password' => Hash::make($plainPassword),
            'default_card_id' => $card->id,   // <-- هنا
        ]);

        $this->command->info("phone: {$phone}");
        $this->command->info("password: {$plainPassword}");
        $this->command->info("card_id: {$card->id}");
    }
}
