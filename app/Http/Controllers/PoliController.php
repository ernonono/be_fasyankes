<?php

namespace App\Http\Controllers;

use App\Models\Poli;
use Illuminate\Http\Request;

class PoliController extends Controller
{
    public function index()
    {
        $name = request()->query('name');
        $location = request()->query('location');

        $poli = Poli::with('doctors')
            ->when($name, function ($query) use ($name) {
                return $query->where('name', 'like', "%$name%");
            })
            ->when($location, function ($query) use ($location) {
                return $query->where('location', 'like', "%$location%");
            })
            ->get();

        return response()->json($poli, 200);
    }

    public function store(Request $request)
    {
        $poli = Poli::create($request->all());
        return response()->json($poli, 201);
    }

    public function show(Poli $poli)
    {
        return $poli;
    }

    public function update(Request $request, Poli $poli)
    {
        $poli->update($request->all());
        return response()->json($poli, 200);
    }

    public function destroy(Poli $poli)
    {
        $poli->delete();
        return response()->json(null, 204);
    }
}
