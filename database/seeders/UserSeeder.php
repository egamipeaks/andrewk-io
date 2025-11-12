<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'andrew',
            'email' => 'andrew@andrewk.io',
            'password' => Hash::make('test'),
            'email_verified_at' => Carbon::now(),
        ]);
    }
}
