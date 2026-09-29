<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Totem;
use Illuminate\Http\Request;

class TotemController extends Controller
{
    public function index()
    {
        $totens = Totem::all();
        return view('totens', compact('totens'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'totem_nome_identificacao' => 'required|max:100',
            'totem_localizacao' => 'nullable|max:100'
        ]);

        Totem::create($validated);
        return redirect()->route('admin.totens.index')->with('success', 'Totem registrado!');
    }
}
