<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource, filtered by jenis if provided.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Report::query();
        if ($request->filled('jenis')) {
            $query->where('jenis', $request->query('jenis'));
        }
        $keyword = $request->query('keyword');
        if ($keyword !== null && $keyword !== '') {
            $t = $keyword;
            $query->where(function ($q) use ($t) {
                $q->where('type', 'like', "%$t%")
                    ->orWhere('warehouse', 'like', "%$t%")
                    ->orWhere('receiver_warehouse', 'like', "%$t%")
                    ->orWhere('sender_pic', 'like', "%$t%")
                    ->orWhere('receiver_pic', 'like', "%$t%")
                    ->orWhere('batch', 'like', "%$t%");
            });
        }

        $reports = $query->latest()->paginate($request->integer('per_page', 15));
        return response()->json($reports);
    }

    /**
     * Summary aggregated view grouped by warehouse, filtered by jenis.
     * Query params:
     * - jenis: string (required)
     * - min_stock: int (optional, default 0) -> B
     * - kebutuhan: int (optional, default 0)
     * - yellow_threshold: int (optional, default 20) classification threshold
     */
    public function summary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'jenis' => 'required|string',
            'min_stock' => 'nullable|integer',
            'kebutuhan' => 'nullable|integer',
            'yellow_threshold' => 'nullable|integer',
        ]);

        $minStockDefault = $request->integer('min_stock', 0);
        $kebutuhanDefault = $request->integer('kebutuhan', 0);
        $yellowThreshold = $request->integer('yellow_threshold', 20);

        $items = Report::query()
            ->where('jenis', $validated['jenis'])
            ->get();

        $grouped = $items->groupBy('warehouse');
        $rows = [];
        $red = 0; $yellow = 0; $green = 0;

        foreach ($grouped as $warehouse => $list) {
            $totalA = (int) $list->sum('qty');
            $onDeliveryC = (int) $list->whereNull('tanggal_sampai')->sum('qty');
            $minStockB = $minStockDefault;
            $gap = $totalA + $onDeliveryC - $minStockB;

            if ($gap < 0) {
                $red++;
            } elseif ($gap < $yellowThreshold) {
                $yellow++;
            } else {
                $green++;
            }

            $rows[] = [
                'warehouse' => $warehouse,
                'total_stock_a' => $totalA,
                'gap_stock' => $gap,
                'kebutuhan' => $kebutuhanDefault,
                'min_stock_requirement_b' => $minStockB,
                'on_delivery_c' => $onDeliveryC,
            ];
        }

        $totalRows = count($rows);
        $percentage = $totalRows > 0 ? round(($green / $totalRows) * 100, 2) : 0.0;
        $lastUpdate = optional($items->max('updated_at'))?->toISOString() ?? now()->toISOString();

        return response()->json([
            'percentage' => $percentage,
            'counts' => [
                'red' => $red,
                'yellow' => $yellow,
                'green' => $green,
            ],
            'last_update' => $lastUpdate,
            'rows' => $rows,
            'meta' => [
                'jenis' => $validated['jenis'],
                'total_rows' => $totalRows,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage (bulk formdata).
     * Expected payload (multipart/form-data or JSON):
     * - jenis: string (required)
     * - items: array of objects (required)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'jenis' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|string',
            'items.*.qty' => 'required|integer',
            'items.*.warehouse' => 'nullable|string',
            'items.*.sender_alamat' => 'nullable|string',
            'items.*.sender_pic' => 'nullable|string',
            'items.*.receiver_alamat' => 'nullable|string',
            'items.*.receiver_warehouse' => 'nullable|string',
            'items.*.receiver_pic' => 'nullable|string',
            'items.*.tanggal_pengiriman' => 'nullable|date',
            'items.*.tanggal_sampai' => 'nullable|date',
            'items.*.batch' => 'nullable|string',
            'items.*.sn_mac_picture' => 'nullable|image|max:5120',
        ]);

        $created = [];
        foreach ($validated['items'] as $i => $item) {
            $path = null;
            $file = $request->file("items.$i.sn_mac_picture");
            if ($file) {
                $path = $file->store('sn_mac_pictures', 'public');
            }
            $created[] = Report::create([
                'jenis' => $validated['jenis'],
                'type' => $item['type'],
                'qty' => $item['qty'],
                'warehouse' => $item['warehouse'] ?? null,
                'sender_alamat' => $item['sender_alamat'] ?? null,
                'sender_pic' => $item['sender_pic'] ?? null,
                'receiver_alamat' => $item['receiver_alamat'] ?? null,
                'receiver_warehouse' => $item['receiver_warehouse'] ?? null,
                'receiver_pic' => $item['receiver_pic'] ?? null,
                'tanggal_pengiriman' => $item['tanggal_pengiriman'] ?? null,
                'tanggal_sampai' => $item['tanggal_sampai'] ?? null,
                'batch' => $item['batch'] ?? null,
                'sn_mac_picture' => $path,
            ]);
        }

        return response()->json([
            'count' => count($created),
            'data' => $created,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Report $report): JsonResponse
    {
        return response()->json($report);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Report $report): JsonResponse
    {
        $validated = $request->validate([
            'jenis' => 'sometimes|string',
            'type' => 'sometimes|string',
            'qty' => 'sometimes|integer',
            'warehouse' => 'nullable|string',
            'sender_alamat' => 'nullable|string',
            'sender_pic' => 'nullable|string',
            'receiver_alamat' => 'nullable|string',
            'receiver_warehouse' => 'nullable|string',
            'receiver_pic' => 'nullable|string',
            'tanggal_pengiriman' => 'nullable|date',
            'tanggal_sampai' => 'nullable|date',
            'batch' => 'nullable|string',
            'sn_mac_picture' => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('sn_mac_picture')) {
            $file = $request->file('sn_mac_picture');
            $path = $file->store('sn_mac_pictures', 'public');
            $validated['sn_mac_picture'] = $path;
        }

        $report->fill($validated);
        $report->save();

        return response()->json($report);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Report $report): JsonResponse
    {
        $report->delete();
        return response()->json(['success' => true]);
    }
}
