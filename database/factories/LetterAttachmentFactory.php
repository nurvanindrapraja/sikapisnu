<?php

namespace Database\Factories;

use App\Models\Letter;
use App\Models\LetterAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LetterAttachment>
 */
class LetterAttachmentFactory extends Factory
{
    protected $model = LetterAttachment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'letter_id' => Letter::factory(),
            'file_path' => 'letters/attachments/test_lampiran.pdf',
            'file_name' => 'Lampiran_Dokumen.pdf',
            'file_size' => 102400,
        ];
    }
}
