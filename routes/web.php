<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/apache-debug', function () {
    return response()->json([
        'apache_conf_snippet' => shell_exec('grep -A5 "Directory /var/www" /etc/apache2/apache2.conf'),
        'apache_conf_public' => shell_exec('grep -A5 "Directory /var/www/html/public" /etc/apache2/apache2.conf'),
        'modules_enabled' => shell_exec('apache2ctl -M 2>&1'),
    ]);
});


