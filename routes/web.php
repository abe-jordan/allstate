<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::post('/', 'ServicetradeController@receive')->name('webhook');
Route::post('/send', 'ServicetradeController@request');
Route::get('/send', 'ServicetradeController@send');
/* Route::get('/', 'PdfController@pdf');
Route::prefix('file')->group(function () {
    Route::post('/build', 'FileBuilderController@build');
    Route::get('/build', 'FileBuilderController@retrieveBuild');
    Route::post('/send', 'FileBuilderController@send');
    Route::get('/send', 'WebhookController@retrieveSent');
}); */
    // Authentication Routes...
    Route::get('login', 'Auth\LoginController@showLoginForm')->name('login');
    Route::post('login', 'Auth\LoginController@login');
    Route::post('logout', 'Auth\LoginController@logout')->name('logout');

    // Registration Routes...
/*     Route::get('register', 'Auth\RegisterController@showRegistrationForm')->name('register');
    Route::post('register', 'Auth\RegisterController@register'); */

    // Password Reset Routes...
    Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
    Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
    Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
    Route::post('password/reset', 'Auth\ResetPasswordController@reset');
    Route::get('/home', 'HomeController@index')->name('home');
Route::get('/', 'HomeController@index')->name('home');
Route::post('/download', 'HomeController@export_csv')->name('toCsv');

Route::get('test','ServicetradeController@unscheduled');

Route::post('cleaner/read','AddressCleanerController@read');
Route::post('cleaner/export','AddressCleanerController@create');
Route::post('cleaner/clean','AddressCleanerController@update');
Route::get('cleaner','AddressCleanerController@index');
