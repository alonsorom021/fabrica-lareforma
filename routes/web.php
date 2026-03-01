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
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'N/A',
        'apache_config' => file_get_contents('/etc/apache2/sites-available/000-default.conf'),
        'apache_conf_snippet' => shell_exec('grep -A5 "Directory /var/www" /etc/apache2/apache2.conf'),
        'htaccess_exists' => file_exists(public_path('.htaccess')),
        'htaccess_content' => file_get_contents(public_path('.htaccess')),
    ]);
});


