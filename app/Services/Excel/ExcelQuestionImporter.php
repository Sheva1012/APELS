<?php

namespace App\Services\Excel;

use Rap2hpoutre\FastExcel\FastExcel;

class ExcelQuestionImporter
{
    private const VALID_CORRECT_ANSWERS = ['a', 'b', 'c', 'd'];
    private const VALID_TYPES = ['grammar', 'vocabulary'];
    private const VALID_TAGS = ['basic', 'intermediate', 'advanced'];
    private const VALID_SETS = ['A', 'B', 'C'];

    public function parse(string $filePath): array
    {
        $valid = [];
        $errors = [];
        $rowNumber = 1; // 1-indexed, header is conceptually 1, first data is 2.

        $collection = (new FastExcel)->import($filePath);

        foreach ($collection as $record) {
            $rowNumber++;
            
            // Format array keys to lowercase and replace spaces with underscores just in case
            $formattedRecord = [];
            foreach ($record as $key => $value) {
                $formattedRecord[strtolower(trim($key))] = $value;
            }

            $reasons = $this->validateRow($formattedRecord);

            if (empty($reasons)) {
                $valid[] = [
                    'set'            => strtoupper(trim($formattedRecord['set'] ?? 'A')),
                    'question'       => trim($formattedRecord['question'] ?? ''),
                    'option_a'       => trim($formattedRecord['option_a'] ?? ''),
                    'option_b'       => trim($formattedRecord['option_b'] ?? ''),
                    'option_c'       => trim($formattedRecord['option_c'] ?? ''),
                    'option_d'       => trim($formattedRecord['option_d'] ?? ''),
                    'correct_answer' => strtolower(trim($formattedRecord['correct_answer'] ?? '')),
                    'type'           => strtolower(trim($formattedRecord['type'] ?? '')),
                    'tag'            => strtolower(trim($formattedRecord['tag'] ?? '')),
                    'difficulty'     => (int) ($formattedRecord['difficulty'] ?? 1),
                    'is_active'      => true,
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

        $set = strtoupper(trim($record['set'] ?? ''));
        if (empty($set)) {
            $reasons[] = "set tidak boleh kosong";
        }

        foreach (['question', 'option_a', 'option_b', 'option_c', 'option_d'] as $field) {
            if (empty(trim($record[$field] ?? ''))) {
                $reasons[] = "{$field} wajib diisi";
            }
        }

        $ca = strtolower(trim($record['correct_answer'] ?? ''));
        if (!in_array($ca, self::VALID_CORRECT_ANSWERS, true)) {
            $reasons[] = "correct_answer harus salah satu dari: a, b, c, d";
        }

        $type = strtolower(trim($record['type'] ?? ''));
        if (!in_array($type, self::VALID_TYPES, true)) {
            $reasons[] = "type harus salah satu dari: grammar, vocabulary";
        }

        $tag = strtolower(trim($record['tag'] ?? ''));
        if (!in_array($tag, self::VALID_TAGS, true)) {
            $reasons[] = "tag harus salah satu dari: basic, intermediate, advanced";
        }

        $diff = (int) ($record['difficulty'] ?? 1);
        if ($diff < 1 || $diff > 5) {
            $reasons[] = "difficulty harus antara 1-5";
        }

        return $reasons;
    }
}
