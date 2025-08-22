<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\InvoiceEmailPreviewController;
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

Route::middleware('page-cache')->group(function () {
    Route::get('/', function () {
        return view('welcome');
    });

    Route::get('/blog/{slug}', [BlogController::class, 'show']);

    Route::get('/work', function () {
        return view('work');
    });
});

// Invoice email preview route
Route::get('/invoice/{invoice}/email-preview', [InvoiceEmailPreviewController::class, 'preview'])
    ->name('invoice.email.preview');
