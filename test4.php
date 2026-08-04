<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\Illuminate\Support\Facades\DB::enableQueryLog();

$setting = App\Models\AdminSecuritySetting::getOrCreate(1);
$setting->login_notification = false;
$setting->save();

var_dump(\Illuminate\Support\Facades\DB::getQueryLog());
