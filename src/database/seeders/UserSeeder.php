<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $staffNames = [
            ['login_id' => 'tanaka', 'name' => '田中 太郎', 'is_admin' => true],
            ['login_id' => 'suzuki', 'name' => '鈴木 花子', 'is_admin' => true],
            ['login_id' => 'sato', 'name' => '佐藤 一郎', 'is_admin' => false],
            ['login_id' => 'yamada', 'name' => '山田 美咲', 'is_admin' => false],
            ['login_id' => 'takahashi', 'name' => '高橋 健太', 'is_admin' => false],
        ];

        foreach ($staffNames as $staff) {
            User::firstOrCreate(
                ['login_id' => $staff['login_id']],
                [
                    'name' => $staff['name'],
                    'password' => Hash::make('P@ssw0rd987'),
                    'is_active' => true,
                    'is_admin' => $staff['is_admin'],
                    'temporary_password_expires_at' => null,
                ]
            );
        }
    }
}
