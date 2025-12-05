<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Report;

class WarehouseController extends Controller
{
    public function taCcanOptions(Request $request, string $warehouse = null)
    {
        $warehouseParam = $warehouse ?? (string) $request->query('warehouse', '');
        $warehouseParam = trim($warehouseParam);

        if ($warehouseParam === '') {
            return response()->json([
                'message' => 'warehouse is required',
            ], 422);
        }

        $results = [];

        if (empty($results)) {
            try {
                $rows = DB::table('ta_ccan_options')
                    ->select('ta_ccan')
                    ->where('warehouse', $warehouseParam)
                    ->distinct()
                    ->get()
                    ->pluck('ta_ccan')
                    ->filter()
                    ->unique()
                    ->values();
                $results = $rows->all();
            } catch (\Throwable $e) {
            }
        }

        if (empty($results)) {
            $rows = Report::query()
                ->where(function ($q) use ($warehouseParam) {
                    $q->where('receiver_warehouse', $warehouseParam)
                        ->orWhere('warehouse', $warehouseParam);
                })
                ->select(['receiver_pic', 'sender_pic'])
                ->get();

            $set = collect($rows)
                ->flatMap(function ($r) {
                    return [
                        $r->receiver_pic,
                        $r->sender_pic,
                    ];
                })
                ->filter()
                ->unique()
                ->values();
            $results = $set->all();
        }

        return response()->json([
            'warehouse' => $warehouseParam,
            'data' => array_map(function ($v) {
                return [
                    'value' => $v,
                    'label' => $v,
                ];
            }, $results),
        ]);
    }
}
