<?php

namespace Database\Seeders;

use App\Models\Question;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $questions = [
            // --- BASIC GRAMMAR ---
            [
                'question' => 'Which of the following sentences is grammatically correct?',
                'option_a' => 'She go to school every day.',
                'option_b' => 'She goes to school every day.',
                'option_c' => 'She going to school every day.',
                'option_d' => 'She gone to school every day.',
                'correct_answer' => 'b',
                'type' => 'grammar',
                'tag' => 'basic',
                'difficulty' => 1,
                'is_active' => true,
            ],
            [
                'question' => 'I ___ a student. My sister ___ a doctor.',
                'option_a' => 'am / is',
                'option_b' => 'is / am',
                'option_c' => 'are / is',
                'option_d' => 'am / are',
                'correct_answer' => 'a',
                'type' => 'grammar',
                'tag' => 'basic',
                'difficulty' => 1,
                'is_active' => true,
            ],
            [
                'question' => 'They ___ not like coffee. They prefer tea.',
                'option_a' => 'does',
                'option_b' => 'do',
                'option_c' => 'doing',
                'option_d' => 'done',
                'correct_answer' => 'b',
                'type' => 'grammar',
                'tag' => 'basic',
                'difficulty' => 1,
                'is_active' => true,
            ],

            // --- BASIC VOCABULARY ---
            [
                'question' => 'What is the synonym of the word "happy"?',
                'option_a' => 'Sad',
                'option_b' => 'Angry',
                'option_c' => 'Glad',
                'option_d' => 'Tired',
                'correct_answer' => 'c',
                'type' => 'vocabulary',
                'tag' => 'basic',
                'difficulty' => 1,
                'is_active' => true,
            ],
            [
                'question' => 'To start a conversation with someone new, we usually say ___',
                'option_a' => 'Goodbye',
                'option_b' => 'Hello',
                'option_c' => 'Thank you',
                'option_d' => 'Sorry',
                'correct_answer' => 'b',
                'type' => 'vocabulary',
                'tag' => 'basic',
                'difficulty' => 1,
                'is_active' => true,
            ],
            [
                'question' => 'Which word describes a person who does not like to work or make an effort?',
                'option_a' => 'Active',
                'option_b' => 'Diligent',
                'option_c' => 'Lazy',
                'option_d' => 'Smart',
                'correct_answer' => 'c',
                'type' => 'vocabulary',
                'tag' => 'basic',
                'difficulty' => 1,
                'is_active' => true,
            ],

            // --- ADVANCED GRAMMAR ---
            [
                'question' => 'If we had left earlier, we ___ the flight.',
                'option_a' => 'would not miss',
                'option_b' => 'would not have missed',
                'option_c' => 'will not miss',
                'option_d' => 'had not missed',
                'correct_answer' => 'b',
                'type' => 'grammar',
                'tag' => 'advanced',
                'difficulty' => 3,
                'is_active' => true,
            ],
            [
                'question' => 'The presentation ___ by the CEO before the board meeting started.',
                'option_a' => 'was reviewing',
                'option_b' => 'had been reviewed',
                'option_c' => 'is reviewed',
                'option_d' => 'has reviewed',
                'correct_answer' => 'b',
                'type' => 'grammar',
                'tag' => 'advanced',
                'difficulty' => 3,
                'is_active' => true,
            ],
            [
                'question' => 'Hardly ___ entered the room when the phone rang.',
                'option_a' => 'I had',
                'option_b' => 'had I',
                'option_c' => 'I did',
                'option_d' => 'did I',
                'correct_answer' => 'b',
                'type' => 'grammar',
                'tag' => 'advanced',
                'difficulty' => 4,
                'is_active' => true,
            ],

            // --- ADVANCED VOCABULARY ---
            [
                'question' => 'During the financial presentation, the CFO explained that the company\'s revenue is ___ due to new market opportunities.',
                'option_a' => 'declining',
                'option_b' => 'surging',
                'option_c' => 'fluctuating',
                'option_d' => 'stagnating',
                'correct_answer' => 'b',
                'type' => 'vocabulary',
                'tag' => 'advanced',
                'difficulty' => 3,
                'is_active' => true,
            ],
            [
                'question' => 'To "negotiate" means to ___',
                'option_a' => 'ignore others\' opinions',
                'option_b' => 'discuss something in order to reach an agreement',
                'option_c' => 'make a presentation',
                'option_d' => 'cancel a business meeting',
                'correct_answer' => 'b',
                'type' => 'vocabulary',
                'tag' => 'advanced',
                'difficulty' => 3,
                'is_active' => true,
            ],
            [
                'question' => 'Which word refers to the ability to speak or write a foreign language easily and accurately?',
                'option_a' => 'Fluency',
                'option_b' => 'Hesitation',
                'option_c' => 'Accuracy',
                'option_d' => 'Vocabulary',
                'correct_answer' => 'a',
                'type' => 'vocabulary',
                'tag' => 'advanced',
                'difficulty' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($questions as $data) {
            Question::updateOrCreate(
                ['question' => $data['question']],
                $data
            );
        }
    }
}
