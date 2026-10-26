<?php

namespace Database\Factories;

use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Section>
 */
class SectionFactory extends Factory
{
    protected $model = Section::class;

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
            'name' => 'Seksi '.fake()->words(3, true),
            'code' => 'SKS-'.strtoupper(fake()->lexify('????')),
            'description' => fake()->sentence(),
        ];
    }
}
