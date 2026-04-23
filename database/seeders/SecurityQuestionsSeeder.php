<?php

namespace Database\Seeders;

use App\Models\SecurityQuestion;
use Illuminate\Database\Seeder;

class SecurityQuestionsSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            ['question' => '¿Cuál es el nombre de tu primera mascota?', 'sort_order' => 1],
            ['question' => '¿En qué ciudad naciste?', 'sort_order' => 2],
            ['question' => '¿Cuál es el nombre de soltera de tu madre?', 'sort_order' => 3],
            ['question' => '¿Cuál fue el nombre de tu primera escuela?', 'sort_order' => 4],
            ['question' => '¿Cuál es tu comida favorita?', 'sort_order' => 5],
            ['question' => '¿Cuál fue tu primer trabajo?', 'sort_order' => 6],
            ['question' => '¿Cuál es el nombre de tu mejor amigo de la infancia?', 'sort_order' => 7],
            ['question' => '¿En qué calle vivías cuando tenías 10 años?', 'sort_order' => 8],
            ['question' => '¿Cuál es el segundo nombre de tu padre?', 'sort_order' => 9],
            ['question' => '¿Cuál fue el modelo de tu primer auto?', 'sort_order' => 10],
        ];

        foreach ($questions as $question) {
            SecurityQuestion::firstOrCreate(
                ['question' => $question['question']],
                [
                    'is_active' => true,
                    'sort_order' => $question['sort_order'],
                ]
            );
        }
    }
}
