<?php

namespace Database\Factories;

use App\Models\Workgroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workgroup>
 */
class WorkgroupFactory extends Factory
{
    protected $model = Workgroup::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'level' => 'PC ISNU',
            'pac_id' => null,
            'name' => 'Pokja '.fake()->words(3, true),
            'code' => 'PKJ-'.strtoupper(fake()->lexify('????')),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
