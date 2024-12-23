<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\User;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'birth' => 'required|date',
            'blood_type' => 'required|string|max:255',
            'gender' => 'required|string|max:255',
            'religion' => 'required|string|max:255',
            'nik' => 'required|string|max:255',
            'kk' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $patient = Patient::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'gender' => $request->gender,
            'birth' => $request->birth,
            'address' => $request->address,
            'religion' => $request->religion,
            'nik' => $request->nik,
            'kk' => $request->kk,
            'blood_type' => $request->blood_type,
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

        $token = $user->createToken('auth_token')->plainTextToken;

        if ($user->role == 'doctor') {
            $doctor = User::with('doctor')->where('id', $user->id)->first();
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
}
