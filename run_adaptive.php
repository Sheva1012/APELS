<?php

$scores = ['speaking' => 80, 'grammar' => 50, 'vocabulary' => 80];
app(\App\Services\AdaptiveEngineService::class)->run(1, $scores);
echo "Adaptive Engine ran successfully for User ID 1.\n";
