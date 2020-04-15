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
Route::get('p1','SftpConnection@process1');
Route::get('com','SftpConnection@copyCompletedFiles');
Route::get('test','SftpConnection@testConnection')->name('pingSftp');