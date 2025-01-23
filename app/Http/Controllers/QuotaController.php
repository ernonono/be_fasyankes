<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Quota;
use Illuminate\Http\Request;

class QuotaController extends Controller
{
    public function index()
    {
        $quotas = Quota::all();
        return view('quotas.index', compact('quotas'));
    }

    public function create()
    {
        return view('quotas.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required',
            'dayIndex' => 'required',
            'quota' => 'required',
            'time' => 'required',
        ]);

        Quota::create($request->all());

        return redirect()->route('quotas.index')
            ->with('success', 'Quota created successfully.');
    }

    public function show(Quota $quota)
    {
        return view('quotas.show', compact('quota'));
    }

    public function edit(Quota $quota)
    {
        return view('quotas.edit', compact('quota'));
    }

    public function update(Request $request, Quota $quota)
    {
        $request->validate([
            'doctor_id' => 'required',
            'dayIndex' => 'required',
            'quota' => 'required',
            'time' => 'required',
        ]);

        $quota->update($request->all());

        return redirect()->route('quotas.index')
            ->with('success', 'Quota updated successfully');
    }

    public function destroy(Quota $quota)
    {
        $quota->delete();

        return redirect()->route('quotas.index')
            ->with('success', 'Quota deleted successfully');
    }
}
