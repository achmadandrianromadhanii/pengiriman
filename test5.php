<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$setting = App\Models\AdminSecuritySetting::getOrCreate(1);
var_dump('Original:', $setting->getOriginal('login_notification'));
var_dump('Casted Original:', $setting->login_notification);

$setting->login_notification = false;
var_dump('New Attribute:', $setting->getAttributes()['login_notification']);
var_dump('Casted New:', $setting->login_notification);

var_dump('Is Dirty?', $setting->isDirty('login_notification'));
