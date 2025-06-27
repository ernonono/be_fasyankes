<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File; // Ganti Storage dengan File Facade untuk public_path
use Illuminate\Validation\ValidationException;

class DoctorController extends Controller
{
    /**
     * Display a listing of the doctor.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $poli_id = $request->query('poli_id');
        $name = $request->query('name');
        $specialty = $request->query('specialty');

        $dokters = Doctor::when($poli_id, function ($query, $poli_id) {
            return $query->where('poli_id', '=', $poli_id);
        })
            ->when($name, function ($query, $name) {
                return $query->where('name', 'like', "%$name%");
            })
            ->when($specialty, function ($query, $specialty) {
                return $query->where('specialty', 'like', "%$specialty%");
            })
            ->with(['poli', 'user'])->get();

        // Dekode education dan actions dari JSON string ke array/object PHP jika ada
        $dokters->each(function ($doctor) {
            if ($doctor->education) {
                $doctor->education = json_decode($doctor->education, true);
            }
            if ($doctor->actions) {
                $doctor->actions = json_decode($doctor->actions, true);
            }
        });

        return response()->json($dokters, 200);
    }

    /**
     * Store a newly created doctor in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Variabel untuk menyimpan nama file sementara jika upload berhasil
        $imageFileName = null;
        $suratIzinFileName = null;

        try {
            // 1. Validasi Input di Awal
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'nik' => 'nullable|string|max:255',
                'birthdate' => 'nullable|date',
                'gender' => 'nullable|in:male,female',
                'address' => 'nullable|string',
                'phone_number' => 'nullable|string|max:20',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => 'required|string|min:8',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB
                'surat_izin' => 'nullable|file|mimes:pdf,jpeg,jpg,png|max:5120', // Max 5MB
                'poli_id' => 'required|exists:polis,id',
                'about' => 'nullable|string',
                'quota' => 'nullable|integer|min:0',
                'profession' => 'nullable|string|max:255',
                'specialty' => 'nullable|string|max:255',
                'specialty_description' => 'nullable|string',
                'actions' => 'nullable|json',
                'education' => 'nullable|json',
            ]);

            // Ambil data user (email dan password) sebelum menghapus dari $validatedData
            $userEmail = $validatedData['email'];
            $userPassword = $validatedData['password'];

            // Hapus 'email' dan 'password' dari $validatedData karena ini akan digunakan untuk membuat Doctor.
            unset($validatedData['email']);
            unset($validatedData['password']);

            // 2. Handle File Upload (dilakukan setelah validasi sukses)
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageFileName = time() . '_image_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path('doctor_image'); // <-- Lokasi penyimpanan gambar

                // Pastikan direktori ada
                if (!File::isDirectory($destinationPath)) {
                    File::makeDirectory($destinationPath, 0777, true, true);
                }
                $image->move($destinationPath, $imageFileName); // Pindahkan file
                $validatedData['image'] = $imageFileName; // Simpan hanya nama file ke database
            } else {
                $validatedData['image'] = null; // Pastikan null jika tidak ada gambar
            }

            if ($request->hasFile('surat_izin')) {
                $suratIzin = $request->file('surat_izin');
                $suratIzinFileName = time() . '_izin_' . uniqid() . '.' . $suratIzin->getClientOriginalExtension();
                $destinationPathIzin = public_path('doctor_izin'); // <-- Lokasi penyimpanan surat izin

                // Pastikan direktori ada
                if (!File::isDirectory($destinationPathIzin)) {
                    File::makeDirectory($destinationPathIzin, 0777, true, true);
                }
                $suratIzin->move($destinationPathIzin, $suratIzinFileName); // Pindahkan file
                $validatedData['surat_izin'] = $suratIzinFileName; // Simpan hanya nama file ke database
            } else {
                $validatedData['surat_izin'] = null; // Pastikan null jika tidak ada surat izin
            }

            // 3. Mulai Transaksi Database
            DB::beginTransaction();

            // Dekode data JSON jika ada dan pastikan menjadi array PHP
            if (isset($validatedData['education'])) {
                $validatedData['education'] = json_decode($validatedData['education'], true);
            }
            if (isset($validatedData['actions'])) {
                $validatedData['actions'] = json_decode($validatedData['actions'], true);
            }

            // Buat entri Doctor
            $doctor = Doctor::create($validatedData);

            // Buat entri User yang terasosiasi dengan Doctor
            $user = User::create([
                'name' => $request->input('name'),
                'email' => $userEmail,
                'password' => bcrypt($userPassword),
                'role' => 'doctor',
                'doctor_id' => $doctor->id,
            ]);

            // Update Doctor dengan user_id yang baru dibuat
            $doctor->update(['user_id' => $user->id]);

            // Jika semua operasi berhasil, commit transaksi
            DB::commit();

            // Mengembalikan respons sukses ke frontend
            return response()->json($doctor, 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            // Hapus file yang mungkin sudah terupload jika ada error validasi
            if ($imageFileName && File::exists(public_path('doctor_image/' . $imageFileName))) {
                File::delete(public_path('doctor_image/' . $imageFileName));
            }
            if ($suratIzinFileName && File::exists(public_path('doctor_izin/' . $suratIzinFileName))) {
                File::delete(public_path('doctor_izin/' . $suratIzinFileName));
            }
            Log::warning('Validasi gagal saat menambah dokter: ', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            // Hapus file yang mungkin sudah terupload jika ada error lain
            if ($imageFileName && File::exists(public_path('doctor_image/' . $imageFileName))) {
                File::delete(public_path('doctor_image/' . $imageFileName));
            }
            if ($suratIzinFileName && File::exists(public_path('doctor_izin/' . $suratIzinFileName))) {
                File::delete(public_path('doctor_izin/' . $suratIzinFileName));
            }
            Log::error('Error saat menambah dokter: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'Gagal menambahkan dokter: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified doctor.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        // Find the doctor by ID with their associated poli and user
        $doctor = Doctor::with(['poli', 'user'])->find($id);

        // Return 404 if not found
        if (!$doctor) {
            return response()->json(['message' => 'Doctor not found'], 404);
        }

        // Dekode education dan actions dari JSON string ke array/object PHP jika ada
        if ($doctor->education) {
            $doctor->education = json_decode($doctor->education, true);
        }
        if ($doctor->actions) {
            $doctor->actions = json_decode($doctor->actions, true);
        }

        // Return the doctor's details
        return response()->json($doctor, 200);
    }

    // Metode uploadImage ini akan saya hapus karena logikanya sudah diintegrasikan ke store dan update.
    // Jika Anda punya route terpisah untuk ini dan ingin tetap memakainya, Anda perlu menyesuaikan
    // agar ia menyimpan ke public/doctor_image dan hanya mengembalikan nama file.
    // Untuk tujuan topik ini, saya akan menghapusnya agar tidak ada duplikasi/kebingungan.

    /**
     * Update the specified doctor in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Doctor  $doctor
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Doctor $doctor)
    {
        // Variabel untuk menyimpan nama file sementara jika upload berhasil
        $imageFileName = null;
        $suratIzinFileName = null;

        try {
            // 1. Validasi Input untuk Update
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'nik' => 'nullable|string|max:255',
                'birthdate' => 'nullable|date',
                'gender' => 'nullable|in:male,female',
                'address' => 'nullable|string',
                'phone_number' => 'nullable|string|max:20',
                // Unique email validation for update, excluding current user's email
                'email' => 'required|string|email|max:255|unique:users,email,' . $doctor->user->id,
                'password' => 'nullable|string|min:8', // Password opsional saat update
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB
                'surat_izin' => 'nullable|file|mimes:pdf,jpeg,jpg,png|max:5120', // Max 5MB
                'poli_id' => 'required|exists:polis,id',
                'about' => 'nullable|string',
                'quota' => 'nullable|integer|min:0',
                'profession' => 'nullable|string|max:255',
                'specialty' => 'nullable|string|max:255',
                'specialty_description' => 'nullable|string',
                'actions' => 'nullable|json',
                'education' => 'nullable|json',
            ]);

            // Ambil data user (email dan password jika ada) sebelum memodifikasi $validatedData
            $userEmail = $validatedData['email'];
            $userPassword = $validatedData['password'] ?? null;

            // Hapus 'email' dan 'password' dari $validatedData
            unset($validatedData['email']);
            if (isset($validatedData['password'])) {
                unset($validatedData['password']);
            }

            // 2. Mulai Transaksi Database
            DB::beginTransaction();

            // 3. Handle Image Upload untuk Update
            if ($request->hasFile('image')) {
                // Hapus gambar lama jika ada
                if ($doctor->image && File::exists(public_path('doctor_image/' . $doctor->image))) {
                    File::delete(public_path('doctor_image/' . $doctor->image));
                }
                $image = $request->file('image');
                $imageFileName = time() . '_image_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path('doctor_image');
                $image->move($destinationPath, $imageFileName);
                $validatedData['image'] = $imageFileName; // Simpan hanya nama file baru
            } elseif ($request->input('image_removed') == 'true') { // Flag dari frontend untuk menghapus gambar
                if ($doctor->image && File::exists(public_path('doctor_image/' . $doctor->image))) {
                    File::delete(public_path('doctor_image/' . $doctor->image));
                }
                $validatedData['image'] = null; // Set di DB menjadi null
            } else {
                // Jika tidak ada file baru dan tidak ada sinyal untuk menghapus, pertahankan yang lama
                if (!isset($validatedData['image'])) {
                    $validatedData['image'] = $doctor->image;
                }
            }

            // LOGIKA BARU UNTUK SURAT IZIN PADA UPDATE
            if ($request->hasFile('surat_izin')) {
                // Hapus surat izin lama jika ada
                if ($doctor->surat_izin && File::exists(public_path('doctor_izin/' . $doctor->surat_izin))) {
                    File::delete(public_path('doctor_izin/' . $doctor->surat_izin));
                }
                $suratIzin = $request->file('surat_izin');
                $suratIzinFileName = time() . '_izin_' . uniqid() . '.' . $suratIzin->getClientOriginalExtension();
                $destinationPathIzin = public_path('doctor_izin');
                $suratIzin->move($destinationPathIzin, $suratIzinFileName);
                $validatedData['surat_izin'] = $suratIzinFileName; // Simpan hanya nama file baru
            } elseif ($request->input('surat_izin_removed') == 'true') { // Flag dari frontend untuk menghapus surat izin
                if ($doctor->surat_izin && File::exists(public_path('doctor_izin/' . $doctor->surat_izin))) {
                    File::delete(public_path('doctor_izin/' . $doctor->surat_izin));
                }
                $validatedData['surat_izin'] = null; // Set di DB menjadi null
            } else {
                // Jika tidak ada file baru dan tidak ada sinyal untuk menghapus, pertahankan yang lama
                if (!isset($validatedData['surat_izin'])) {
                    $validatedData['surat_izin'] = $doctor->surat_izin;
                }
            }


            // Dekode data JSON jika ada dan pastikan menjadi array PHP
            // Jika field tidak dikirim dari frontend, gunakan data lama dari model
            if (isset($validatedData['education'])) {
                $validatedData['education'] = json_decode($validatedData['education'], true);
            } else {
                $validatedData['education'] = json_decode($doctor->education, true);
            }

            if (isset($validatedData['actions'])) {
                $validatedData['actions'] = json_decode($validatedData['actions'], true);
            } else {
                $validatedData['actions'] = json_decode($doctor->actions, true);
            }

            // Update doctor
            $doctor->update($validatedData);

            // Update user associated with the doctor
            $userData = [
                'name' => $request->input('name'),
                'email' => $userEmail,
            ];

            // Hanya update password jika ada di request
            if ($userPassword) {
                $userData['password'] = bcrypt($userPassword);
            }
            $doctor->user()->update($userData);

            // Commit transaksi jika semua operasi berhasil
            DB::commit();

            return response()->json($doctor, 200);
        } catch (ValidationException $e) {
            DB::rollBack();
            // Hapus file yang mungkin sudah terupload jika ada error validasi saat update
            if ($imageFileName && File::exists(public_path('doctor_image/' . $imageFileName))) {
                File::delete(public_path('doctor_image/' . $imageFileName));
            }
            if ($suratIzinFileName && File::exists(public_path('doctor_izin/' . $suratIzinFileName))) {
                File::delete(public_path('doctor_izin/' . $suratIzinFileName));
            }
            Log::warning('Validasi gagal saat update dokter: ', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            // Hapus file yang mungkin sudah terupload jika ada error lain
            if ($imageFileName && File::exists(public_path('doctor_image/' . $imageFileName))) {
                File::delete(public_path('doctor_image/' . $imageFileName));
            }
            if ($suratIzinFileName && File::exists(public_path('doctor_izin/' . $suratIzinFileName))) {
                File::delete(public_path('doctor_izin/' . $suratIzinFileName));
            }
            Log::error('Error saat update dokter: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'Gagal mengupdate dokter: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified doctor from storage.
     *
     * @param  \App\Models\Doctor  $doctor
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Doctor $doctor)
    {
        try {
            // Periksa apakah dokter memiliki registrasi aktif
            if ($doctor->registrations()->exists()) {
                return response()->json(['message' => 'Dokter ini memiliki registrasi aktif. Tidak dapat dihapus.'], 400);
            }

            DB::beginTransaction(); // Mulai transaksi untuk penghapusan

            // Hapus gambar profil dokter jika ada
            if ($doctor->image && File::exists(public_path('doctor_image/' . $doctor->image))) {
                File::delete(public_path('doctor_image/' . $doctor->image));
            }

            // Hapus surat izin jika ada
            if ($doctor->surat_izin && File::exists(public_path('doctor_izin/' . $doctor->surat_izin))) {
                File::delete(public_path('doctor_izin/' . $doctor->surat_izin));
            }

            // Hapus user yang terkait dengan dokter
            if ($doctor->user) {
                $doctor->user->delete();
            }

            // Hapus dokter itu sendiri
            $doctor->delete();

            DB::commit(); // Commit transaksi jika semua berhasil

            return response()->json(null, 204);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback jika ada error
            Log::error('Error saat menghapus dokter: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'Gagal menghapus dokter: ' . $e->getMessage()], 500);
        }
    }
}
