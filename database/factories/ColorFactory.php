<?php

namespace Database\Factories;
use App\Models\Color;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Color>
 */
class ColorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name=['Red','Blue','Dark'];
        return [
            'name' => $this->faker->randomElement($name),
            'code' => $this->faker->imageUrl(),
        ];
    }
}
