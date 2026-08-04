<?php

namespace App\Services\Excel;

use Rap2hpoutre\FastExcel\FastExcel;

class ExcelModuleImporter
{
    private const VALID_LEVELS = ['basic', 'intermediate', 'advanced'];
    private const VALID_TAGS = ['grammar', 'vocabulary'];

    public function parse(string $filePath): array
    {
        $valid = [];
        $errors = [];
        $rowNumber = 1;

        $collection = (new FastExcel)->import($filePath);

        foreach ($collection as $record) {
            $rowNumber++;
            
            $formattedRecord = [];
            foreach ($record as $key => $value) {
                // Normalize "Lesson 1 Title" to "lesson_1_title"
                $normalizedKey = strtolower(trim($key));
                $normalizedKey = str_replace([' ', '-'], '_', $normalizedKey);
                $formattedRecord[$normalizedKey] = $value;
            }

            $reasons = $this->validateRow($formattedRecord);

            if (empty($reasons)) {
                $lessons = [];
                for ($i = 1; $i <= 10; $i++) {
                    $lessonKey = "lesson_{$i}_title";
                    $contentKey = "lesson_{$i}_content";
                    if (!empty(trim($formattedRecord[$lessonKey] ?? ''))) {
                        $lessons[] = [
                            'title' => trim($formattedRecord[$lessonKey]),
                            'content' => trim($formattedRecord[$contentKey] ?? ''),
                            'type'  => 'text',
                            'order_index' => $i
                        ];
                    }
                }

                $questions = [];
                for ($i = 1; $i <= 5; $i++) {
                    $qKey = "q{$i}_question";
                    if (!empty(trim($formattedRecord[$qKey] ?? ''))) {
                        $questions[] = [
                            'question' => trim($formattedRecord[$qKey]),
                            'option_a' => trim($formattedRecord["q{$i}_opt_a"] ?? ''),
                            'option_b' => trim($formattedRecord["q{$i}_opt_b"] ?? ''),
                            'option_c' => trim($formattedRecord["q{$i}_opt_c"] ?? ''),
                            'option_d' => trim($formattedRecord["q{$i}_opt_d"] ?? ''),
                            'correct_answer' => strtolower(trim($formattedRecord["q{$i}_correct"] ?? 'a')),
                            'difficulty' => 3,
                            'type' => strtolower(trim($formattedRecord['tag'] ?? 'grammar')),
                            'tag' => strtolower(trim($formattedRecord['level'] ?? 'basic')),
                            'is_active' => true,
                            'set' => 'A'
                        ];
                    }
                }

                $valid[] = [
                    'module' => [
                        'title'       => trim($formattedRecord['module_title'] ?? ''),
                        'description' => trim($formattedRecord['module_description'] ?? ''),
                        'path_key'    => trim($formattedRecord['path_key'] ?? ''),
                        'level'       => strtolower(trim($formattedRecord['level'] ?? '')),
                        'tag'         => strtolower(trim($formattedRecord['tag'] ?? '')),
                        'is_active'   => true,
                        'order_index' => (int) ($formattedRecord['order_index'] ?? 0),
                        'writing_prompt' => trim($formattedRecord['writing_prompt'] ?? ''),
                        'ai_evaluation_parameters' => trim($formattedRecord['ai_evaluation_parameters'] ?? ''),
                    ],
                    'lessons' => $lessons,
                    'questions' => $questions
                ];
            } else {
                $errors[] = ['row' => $rowNumber, 'reasons' => $reasons];
            }
        }

        return ['valid' => $valid, 'errors' => $errors];
    }

    private function validateRow(array $record): array
    {
        $reasons = [];

        if (empty(trim($record['module_title'] ?? ''))) {
            $reasons[] = "module_title wajib diisi";
        }
        if (empty(trim($record['path_key'] ?? ''))) {
            $reasons[] = "path_key wajib diisi";
        }

        $level = strtolower(trim($record['level'] ?? ''));
        if (!in_array($level, self::VALID_LEVELS, true)) {
            $reasons[] = "level harus salah satu dari: basic, intermediate, advanced";
        }

        $tag = strtolower(trim($record['tag'] ?? ''));
        if (!in_array($tag, self::VALID_TAGS, true)) {
            $reasons[] = "tag harus salah satu dari: grammar, vocabulary";
        }

        return $reasons;
    }
}
