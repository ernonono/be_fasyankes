<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\User; // Pastikan Anda mengimpor model User
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;   // Untuk transaksi database
use Illuminate\Support\Facades\File; // Untuk operasi file (hapus gambar)
use Illuminate\Validation\ValidationException; // Untuk menangani error validasi spesifik

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
        // Variabel untuk menyimpan nama file gambar sementara jika upload berhasil
        $imageName = null;

        try {
            // 1. Validasi Input di Awal
            // Jika validasi gagal, Laravel akan otomatis mengirim respons 422 dan menghentikan eksekusi.
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'nik' => 'nullable|string|max:255', // Sesuaikan jika NIK wajib atau unik
                'birthdate' => 'nullable|date',
                'gender' => 'nullable|in:male,female',
                'address' => 'nullable|string',
                'phone_number' => 'nullable|string|max:20', // Sesuaikan panjang max
                // PENTING: Unique di doctors dan users untuk mencegah duplikasi email
                'email' => 'required|string|email|max:255|unique:doctors,email|unique:users,email',
                // Password minimal 8 karakter. Pertimbangkan menambahkan validasi kompleksitas (regex).
                'password' => 'required|string|min:8',
                // Validasi file gambar (max 2MB)
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                // PENTING: Pastikan poli_id ada di tabel polis
                'poli_id' => 'required|exists:polis,id',
                'about' => 'nullable|string',
                'profession' => 'nullable|string|max:255',
                'specialty' => 'nullable|string|max:255',
                'specialty_description' => 'nullable|string',
                // Karena Anda mengirim JSON.stringify dari frontend
                'actions' => 'nullable|json',
                // Karena Anda mengirim JSON.stringify dari frontend
                'education' => 'nullable|json',
            ]);

            // 2. Handle Image Upload (dilakukan setelah validasi sukses)
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path('/doctor_image');
                // Pindahkan gambar ke direktori publik
                $image->move($destinationPath, $imageName);
                // Perbarui data yang divalidasi dengan nama file gambar
                $validatedData['image'] = $imageName;
            } else {
                // Jika tidak ada gambar diupload, pastikan field image tidak ada dalam data
                // atau set ke null tergantung pada nullable/default di database
                $validatedData['image'] = null;
            }

            // 3. Mulai Transaksi Database
            // Semua operasi di dalam callback akan menjadi bagian dari satu transaksi.
            // Jika ada Exception di dalamnya, transaksi akan di-rollback.
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
            // Menggunakan User::create() langsung karena user_id di doctor baru bisa diupdate setelah User dibuat
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => bcrypt($validatedData['password']), // Password sudah di-hash di sini
                'role' => 'doctor', // Pastikan role 'doctor' ada di tabel roles atau sejenisnya
                'doctor_id' => $doctor->id, // Tautkan ke doctor yang baru dibuat
            ]);

            // Update Doctor dengan user_id yang baru dibuat
            $doctor->update(['user_id' => $user->id]);

            // Jika semua operasi berhasil, commit transaksi
            DB::commit();

            // Mengembalikan respons sukses ke frontend
            return response()->json($doctor, 201);

        } catch (ValidationException $e) {
            // Tangani error validasi dari $request->validate()
            // Hapus gambar yang mungkin sudah terupload jika ada error validasi
            if ($imageName && File::exists(public_path('/doctor_image/' . $imageName))) {
                File::delete(public_path('/doctor_image/' . $imageName));
            }
            Log::warning('Validasi gagal saat menambah dokter: ', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422); // Status 422 Unprocessable Entity untuk error validasi
        } catch (\Exception $e) {
            // Rollback transaksi jika terjadi error lain setelah beginTransaction()
            DB::rollBack();

            // Hapus gambar yang mungkin sudah terupload jika ada error lain
            if ($imageName && File::exists(public_path('/doctor_image/' . $imageName))) {
                File::delete(public_path('/doctor_image/' . $imageName));
            }

            Log::error('Error saat menambah dokter: ' . $e->getMessage(), ['exception' => $e]);

            // Mengembalikan pesan error yang lebih informatif untuk debugging
            // Di lingkungan produksi, pertimbangkan pesan yang lebih umum seperti "Terjadi kesalahan server."
            return response()->json([
                'message' => 'Gagal menambahkan dokter'
            ], 500); // Status 500 Internal Server Error
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

    /**
     * Upload an image for a specific doctor.
     * This method assumes you already have a doctor record and want to update their image.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImage(Request $request)
    {
        try {
            // Validasi input: doctor_id wajib dan harus ada, image adalah file gambar
            $request->validate([
                'doctor_id' => 'required|exists:doctors,id',
                'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            $doctor = Doctor::find($request->doctor_id);

            // Hapus gambar lama jika ada
            if ($doctor->image) {
                $image_path = public_path('/doctor_image/') . $doctor->image;
                if (File::exists($image_path)) {
                    File::delete($image_path);
                }
            }

            // Handle image upload
            $image = $request->file('image');
            $name = time() . '.' . $image->getClientOriginalExtension();
            $destinationPath = public_path('/doctor_image');
            $image->move($destinationPath, $name);

            // Update dokter dengan nama gambar baru
            $doctor->update(['image' => $name]);

            return response()->json($doctor, 200);

        } catch (ValidationException $e) {
            Log::warning('Validasi gagal saat upload gambar dokter: ', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error saat upload gambar dokter: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'Gagal mengupload gambar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified doctor in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Doctor  $doctor
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Doctor $doctor)
    {
        // Variabel untuk menyimpan nama file gambar sementara jika upload berhasil
        $imageName = null;

        try {
            // 1. Validasi Input untuk Update
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'nik' => 'nullable|string|max:255',
                'birthdate' => 'nullable|date',
                'gender' => 'nullable|in:male,female',
                'address' => 'nullable|string',
                'phone_number' => 'nullable|string|max:20',
                // Untuk update, email unique harus mengabaikan email dokter ini sendiri dan user terkait
                'email' => 'required|string|email|max:255|unique:doctors,email,'.$doctor->id.'|unique:users,email,'.$doctor->user->id,
                'password' => 'nullable|string|min:8', // Password opsional saat update
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'poli_id' => 'required|exists:polis,id',
                'about' => 'nullable|string',
                'profession' => 'nullable|string|max:255',
                'specialty' => 'nullable|string|max:255',
                'specialty_description' => 'nullable|string',
                'actions' => 'nullable|json',
                'education' => 'nullable|json',
            ]);

            // 2. Handle Image Upload untuk Update
            if ($request->hasFile('image')) {
                // Hapus gambar lama jika ada
                if ($doctor->image && File::exists(public_path('/doctor_image/' . $doctor->image))) {
                    File::delete(public_path('/doctor_image/' . $doctor->image));
                }
                $image = $request->file('image');
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path('/doctor_image');
                $image->move($destinationPath, $imageName);
                $validatedData['image'] = $imageName; // Set nama gambar baru
            } else {
                // Jika tidak ada gambar baru diupload, pertahankan gambar lama dari DB
                // Pastikan tidak menimpa dengan null jika fieldnya opsional
                if (!isset($validatedData['image'])) { // cek jika 'image' tidak ada di request atau null
                    $validatedData['image'] = $doctor->image;
                }
            }


            // 3. Mulai Transaksi Database
            DB::beginTransaction();

            // Dekode data JSON jika ada dan pastikan menjadi array PHP
            // Jika field tidak dikirim dari frontend, gunakan data lama dari model
            if (isset($validatedData['education'])) {
                $validatedData['education'] = json_decode($validatedData['education'], true);
            } else {
                $validatedData['education'] = json_decode($doctor->education, true); // Gunakan data lama
            }

            if (isset($validatedData['actions'])) {
                $validatedData['actions'] = json_decode($validatedData['actions'], true);
            } else {
                $validatedData['actions'] = json_decode($doctor->actions, true); // Gunakan data lama
            }

            // Update doctor
            $doctor->update($validatedData);

            // Update user associated with the doctor
            $userData = [
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
            ];

            // Hanya update password jika ada di request
            if ($request->password) {
                $userData['password'] = bcrypt($validatedData['password']);
            }
            $doctor->user()->update($userData);

            // Commit transaksi jika semua operasi berhasil
            DB::commit();

            return response()->json($doctor, 200);
        } catch (ValidationException $e) {
            // Tangani error validasi
            // Hapus gambar yang mungkin sudah terupload jika ada error validasi saat update
            if ($imageName && File::exists(public_path('/doctor_image/' . $imageName))) {
                File::delete(public_path('/doctor_image/' . $imageName));
            }
            Log::warning('Validasi gagal saat update dokter: ', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Rollback transaksi jika terjadi error lain
            DB::rollBack();

            // Hapus gambar yang mungkin sudah terupload jika ada error lain
            if ($imageName && File::exists(public_path('/doctor_image/' . $imageName))) {
                File::delete(public_path('/doctor_image/' . $imageName));
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

            // Hapus gambar profil dokter jika ada
            if ($doctor->image && File::exists(public_path('/doctor_image/' . $doctor->image))) {
                File::delete(public_path('/doctor_image/' . $doctor->image));
            }

            // Hapus user yang terkait dengan dokter
            if ($doctor->user) {
                $doctor->user->delete();
            }

            // Hapus dokter itu sendiri
            $doctor->delete();
            return response()->json(null, 204); // Respon 204 No Content untuk penghapusan berhasil
        } catch (\Exception $e) {
            Log::error('Error saat menghapus dokter: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'Gagal menghapus dokter: ' . $e->getMessage()], 500);
        }
    }
}
