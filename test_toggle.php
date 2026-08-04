<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

Illuminate\Support\Facades\DB::enableQueryLog();

$setting = App\Models\AdminSecuritySetting::getOrCreate(1);

echo "=== BEFORE ===\n";
echo "login_notification (get): " . var_export($setting->login_notification, true) . "\n";
echo "login_notification (raw): " . var_export($setting->getAttributes()['login_notification'] ?? 'N/A', true) . "\n";

// Set to false
$setting->login_notification = false;

echo "\n=== AFTER SET false ===\n";
echo "login_notification (get): " . var_export($setting->login_notification, true) . "\n";
echo "login_notification (raw): " . var_export($setting->getAttributes()['login_notification'] ?? 'N/A', true) . "\n";
echo "isDirty: " . var_export($setting->isDirty('login_notification'), true) . "\n";

$setting->save();

echo "\n=== AFTER SAVE ===\n";
$queries = Illuminate\Support\Facades\DB::getQueryLog();
foreach ($queries as $q) {
    echo "SQL: " . $q['query'] . "\n";
    echo "Bindings: " . json_encode($q['bindings']) . "\n";
}

// Re-read from DB
$fresh = App\Models\AdminSecuritySetting::where('admin_id', 1)->first();
echo "\n=== FRESH FROM DB ===\n";
echo "login_notification (get): " . var_export($fresh->login_notification, true) . "\n";
echo "login_notification (raw): " . var_export($fresh->getAttributes()['login_notification'] ?? 'N/A', true) . "\n";
