<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index()
    {
        $name = request()->query('name');

        $data = Patient::with('user')
            ->when($name, function ($query) use ($name) {
                return $query->whereHas('user', function ($query) use ($name) {
                    return $query->where('name', 'like', "%$name%");
                });
            })
            ->get();


        return response()->json($data, 200);
    }

    public function uploadImage(Request $request)
    {
        try {
            $patient = Patient::find($request->patient_id);

            // check if data exists
            if (!$patient) {
                return response()->json(['message' => 'Patient not found'], 404);
            }

            // delete existing image
            if ($patient->image) {
                $image_path = public_path('/patient_image/') . $patient->image;
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }


            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $name = time() . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path('/patient_image');
                $image->move($destinationPath, $name);

                // Update the doctor with the image name
                $patient->update(['image' => $name]);

                return response()->json($patient, 200);
            }

            return response()->json(['message' => 'No image uploaded'], 400);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all(); // Copy request data

            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $name = time() . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path('/patient_image');
                $image->move($destinationPath, $name);
                $data['image'] = $name; // Update the copied data with the image name
            }

            // Create doctor
            $patient = Patient::create($data);

            // Create user associated with the doctor
            $user = $patient->user()->create([
                'name' => $request->name,
                'email' => $request->email,
                'patient_id' => $patient->id,
                'password' => bcrypt($request->password),
                'role' => 'patient',
            ]);

            // Update the doctor with the user ID
            $patient->update(['user_id' => $user->id]);

            if ($user) {
                return response()->json($patient, 201);
            }

            return response()->json($patient, 500);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        // Find the doctor by ID
        $patient = Patient::with('user')->find($id);

        // Return 404 if not found
        if (!$patient) {
            return response()->json(['message' => 'Patient not found'], 404);
        }

        // Return the doctor's details
        return response()->json($patient, 200);
    }

    public function update(Request $request, Patient $patient)
    {
        try {
            $data = $request->all(); // Copy request data

            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $name = time() . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path('/patient_image');
                $image->move($destinationPath, $name);
                $data['image'] = $name; // Update the copied data with the image name
            }

            // Update the doctor
            $patient->update($data);

            // Update the user associated with the doctor
            $patient->user()->update([
                'name' => $request->name,
                'email' => $request->email,
            ]);

            if ($request->password) {
                $patient->user()->update([
                    'password' => bcrypt($request->password),
                ]);
            }

            return response()->json($patient, 200); // Update the response status code to 200
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function destroy(Patient $patient)
    {
        $patient->delete();
        return response()->json(null, 204);
    }
}
