<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ActivityLog::query()->with('user');
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }
        $logs = $query->latest('timestamp')->paginate($request->integer('per_page', 15));
        return response()->json($logs);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'activity' => 'required|string',
            'timestamp' => 'nullable|date',
        ]);

        $log = ActivityLog::create($validated);
        return response()->json($log, 201);
    }

    public function show(ActivityLog $activityLog): JsonResponse
    {
        return response()->json($activityLog->load('user'));
    }

    public function update(Request $request, ActivityLog $activityLog): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'activity' => 'sometimes|string',
            'timestamp' => 'nullable|date',
        ]);
        $activityLog->fill($validated)->save();
        return response()->json($activityLog);
    }

    public function destroy(ActivityLog $activityLog): JsonResponse
    {
        $activityLog->delete();
        return response()->json(['success' => true]);
    }
}