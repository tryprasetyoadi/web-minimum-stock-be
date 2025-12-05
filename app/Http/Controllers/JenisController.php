<?php

namespace App\Http\Controllers;

use App\Models\Jenis;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JenisController extends Controller
{
    public function index(Request $request)
    {
        $query = Jenis::orderBy('name');
        $keyword = $request->query('keyword');
        if ($keyword !== null && $keyword !== '') {
            $query->where('name', 'like', "%$keyword%");
        }
        $list = $query->get(['id', 'name']);

        return response()->json([
            'message' => 'Jenis fetched successfully',
            'data' => $list,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:jenis,name'],
        ]);

        $jenis = Jenis::create(['name' => $validated['name']]);

        return response()->json([
            'message' => 'Jenis created successfully',
            'data' => [
                'id' => $jenis->id,
                'name' => $jenis->name,
            ],
        ], 201);
    }

    public function show(Jenis $jenis)
    {
        return response()->json([
            'id' => $jenis->id,
            'name' => $jenis->name,
        ]);
    }

    public function update(Request $request, Jenis $jenis)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('jenis', 'name')->ignore($jenis->id)],
        ]);

        $jenis->update(['name' => $validated['name']]);

        return response()->json([
            'message' => 'Jenis updated successfully',
            'data' => [
                'id' => $jenis->id,
                'name' => $jenis->name,
            ],
        ]);
    }

    public function destroy(Jenis $jenis)
    {
        if ($jenis->merks()->exists()) {
            return response()->json([
                'message' => 'Cannot delete jenis with associated merks',
            ], 422);
        }

        $jenis->delete();

        return response()->json([
            'message' => 'Jenis deleted successfully',
        ]);
    }
}
