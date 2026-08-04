<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$fresh = App\Models\AdminSecuritySetting::where('admin_id', 1)->first();
var_dump($fresh->login_notification, $fresh->auto_logout, $fresh->biometric_enabled);
