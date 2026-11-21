<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['name' => 'Demo Admin', 'email' => 'admin@vending.test', 'role' => 'admin', 'password' => 'AdminDemo123!'],
            ['name' => 'Demo User', 'email' => 'user@vending.test', 'role' => 'user', 'password' => 'UserDemo123!'],
        ] as $attributes) {
            $user = User::withTrashed()->firstOrNew(['email' => $attributes['email']]);

            if (! $user->exists) {
                $user->name = $attributes['name'];
                $user->role = $attributes['role'];
                $user->password = $attributes['password'];
                $user->save();
            }
        }
    }
}
