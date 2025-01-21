<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\SendResetPasswordMail;
use App\Models\Doctor;
use App\Models\User;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $patient = Patient::create([
            'name' => $request->name,
            'phone' => '',
            'gender' => '',
            'birth' => $request->birth,
            'address' => '',
            'religion' => '',
            'nik' => '',
            'kk' => '',
            'blood_type' => '',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'patient_id' => $patient->id,
        ]);

        $updatedPatient = Patient::find($patient->id);
        $updatedPatient->user_id = $user->id;
        $updatedPatient->save();

        return response()->json(['message' => 'User registered successfully'], 201);
    }

    public function registerDoctor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'poli_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'doctor',
        ]);

        $doctor = Doctor::create([
            'name' => $request->name,
            'poli_id' => $request->poli_id,
            'user_id' => $user->id,
            'specialty' => $request->specialty,
            'about' => $request->about,
            'education' => json_encode($request->education),
            'work_experience' => json_encode($request->work_experience),
            'actions' => json_encode($request->actions),
        ]);

        // update user
        $updatedUser = User::find($user->id);
        $updatedUser->doctor_id = $doctor->id;
        $updatedUser->save();

        return response()->json(['message' => 'Doctor registered successfully'], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::with('patient')->where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'There is no user found with the matching email'], 401);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'User is not active'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        if ($user->role == 'doctor') {
            $doctor = User::with('doctor.poli')->where('id', $user->id)->first();
            $doctor->doctor->actions = json_decode($doctor->doctor->actions);
            $doctor->doctor->education = json_decode($doctor->doctor->education);
            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $doctor,
            ]);
        }

        Auth::login($user); // login the user

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        if ($user->role == 'doctor') {
            $doctor = User::with('doctor.poli')->where('id', $user->id)->first();
            $doctor->doctor->actions = json_decode($doctor->doctor->actions);
            $doctor->doctor->education = json_decode($doctor->doctor->education);
            return response()->json($doctor);
        }

        if ($user->role == 'patient') {
            $patient = User::with('patient')->where('id', $user->id)->first();
            return response()->json($patient);
        }

        return response()->json($user);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        if ($user->role == 'doctor') {
            $doctor = Doctor::where('user_id', $user->id)->first();
            $doctor->name = $request->name;
            $doctor->nik = $request->nik;
            $doctor->birthdate = $request->birthdate;
            $doctor->gender = $request->gender;
            $doctor->address = $request->address;
            $doctor->phone_number = $request->phone_number;
            $doctor->poli_id = $request->poli_id;
            $doctor->profession = $request->profession;
            $doctor->about = $request->about;
            $doctor->specialty = $request->specialty;
            $doctor->specialty_description = $request->specialty_description;
            $doctor->facebook_link = $request->facebook_link;
            $doctor->twitter_link = $request->twitter_link;
            $doctor->google_plus_link = $request->google_plus_link;
            $doctor->linkedin_link = $request->linkedin_link;
            $doctor->education = json_encode($request->education);
            $doctor->actions = json_encode($request->actions);
            $doctor->save();

            $user->name = $request->name;
            $user->email = $request->email;

            $user->password = Hash::make($request->password);
            $user->save();

            $updatedDoctor = Doctor::where('user_id', $user->id)->first();
            $updatedDoctor->actions = json_decode($updatedDoctor->actions);
            $updatedDoctor->education = json_decode($updatedDoctor->education);
            return response()->json($updatedDoctor);
        }

        $patient = Patient::find($user->patient_id);
        $patient->name = $request->name;
        $patient->phone = $request->phone;
        $patient->gender = $request->gender;
        $patient->birth = $request->birth;
        $patient->address = $request->address;
        $patient->religion = $request->religion;
        $patient->nik = $request->nik;
        $patient->kk = $request->kk;
        $patient->bpjs = $request->bpjs;
        $patient->blood_type = $request->blood_type;
        $patient->save();

        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        $updatedUser = User::with('patient')->where('id', $user->id)->first();

        return response()->json($updatedUser);
    }

    public function sendEmailResetPassword(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'There is no user found with the matching email'], 404);
        }

        // send email here
        Mail::to($user->email)->send(new SendResetPasswordMail($user->email));

        return response()->json(['message' => 'Email sent']);
    }

    public function resetPassword(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        $token = $request->token;

        if (!$user) {
            return response()->json(['message' => 'There is no user found with the matching email'], 404);
        }

        if ($user->remember_token != $token) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $user->password = Hash::make($request->password);
        $user->remember_token = null;
        $user->save();

        return response()->json(['message' => 'Password reset successfully']);
    }

    public function toggleIsActive(Request $request)
    {
        $user = User::find($request->user_id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json($user);
    }
}
