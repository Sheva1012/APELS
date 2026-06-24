<?php

use App\Models\User;
use App\Models\Question;
use App\Models\DiagnosticResult;
use App\Models\UserLearningPath;
use App\Models\Feedback;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
    $this->seed(\Database\Seeders\ModuleSeeder::class);
});

describe('API Endpoints - Lecturer Revisions', function () {
    
    describe('GET /api/dashboard', function () {
        it('allows authenticated student to access and returns correct structure', function () {
            $student = User::factory()->create();
            $student->assignRole('mahasiswa');

            // Set up some diagnostic result and feedback
            $diagnostic = DiagnosticResult::create([
                'user_id' => $student->id,
                'speaking' => 65,
                'grammar' => 55,
                'vocabulary' => 68,
                'overall' => 61.9,
                'attempt' => 1,
                'is_speaking_processed' => true,
            ]);

            $activePath = UserLearningPath::create([
                'user_id' => $student->id,
                'path_key' => 'grammar_foundation',
                'status' => 'active',
                'assigned_at' => now(),
            ]);

            $feedback = Feedback::create([
                'user_id' => $student->id,
                'diagnostic_result_id' => $diagnostic->id,
                'message' => 'Your speaking needs improvement...',
                'details' => [],
                'next_focus' => 'Grammar',
            ]);

            $response = $this->actingAs($student)->getJson('/api/dashboard');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'skills' => [
                            'speaking',
                            'grammar',
                            'vocabulary',
                            'writing',
                            'business_vocab',
                        ],
                        'learning_path',
                        'feedback',
                    ]
                ])
                ->assertJsonPath('data.skills.speaking', 65)
                ->assertJsonPath('data.skills.grammar', 55)
                ->assertJsonPath('data.skills.vocabulary', 68)
                ->assertJsonPath('data.skills.writing', 60) // derived: round(55 * 1.09) = 60
                ->assertJsonPath('data.skills.business_vocab', 62) // derived: round(68 * 0.91) = 62
                ->assertJsonPath('data.learning_path', 'grammar_foundation')
                ->assertJsonPath('data.feedback', 'Your speaking needs improvement...');
        });

        it('returns fallback data if no diagnostic results exist', function () {
            $student = User::factory()->create();
            $student->assignRole('mahasiswa');

            $response = $this->actingAs($student)->getJson('/api/dashboard');

            $response->assertStatus(200)
                ->assertJsonPath('data.skills.speaking', 0)
                ->assertJsonPath('data.skills.grammar', 0)
                ->assertJsonPath('data.skills.vocabulary', 0)
                ->assertJsonPath('data.learning_path', 'none');
        });

        it('denies access to guests', function () {
            $response = $this->getJson('/api/dashboard');
            $response->assertStatus(401);
        });
    });

    describe('GET /api/learning-path', function () {
        it('returns correct learning path layout with unlocked and locked modules', function () {
            $student = User::factory()->create();
            $student->assignRole('mahasiswa');

            UserLearningPath::create([
                'user_id' => $student->id,
                'path_key' => 'professional_simulation',
                'status' => 'active',
                'assigned_at' => now(),
            ]);

            $response = $this->actingAs($student)->getJson('/api/learning-path');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'learning_path',
                        'modules_unlocked',
                        'modules_locked',
                    ]
                ])
                ->assertJsonPath('data.learning_path', 'professional_simulation');

            // The seeder seeds Basic English Foundation (basic level) which is always unlocked
            $unlockedModules = $response->json('data.modules_unlocked');
            expect($unlockedModules)->toContain('basic_english_foundation');
        });
    });

    describe('GET /api/feedback', function () {
        it('returns latest feedback message', function () {
            $student = User::factory()->create();
            $student->assignRole('mahasiswa');

            $diagnostic = DiagnosticResult::create([
                'user_id' => $student->id,
                'speaking' => 80,
                'grammar' => 80,
                'vocabulary' => 80,
                'overall' => 80,
                'attempt' => 1,
                'is_speaking_processed' => true,
            ]);

            Feedback::create([
                'user_id' => $student->id,
                'diagnostic_result_id' => $diagnostic->id,
                'message' => 'Excellent progress!',
                'details' => [],
                'next_focus' => 'none',
            ]);

            $response = $this->actingAs($student)->getJson('/api/feedback');

            $response->assertStatus(200)
                ->assertJsonPath('data.feedback', 'Excellent progress!');
        });
    });

    describe('POST /api/questions', function () {
        it('allows admin to create a question', function () {
            $admin = User::factory()->create();
            $admin->assignRole('admin');

            $response = $this->actingAs($admin)->postJson('/api/questions', [
                'question' => 'What is the correct past tense of go?',
                'options' => [
                    'a' => 'goed',
                    'b' => 'went',
                    'c' => 'gone',
                    'd' => 'going',
                ],
                'correct_answer' => 'b',
                'tag' => 'basic',
                'type' => 'grammar',
                'difficulty' => 1,
            ]);

            $response->assertStatus(201)
                ->assertJsonPath('status', 'success');

            $this->assertDatabaseHas('questions', [
                'question' => 'What is the correct past tense of go?',
                'correct_answer' => 'b',
                'tag' => 'basic',
            ]);
        });

        it('allows dosen to create a question', function () {
            $dosen = User::factory()->create();
            $dosen->assignRole('dosen');

            $response = $this->actingAs($dosen)->postJson('/api/questions', [
                'question' => 'Select the synonym of big.',
                'options' => [
                    'a' => 'small',
                    'b' => 'huge',
                    'c' => 'tiny',
                    'd' => 'thin',
                ],
                'correct_answer' => 'b',
                'tag' => 'intermediate',
                'type' => 'vocabulary',
            ]);

            $response->assertStatus(201);
            $this->assertDatabaseHas('questions', [
                'question' => 'Select the synonym of big.',
                'correct_answer' => 'b',
            ]);
        });

        it('denies student from creating a question', function () {
            $student = User::factory()->create();
            $student->assignRole('mahasiswa');

            $response = $this->actingAs($student)->postJson('/api/questions', [
                'question' => 'Hack question',
                'options' => ['a' => '1', 'b' => '2', 'c' => '3', 'd' => '4'],
                'correct_answer' => 'a',
                'tag' => 'basic',
            ]);

            $response->assertStatus(403);
        });

        it('denies guest from creating a question', function () {
            $response = $this->postJson('/api/questions', [
                'question' => 'Guest question',
                'options' => ['a' => '1', 'b' => '2', 'c' => '3', 'd' => '4'],
                'correct_answer' => 'a',
                'tag' => 'basic',
            ]);

            $response->assertStatus(401);
        });
    });
});
