<?php

/*
|--------------------------------------------------------------------------
| APELS Learning Paths Master Configuration
|--------------------------------------------------------------------------
|
| Single source of truth untuk delapan learning path APELS.
|
| Digunakan oleh:
|   - AdaptiveEngineService::run()           => mengambil `label` via
|     config("learning_paths.{$pathKey}.label") untuk field `path_label`
|     pada response. (Req 9.5)
|   - Filament ModuleResource                => populate Select `path_key`
|     dari array_keys(config('learning_paths')) sebagai whitelist valid.
|     (Req 21.3)
|   - Dashboard mahasiswa                    => menampilkan label readiness
|     path aktif. (Req 13.2)
|   - LearningPathSeeder & dokumentasi audit => mendokumentasikan threshold
|     rule per path agar dapat direview oleh dosen/admin.
|
| Setiap entri WAJIB memiliki:
|   - label     (string) Label tampilan Dashboard sesuai Req 13.2.
|   - color     (string) Token warna Tailwind untuk badge/UI.
|   - condition (string) Threshold rule human-readable. Implementasi
|                        deterministik berada di AdaptiveEngineService::evaluateRules
|                        (Req 9.1) — string ini hanya untuk dokumentasi/audit.
|   - modules   (array)  Daftar slug modul yang relevan dengan path ini.
|                        Slug ini menjadi acuan ModuleSeeder dan harus
|                        konsisten dengan kolom `modules.path_key` (Req 11).
|
| Daftar `path_key` yang valid (Req 9.3 — totality AdaptiveEngineService):
|   emergency_foundation, fundamental_communication, basic_speaking,
|   grammar_foundation, vocabulary_builder, intermediate_path,
|   professional_simulation, industry_ready
|
| Sumber: CLAUDE.md §10 (Adaptive Engine — Learning Path Master).
|
*/

return [

    'financial_position_reporting' => [
        'label'     => 'FINANCIAL POSITION & TRANSACTION REPORTING',
        'color'     => 'blue',
        'condition' => 'Speaking < 60',
        'modules'   => [],
    ],

    'income_statement_analysis' => [
        'label'     => 'INCOME STATEMENT & PERFORMANCE ANALYSIS',
        'color'     => 'yellow',
        'condition' => 'Semua skor 60-70',
        'modules'   => [],
    ],

    'cash_flow_auditing_taxation' => [
        'label'     => 'CASH FLOW, AUDITING & TAXATION',
        'color'     => 'green',
        'condition' => 'Speaking >= 70',
        'modules'   => [],
    ],

];
