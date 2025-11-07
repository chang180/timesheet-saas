<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WeeklyReport;
use App\Models\WeeklyReportItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WeeklyReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = WeeklyReport::with(['items', 'user', 'department']);

        if ($user->isMember()) {
            $query->where('user_id', $user->id);
        } elseif ($user->isManager()) {
            $query->where('department_id', $user->department_id);
        } elseif ($user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('user', function ($userQuery) use ($user) {
                    $userQuery->where('company_id', $user->company_id);
                });
            });
        }

        if ($request->has('week_start_date')) {
            $query->where('week_start_date', $request->week_start_date);
        }

        $reports = $query->latest('week_start_date')->paginate(20);

        return response()->json($reports);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'week_start_date' => 'required|date',
            'week_end_date' => 'required|date|after:week_start_date',
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.type' => 'required|in:current_week,next_week',
            'items.*.task_description' => 'required|string',
            'items.*.hours_spent' => 'nullable|numeric|min:0',
            'items.*.redmine_issue_id' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $report = WeeklyReport::create([
                'user_id' => $request->user()->id,
                'department_id' => $request->user()->department_id,
                'week_start_date' => $validated['week_start_date'],
                'week_end_date' => $validated['week_end_date'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'draft',
            ]);

            foreach ($validated['items'] as $index => $item) {
                WeeklyReportItem::create([
                    'weekly_report_id' => $report->id,
                    'type' => $item['type'],
                    'task_description' => $item['task_description'],
                    'hours_spent' => $item['hours_spent'] ?? null,
                    'redmine_issue_id' => $item['redmine_issue_id'] ?? null,
                    'sort_order' => $index,
                ]);
            }

            DB::commit();
            return response()->json($report->load('items'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create report'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $report = WeeklyReport::with(['items', 'user', 'department'])->findOrFail($id);

        $user = $request->user();
        if ($user->isMember() && $report->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        } elseif ($user->isManager() && $report->department_id !== $user->department_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        } elseif ($user->isAdmin() && $report->user->company_id !== $user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($report);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $report = WeeklyReport::findOrFail($id);

        if ($request->user()->id !== $report->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string',
            'status' => 'nullable|in:draft,submitted,approved',
            'items' => 'nullable|array',
            'items.*.type' => 'required|in:current_week,next_week',
            'items.*.task_description' => 'required|string',
            'items.*.hours_spent' => 'nullable|numeric|min:0',
            'items.*.redmine_issue_id' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $report->update([
                'notes' => $validated['notes'] ?? $report->notes,
                'status' => $validated['status'] ?? $report->status,
            ]);

            if (isset($validated['items'])) {
                $report->items()->delete();
                foreach ($validated['items'] as $index => $item) {
                    WeeklyReportItem::create([
                        'weekly_report_id' => $report->id,
                        'type' => $item['type'],
                        'task_description' => $item['task_description'],
                        'hours_spent' => $item['hours_spent'] ?? null,
                        'redmine_issue_id' => $item['redmine_issue_id'] ?? null,
                        'sort_order' => $index,
                    ]);
                }
            }

            DB::commit();
            return response()->json($report->load('items'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update report'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $report = WeeklyReport::findOrFail($id);

        if ($request->user()->id !== $report->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $report->delete();
        return response()->json(null, 204);
    }

    /**
     * Export weekly report to CSV
     */
    public function exportCsv(Request $request, string $id)
    {
        $report = WeeklyReport::with(['items', 'user', 'department'])->findOrFail($id);

        $user = $request->user();
        if ($user->isMember() && $report->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $filename = "weekly_report_{$report->week_start_date}.csv";
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($report) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Weekly Report - ' . $report->user->name]);
            fputcsv($file, ['Week', $report->week_start_date . ' to ' . $report->week_end_date]);
            fputcsv($file, ['Department', $report->department->name]);
            fputcsv($file, []);
            fputcsv($file, ['Type', 'Task Description', 'Hours Spent', 'Redmine Issue']);

            foreach ($report->items as $item) {
                fputcsv($file, [
                    $item->type,
                    $item->task_description,
                    $item->hours_spent ?? 'N/A',
                    $item->redmine_issue_id ?? 'N/A',
                ]);
            }

            fputcsv($file, []);
            fputcsv($file, ['Notes', $report->notes ?? 'N/A']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

