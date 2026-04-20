<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LocalAdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $user = User::firstOrNew([
            'email' => env('INITIAL_ADMIN_EMAIL', 'admin@example.com'),
        ]);

        $user->name = env('INITIAL_ADMIN_NAME', '管理者');
        $user->role = 10;
        $user->active_flg = 1;
        $user->email_verified_at = $user->email_verified_at ?? now();
        $user->password = Hash::make(env('INITIAL_ADMIN_PASSWORD', 'adminpassword'));
        $user->save();
    }
}
