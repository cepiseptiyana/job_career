<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\JobType;
use App\Models\Category;

class JobFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => $this->faker->jobTitle(),
            'user_id' => User::inRandomOrder()->value('id'),          // ambil user id valid
            'job_type_id' => JobType::inRandomOrder()->value('id'),   // ambil job_type id valid
            'category_id' => Category::inRandomOrder()->value('id'),  // ambil category id valid
            'vacancy' => rand(1, 5),
            'location' => $this->faker->city(),
            'description' => $this->faker->realText(),
            'experience' => rand(1, 10),
            'company_name' => $this->faker->company(),
        ];
    }
}
