<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (App\Models\Module::all() as $m) {
    echo "Module ID: {$m->id}\n";
    echo "Title: {$m->title}\n";
    echo "Tag: {$m->tag}\n";
    echo "Level: {$m->level}\n";
    echo "Matching active questions: " . App\Models\Question::active()->byTag($m->tag)->count() . "\n";
    echo "----------------------------------------\n";
}
