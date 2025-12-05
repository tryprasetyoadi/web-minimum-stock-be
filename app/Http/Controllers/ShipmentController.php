<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShipmentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = max(1, min(100, (int) $request->query('per_page', 15)));

        $query = Shipment::with(['pic', 'approvedBy'])->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('jenis')) {
            $query->where('jenis', $request->query('jenis'));
        }
        if ($request->filled('merk')) {
            $query->where('merk', $request->query('merk'));
        }
        if ($request->filled('delivery_by')) {
            $query->where('delivery_by', $request->query('delivery_by'));
        }
        $keyword = $request->query('keyword');
        $search = $request->query('search');
        $term = $keyword ?? $search;
        if ($term !== null && $term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('type', 'like', "%$term%")
                    ->orWhere('jenis', 'like', "%$term%")
                    ->orWhere('merk', 'like', "%$term%")
                    ->orWhere('alamat_tujuan', 'like', "%$term%")
                    ->orWhere('status', 'like', "%$term%")
                    ->orWhere('delivery_by', 'like', "%$term%");
            });
        }

        $paginator = $query->paginate($perPage);

        $data = array_map(function ($item) {
            return [
                'id' => $item->id,
                'type' => $item->type,
                'jenis' => $item->jenis,
                'merk' => $item->merk,
                'qty' => $item->qty,
                'delivery_by' => $item->delivery_by,
                'alamat_tujuan' => $item->alamat_tujuan,
                'pic' => [
                    'id' => optional($item->pic)->id,
                    'name' => optional($item->pic)->name,
                ],
                'approved_by' => [
                    'id' => optional($item->approvedBy)->id,
                    'name' => optional($item->approvedBy)->name,
                ],
                'status' => $item->status,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
        }, $paginator->items());

        return response()->json([
            'message' => 'Shipments fetched successfully',
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'max:150'],
            'jenis' => ['required', 'string', 'max:100'],
            'merk' => ['required', 'string', 'max:100'],
            'qty' => ['required', 'integer', 'min:1'],
            'delivery_by' => ['required', Rule::in(['Udara', 'Darat'])],
            'alamat_tujuan' => ['required', 'string', 'max:1000'],
            'pic_user_id' => ['required', 'integer', 'exists:users,id'],
            'approved_by_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['required', Rule::in(['Submitted', 'On Going', 'Approved', 'Completed', 'Cancelled'])],
        ]);

        $shipment = Shipment::create($validated);

        return response()->json([
            'message' => 'Shipment created successfully',
            'data' => $shipment,
        ], 201);
    }

    public function show(Shipment $shipment)
    {
        $shipment->load(['pic', 'approvedBy']);
        return response()->json($shipment);
    }

    public function update(Request $request, Shipment $shipment)
    {
        $validated = $request->validate([
            'type' => ['sometimes', 'required', 'string', 'max:150'],
            'jenis' => ['sometimes', 'required', 'string', 'max:100'],
            'merk' => ['sometimes', 'required', 'string', 'max:100'],
            'qty' => ['sometimes', 'required', 'integer', 'min:1'],
            'delivery_by' => ['sometimes', 'required', Rule::in(['Udara', 'Darat'])],
            'alamat_tujuan' => ['sometimes', 'required', 'string', 'max:1000'],
            'pic_user_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'approved_by_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['sometimes', 'required', Rule::in(['Submitted', 'On Going', 'Approved', 'Completed', 'Cancelled'])],
        ]);

        $shipment->update($validated);

        return response()->json([
            'message' => 'Shipment updated successfully',
            'data' => $shipment,
        ]);
    }

    public function destroy(Shipment $shipment)
    {
        $shipment->delete();
        return response()->json([
            'message' => 'Shipment deleted successfully',
        ]);
    }
}
