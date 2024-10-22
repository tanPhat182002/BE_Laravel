<?php

namespace Database\Factories;
use App\Models\Size;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Size>
 */
class SizeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name=['S','M','L'];
        return [
            'name' => $this->faker->randomElement($name),
            'code' => $this->faker->imageUrl(),
        ];
    }
}
