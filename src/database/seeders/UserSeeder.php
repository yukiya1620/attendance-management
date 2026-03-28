<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    public function run()
    {
        // 管理者
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => '管理者',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => Carbon::now(),
            ]
        );

        // スタッフ
        $staffs = [
            ['name' => '西　怜奈', 'email' => 'reina.n@coachtech.com'],
            ['name' => '山田　太郎', 'email' => 'taro.y@coachtech.com'],
            ['name' => '増田　一世', 'email' => 'issei.m@coachtech.com'],
            ['name' => '山本　敬吉', 'email' => 'keikichi.y@coachtech.com'],
            ['name' => '秋田　朋美', 'email' => 'tomomi.a@coachtech.com'],
            ['name' => '中西　教夫', 'email' => 'norio.n@coachtech.com'],
        ];

        foreach ($staffs as $staff) {
            User::updateOrCreate(
                ['email' => $staff['email']],
                [
                    'name' => $staff['name'],
                    'password' => Hash::make('password'),
                    'role' => 'user',
                    'email_verified_at' => Carbon::now(),
                ]
            );
        }
    }
}