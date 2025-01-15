<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PoliController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HealthcareController;

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

Route::post('login', [AuthController::class, 'login']);


Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('user', [AuthController::class, 'user']);

    Route::post('logout', [AuthController::class, 'logout']);

    Route::get('polis', [PoliController::class, 'index']);
    Route::get('polis/{poli}', [PoliController::class, 'show']);

    Route::get('doctors', [DoctorController::class, 'index']);
    Route::get('doctors/{doctor}', [DoctorController::class, 'show']);

    Route::get('patients', [PatientController::class, 'index']);
    Route::get('patients/{patient}', [PatientController::class, 'show']);

    Route::post('registrations', [RegistrationController::class, 'store']);
    Route::get('registrations', [RegistrationController::class, 'index']);
    Route::get('registrations-agenda', [RegistrationController::class, 'indexAgenda']);
    Route::get('registrations/{registration}', [RegistrationController::class, 'show']);
    Route::get('registrations-doctor-agenda/{dokter_id}', [RegistrationController::class, 'getRegistrationByDoctorAgendaById']);

    Route::get('healthcares', [HealthcareController::class, 'index']);
    Route::get('healthcares/{healthcare}', [HealthcareController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'role:doctor'])->group(function () {
    Route::get('registrations-doctor/{registration}', [RegistrationController::class, 'getDetailRegistrationByDoctor']);

    Route::get('registrations-doctor', [RegistrationController::class, 'getRegistrationByDoctor']);
    Route::get('registrations-doctor-agenda', [RegistrationController::class, 'getRegistrationByDoctorAgenda']);

    Route::post('medical-records', [MedicalRecordController::class, 'store']);

    Route::delete('medical-records/{medicalrecord}', [MedicalRecordController::class, 'destroy']);

    Route::put('medical-records/{medicalrecord}', [MedicalRecordController::class, 'update']);

    Route::get('medical-records', [MedicalRecordController::class, 'index']);

    Route::get('medical-records/{medicalrecord}', [MedicalRecordController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('polis', [PoliController::class, 'store']);
    Route::put('polis/{poli}', [PoliController::class, 'update']);
    Route::delete('polis/{poli}', [PoliController::class, 'destroy']);

    Route::post('doctors', [DoctorController::class, 'store']);
    Route::delete('doctors/{doctor}', [DoctorController::class, 'destroy']);
    Route::put('doctors/{doctor}', [DoctorController::class, 'update']);
    Route::post('doctors/upload-image', [DoctorController::class, 'uploadImage']);

    Route::apiResource('patients', PatientController::class, ['except' => ['update']]);
    Route::put('patients/{patient}', [PatientController::class, 'update']);
    Route::post('patients/upload-image', [PatientController::class, 'uploadImage']);

    Route::get('medical-records/registration/{registration_id}', [MedicalRecordController::class, 'getMedicalRecordByRegistration']);

    Route::delete('registrations/{registration}', [RegistrationController::class, 'destroy']);

    Route::post('healthcares', [HealthcareController::class, 'store']);
    Route::put('healthcares/{healthcare}', [HealthcareController::class, 'update']);
    Route::delete('healthcares/{healthcare}', [HealthcareController::class, 'destroy']);
    Route::post('healthcares/upload-video', [HealthcareController::class, 'uploadVideo']);
});
