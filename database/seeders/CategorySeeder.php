<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\MerchantCategory;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ["name" => "food", "name_ar" => "طعام", "icon" => "🍔", "color" => "#F59E0B", "sort_order" => 1],
            ["name" => "grocery", "name_ar" => "بقالة", "icon" => "🛒", "color" => "#10B981", "sort_order" => 2],
            ["name" => "fuel", "name_ar" => "وقود", "icon" => "⛽", "color" => "#EF4444", "sort_order" => 3],
            ["name" => "shopping", "name_ar" => "تسوق", "icon" => "🛍️", "color" => "#8B5CF6", "sort_order" => 4],
            ["name" => "home", "name_ar" => "منزل", "icon" => "🏠", "color" => "#6366F1", "sort_order" => 5],
            ["name" => "health", "name_ar" => "صحة", "icon" => "💊", "color" => "#EC4899", "sort_order" => 6],
            ["name" => "transport", "name_ar" => "مواصلات", "icon" => "🚗", "color" => "#14B8A6", "sort_order" => 7],
            ["name" => "entertainment", "name_ar" => "ترفيه", "icon" => "🎮", "color" => "#F97316", "sort_order" => 8],
            ["name" => "tech", "name_ar" => "تقنية", "icon" => "📱", "color" => "#3B82F6", "sort_order" => 9],
            ["name" => "subscriptions", "name_ar" => "اشتراكات", "icon" => "📺", "color" => "#A855F7", "sort_order" => 10],
            ["name" => "transfer", "name_ar" => "تحويل", "icon" => "💸", "color" => "#22C55E", "sort_order" => 11],
            ["name" => "other", "name_ar" => "أخرى", "icon" => "📦", "color" => "#6B7280", "sort_order" => 99],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(["name" => $cat["name"]], $cat);
        }

        // Auto-classification patterns
        $patterns = [
            // Food
            "keeta" => "food", "hungerstation" => "food", "jahez" => "food", "the chefz" => "food",
            "mcdonald" => "food", "kfc" => "food", "starbucks" => "food", "dunkin" => "food",
            "herfy" => "food", "albaik" => "food", "kudu" => "food", "pizza" => "food",
            "burger" => "food", "shawarma" => "food", "coffee" => "food", "cafe" => "food",
            "restaurant" => "food", "مطعم" => "food", "قهوة" => "food", "مقهى" => "food",
            // Grocery
            "panda" => "grocery", "danube" => "grocery", "tamimi" => "grocery", "carrefour" => "grocery",
            "lulu" => "grocery", "farm" => "grocery", "othaim" => "grocery", "nesto" => "grocery",
            "bin dawood" => "grocery", "بقالة" => "grocery", "سوبرماركت" => "grocery",
            // Fuel
            "petromin" => "fuel", "aldrees" => "fuel", "naft" => "fuel", "sasco" => "fuel",
            "benzene" => "fuel", "بنزين" => "fuel", "محطة" => "fuel",
            // Shopping
            "noon" => "shopping", "amazon" => "shopping", "shein" => "shopping", "namshi" => "shopping",
            "centrepoint" => "shopping", "hm.com" => "shopping", "zara" => "shopping", "ikea" => "shopping",
            "jarir" => "shopping", "extra" => "shopping",
            // Health
            "pharmacy" => "health", "nahdi" => "health", "dawa" => "health", "hospital" => "health",
            "clinic" => "health", "صيدلية" => "health", "مستشفى" => "health",
            // Transport
            "uber" => "transport", "careem" => "transport", "jeeny" => "transport",
            // Tech
            "apple" => "tech", "stc" => "tech", "mobily" => "tech", "zain" => "tech",
            // Subscriptions
            "netflix" => "subscriptions", "spotify" => "subscriptions", "shahid" => "subscriptions",
            "youtube" => "subscriptions", "icloud" => "subscriptions",
            // Transfer
            "stcpay" => "transfer", "urpay" => "transfer", "تحويل" => "transfer",
        ];

        foreach ($patterns as $pattern => $categoryName) {
            $category = Category::where("name", $categoryName)->first();
            if ($category) {
                MerchantCategory::updateOrCreate(
                    ["merchant_pattern" => $pattern],
                    ["category_id" => $category->id]
                );
            }
        }
    }
}
