<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $staffNames = [
            ['login_id' => 'tanaka', 'name' => '田中 太郎'],
            ['login_id' => 'suzuki', 'name' => '鈴木 花子'],
            ['login_id' => 'sato', 'name' => '佐藤 一郎'],
            ['login_id' => 'yamada', 'name' => '山田 美咲'],
            ['login_id' => 'takahashi', 'name' => '高橋 健太'],
            ['login_id' => 'watanabe', 'name' => '渡辺 由美'],
            ['login_id' => 'ito', 'name' => '伊藤 大輔'],
            ['login_id' => 'nakamura', 'name' => '中村 あかり'],
        ];

        foreach ($staffNames as $staff) {
            User::factory()->create($staff);
        }
    }
}
