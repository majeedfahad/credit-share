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
        $card = Card::first(); // أو انشئ بطاقة إذا ما فيه
        if (!$card) {
            $card = Card::create([
                'name' => 'Visa - Apple Pay',
                'last4' => '2568',
                'type' => 'visa-applepay',
                'current_balance' => 0,
                'currency' => 'SAR',
            ]);
        }

        $phone = '0550033370';
        $plainPassword = bin2hex(random_bytes(5));

        $user = \App\Models\User::create([
            'name' => 'Family Member',
            'email' => 'aabdulmajeed16@gmail.com',
            'phone' => $phone,
            'password' => Hash::make($plainPassword),
            'default_card_id' => $card->id,   // <-- هنا
        ]);

        $this->command->info("phone: {$phone}");
        $this->command->info("password: {$plainPassword}");
        $this->command->info("card_id: {$card->id}");
    }
}
