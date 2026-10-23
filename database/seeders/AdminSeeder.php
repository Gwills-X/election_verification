<?php
namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(['email' => env('ADMIN_EMAIL', 'admin@example.com')], [
            'name' => 'Administrator',
            'password' => Hash::make(env('ADMIN_PASSWORD', 'ChangeMe123!')),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }
}
