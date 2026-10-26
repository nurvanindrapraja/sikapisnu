<?php

namespace Database\Factories;

use App\Models\Program;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    protected $model = Program::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'name' => 'Program '.fake()->words(3, true),
            'code' => 'PRG-'.strtoupper(fake()->lexify('????')),
            'indicator' => 'Terlaksananya kegiatan dan partisipasi kader',
            'target' => fake()->randomElement(['100 Kader', '5 Kegiatan', '90% Kehadiran', '1 Buku/Laporan']),
            'description' => fake()->sentence(),
            'year' => '2026',
            'is_active' => true,
        ];
    }
}
