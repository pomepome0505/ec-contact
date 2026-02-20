<?php

namespace Database\Seeders;

use App\Models\InquiryCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InquiryCategorySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => '商品について', 'display_order' => 1],
            ['name' => '注文について', 'display_order' => 2],
            ['name' => '配送について', 'display_order' => 3],
            ['name' => '返品・交換について', 'display_order' => 4],
            ['name' => 'システムについて', 'display_order' => 5],
            ['name' => 'その他', 'display_order' => 6],
        ];

        foreach ($categories as $category) {
            InquiryCategory::firstOrCreate(
                ['name' => $category['name']],
                $category,
            );
        }
    }
}
