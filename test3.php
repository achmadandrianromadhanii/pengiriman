<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    \Illuminate\Support\Facades\DB::table('admin_security_settings')->where('id', 1)->update(['login_notification' => 'false']);
    echo "Done updating string 'false'!\n";
} catch (\Exception $e) {
    echo "Error updating string 'false': " . $e->getMessage() . "\n";
}

try {
    \Illuminate\Support\Facades\DB::table('admin_security_settings')->where('id', 1)->update(['auto_logout' => false]);
    echo "Done updating boolean false!\n";
} catch (\Exception $e) {
    echo "Error updating boolean false: " . $e->getMessage() . "\n";
}
