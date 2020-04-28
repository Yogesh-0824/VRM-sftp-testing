<?php

use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return view('welcome');
});
Route::get('sftp','SftpConnection@index');
Route::get('p1','Process_one@index');
Route::get('p2','Process_two@index');
Route::get('com','PostProcessingTask@index');
Route::get('test','SftpConnection@testConnection')->name('pingSftp');