<?php

namespace Database\Factories;

use App\Enums\JobPostingStatus;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobPosting>
 */
class JobPostingFactory extends Factory
{
    /**
     * 既定は「下書き」。作成者は採用担当者
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->jobTitle(),
            'description' => fake()->realText(200),
            'status' => JobPostingStatus::Draft,
            'created_by' => User::factory()->recruiter(),
        ];
    }

    /**
     * 公開中の求人票にする
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JobPostingStatus::Published,
        ]);
    }

    /**
     * 募集終了の求人票にする
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JobPostingStatus::Closed,
        ]);
    }
}
