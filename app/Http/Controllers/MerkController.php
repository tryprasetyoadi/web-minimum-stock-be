<?php

namespace App\Http\Controllers;

use App\Models\Merk;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MerkController extends Controller
{
    public function index(Request $request)
    {
        $query = Merk::with('jenis')->orderBy('name');

        if ($request->filled('jenis_id')) {
            $query->where('jenis_id', (int) $request->query('jenis_id'));
        }

        $keyword = $request->query('keyword');
        if ($keyword !== null && $keyword !== '') {
            $query->where('name', 'like', "%$keyword%");
        }

        $list = $query->get(['id', 'name', 'jenis_id']);

        $data = $list->map(function ($merk) {
            return [
                'id' => $merk->id,
                'name' => $merk->name,
                'jenis' => [
                    'id' => optional($merk->jenis)->id,
                    'name' => optional($merk->jenis)->name,
                ],
            ];
        })->all();

        return response()->json([
            'message' => 'Merk fetched successfully',
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'jenis_id' => ['required', 'integer', 'exists:jenis,id'],
        ]);

        // Unik gabungan name+jenis
        $exists = Merk::where('name', $validated['name'])
            ->where('jenis_id', $validated['jenis_id'])
            ->exists();
        if ($exists) {
            return response()->json([
                'message' => 'Merk name already exists for the selected jenis',
            ], 422);
        }

        $merk = Merk::create($validated);

        return response()->json([
            'message' => 'Merk created successfully',
            'data' => [
                'id' => $merk->id,
                'name' => $merk->name,
                'jenis' => [
                    'id' => optional($merk->jenis)->id,
                    'name' => optional($merk->jenis)->name,
                ],
            ],
        ], 201);
    }

    public function show(Merk $merk)
    {
        $merk->load('jenis');
        return response()->json([
            'id' => $merk->id,
            'name' => $merk->name,
            'jenis' => [
                'id' => optional($merk->jenis)->id,
                'name' => optional($merk->jenis)->name,
            ],
        ]);
    }

    public function update(Request $request, Merk $merk)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'jenis_id' => ['required', 'integer', 'exists:jenis,id'],
        ]);

        $exists = Merk::where('name', $validated['name'])
            ->where('jenis_id', $validated['jenis_id'])
            ->where('id', '!=', $merk->id)
            ->exists();
        if ($exists) {
            return response()->json([
                'message' => 'Merk name already exists for the selected jenis',
            ], 422);
        }

        $merk->update($validated);

        $merk->load('jenis');
        return response()->json([
            'message' => 'Merk updated successfully',
            'data' => [
                'id' => $merk->id,
                'name' => $merk->name,
                'jenis' => [
                    'id' => optional($merk->jenis)->id,
                    'name' => optional($merk->jenis)->name,
                ],
            ],
        ]);
    }

    public function destroy(Merk $merk)
    {
        $merk->delete();
        return response()->json([
            'message' => 'Merk deleted successfully',
        ]);
    }
}
