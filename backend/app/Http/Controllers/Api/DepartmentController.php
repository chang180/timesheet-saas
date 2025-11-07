<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\WeeklyReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Department::with(['company', 'users']);

        if ($user->isAdmin()) {
            $query->where('company_id', $user->company_id);
        } else {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $departments = $query->get();
        return response()->json($departments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $department = Department::create([
            'company_id' => $request->user()->company_id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json($department, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $department = Department::with(['company', 'users'])->findOrFail($id);

        $user = $request->user();
        if ($user->isAdmin() && $department->company_id !== $user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        } elseif ($user->isManager() && $department->id !== $user->department_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($department);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $department = Department::findOrFail($id);

        if ($department->company_id !== $request->user()->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $department->update($validated);
        return response()->json($department);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $department = Department::findOrFail($id);

        if ($department->company_id !== $request->user()->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $department->delete();
        return response()->json(null, 204);
    }

    /**
     * Get department summary with weekly reports
     */
    public function summary(Request $request, string $id)
    {
        $department = Department::findOrFail($id);

        $user = $request->user();
        if ($user->isManager() && $department->id !== $user->department_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        } elseif ($user->isAdmin() && $department->company_id !== $user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $weekStartDate = $request->input('week_start_date', now()->startOfWeek()->toDateString());

        $reports = WeeklyReport::with(['user', 'items'])
            ->where('department_id', $id)
            ->where('week_start_date', $weekStartDate)
            ->get();

        $summary = [
            'department' => $department,
            'week_start_date' => $weekStartDate,
            'total_reports' => $reports->count(),
            'submitted_reports' => $reports->where('status', 'submitted')->count(),
            'approved_reports' => $reports->where('status', 'approved')->count(),
            'total_hours' => $reports->sum(function ($report) {
                return $report->items->sum('hours_spent');
            }),
            'reports' => $reports,
        ];

        return response()->json($summary);
    }
}

