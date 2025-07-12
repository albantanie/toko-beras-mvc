<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TESTING DATABASE SEEDER ===" . PHP_EOL;

try {
    echo "Running migrate:fresh --seed..." . PHP_EOL;
    
    // Run migrate fresh with seed
    Artisan::call('migrate:fresh', ['--seed' => true]);
    
    echo "✅ Migration and seeding completed successfully!" . PHP_EOL;
    echo "Output: " . Artisan::output() . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error occurred: " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . PHP_EOL;
    
    if ($e->getPrevious()) {
        echo "Previous error: " . $e->getPrevious()->getMessage() . PHP_EOL;
    }
}

echo "=== TEST COMPLETED ===" . PHP_EOL;
