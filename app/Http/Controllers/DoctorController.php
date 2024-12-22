<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DoctorController extends Controller
{
    public function index(Request $request)
    {
        $poli_id = $request->query('poli_id');

        $dokters = Doctor::when($poli_id, function ($query, $poli_id) {
            return $query->where('poli_id', '=', $poli_id);
        })->with(['poli', 'user'])->get();

        return response()->json($dokters, 200);
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all(); // Copy request data

            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $name = time() . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path('/doctor_image');
                $image->move($destinationPath, $name);
                $data['image'] = $name; // Update the copied data with the image name
            }

            // Create doctor
            $doctor = Doctor::create($data);

            // Create user associated with the doctor
            $user = $doctor->user()->create([
                'name' => $request->name,
                'email' => $request->email,
                'doctor_id' => $doctor->id,
                'password' => bcrypt($request->password),
                'role' => 'doctor',
            ]);

            // Update the doctor with the user ID
            $doctor->update(['user_id' => $user->id]);

            if ($user) {
                return response()->json($doctor, 201);
            }

            return response()->json($doctor, 500);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }


    public function show($id)
    {
        // Find the doctor by ID
        $doctor = Doctor::with(['poli', 'user'])->find($id);

        // Return 404 if not found
        if (!$doctor) {
            return response()->json(['message' => 'Doctor not found'], 404);
        }

        // Return the doctor's details
        return response()->json($doctor, 200);
    }

    public function uploadImage(Request $request)
    {
        try {
            $doctor = Doctor::find($request->doctor_id);

            // check if doctor exists
            if (!$doctor) {
                return response()->json(['message' => 'Doctor not found'], 404);
            }

            // delete existing image
            if ($doctor->image) {
                $image_path = public_path('/doctor_image/') . $doctor->image;
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }


            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $name = time() . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path('/doctor_image');
                $image->move($destinationPath, $name);

                // Update the doctor with the image name
                $doctor->update(['image' => $name]);

                return response()->json($doctor, 200);
            }

            return response()->json(['message' => 'No image uploaded'], 400);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function update(Request $request, Doctor $doctor)
    {
        try {
            $data = $request->all(); // Copy request data

            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $name = time() . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path('/doctor_image');
                $image->move($destinationPath, $name);
                $data['image'] = $name; // Update the copied data with the image name
            }

            // Update the doctor
            $doctor->update($data);

            // Update the user associated with the doctor
            $doctor->user()->update([
                'name' => $request->name,
                'email' => $request->email,
            ]);

            if ($request->password) {
                $doctor->user()->update([
                    'password' => bcrypt($request->password),
                ]);
            }

            return response()->json($doctor, 200); // Update the response status code to 200
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }


    public function destroy(Doctor $doctor)
    {
        $doctor->delete();
        return response()->json(null, 204);
    }
}
