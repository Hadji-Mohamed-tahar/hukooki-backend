<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Admin::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'username' => 'admin',
                'email'    => 'admin@admin.com',
                'password' => Hash::make('admin@1234'),
            ]
        );

        $this->command->info('✅ Admin created successfully!');
        $this->command->info('📧 Email   : admin@admin.com');
        $this->command->info('👤 Username: admin');
        $this->command->info('🔑 Password: admin@1234');
    }
}