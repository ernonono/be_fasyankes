<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PoliController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('register', [AuthController::class, 'register']);
Route::post('register-doctor', [AuthController::class, 'registerDoctor']);
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', 'role:patient'])->group(function () {
    Route::apiResource('polis', PoliController::class);
    Route::apiResource('dokters', DoctorController::class);
    Route::apiResource('medical_record', MedicalRecordController::class);
    Route::apiResource('patients', PatientController::class);
    Route::apiResource('registrations', RegistrationController::class);
});

Route::middleware(['auth:sanctum', 'role:doctor'])->group(function () {
    Route::get('registrations-doctor', [RegistrationController::class, 'getRegistrationByDoctor']);
});
