<?php

namespace Database\Seeders;
use App\Models\Categories;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Factories\ColorFactory;
use Database\Factories\SizeFactory;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        // Categories::factory(3)->create();
        SizeFactory::factory(3)->create();
        ColorFactory::factory(3)->create();
    }
}
