<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreTeamRequest;
use App\Http\Requests\Tenant\UpdateTeamRequest;
use App\Models\Company;
use App\Models\Department;
use App\Models\Division;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    /**
     * Display a listing of teams.
     */
    public function index(Company $company): JsonResponse
    {
        $query = Team::query()
            ->where('company_id', $company->getKey())
            ->with(['division', 'department']);

        if (request()->has('department_id')) {
            $query->where('department_id', request('department_id'));
        }

        if (request()->has('division_id')) {
            $query->where('division_id', request('division_id'));
        }

        $teams = $query->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'teams' => $teams->map(fn ($team) => [
                'id' => $team->id,
                'division_id' => $team->division_id,
                'department_id' => $team->department_id,
                'name' => $team->name,
                'slug' => $team->slug,
                'description' => $team->description,
                'sort_order' => $team->sort_order,
                'is_active' => (bool) $team->is_active,
            ]),
        ]);
    }

    /**
     * Store a newly created team.
     */
    public function store(StoreTeamRequest $request, Company $company): RedirectResponse
    {
        $data = $request->validated();

        if (! isset($data['slug']) || $data['slug'] === '') {
            $data['slug'] = Str::slug($data['name']);
        }

        $division = $data['division_id'] ? Division::find($data['division_id']) : null;
        $department = $data['department_id'] ? Department::find($data['department_id']) : null;

        if ($department && $division && $department->division_id !== $division->id) {
            return redirect()->route('tenant.organization', $company)
                ->withErrors(['department_id' => '所選的部門不屬於提供的事業群。']);
        }

        if ($department && ! $division) {
            $division = $department->division;
            $data['division_id'] = $division?->id;
        }

        Team::create([
            'company_id' => $company->getKey(),
            'division_id' => $data['division_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return redirect()->route('tenant.organization', $company)
            ->with('success', '小組已建立');
    }

    /**
     * Update the specified team.
     */
    public function update(UpdateTeamRequest $request, Company $company, Team $team): RedirectResponse
    {
        if ($team->company_id !== $company->getKey()) {
            abort(404);
        }

        $data = $request->validated();

        if (isset($data['name']) && (! isset($data['slug']) || $data['slug'] === '')) {
            $data['slug'] = Str::slug($data['name']);
        }

        $division = isset($data['division_id']) && $data['division_id'] ? Division::find($data['division_id']) : null;
        $department = isset($data['department_id']) && $data['department_id'] ? Department::find($data['department_id']) : null;

        if ($department && $division && $department->division_id !== $division->id) {
            return redirect()->route('tenant.organization', $company)
                ->withErrors(['department_id' => '所選的部門不屬於提供的事業群。']);
        }

        if ($department && ! $division) {
            $division = $department->division;
            $data['division_id'] = $division?->id;
        }

        $team->update($data);

        return redirect()->route('tenant.organization', $company)
            ->with('success', '小組已更新');
    }

    /**
     * Remove the specified team.
     */
    public function destroy(Company $company, Team $team): RedirectResponse
    {
        if ($team->company_id !== $company->getKey()) {
            abort(404);
        }

        $this->authorize('delete', $team);

        if ($team->users()->exists()) {
            return redirect()->route('tenant.organization', $company)
                ->with('error', '無法刪除小組，因為已有成員資料。請先遷移成員。');
        }

        $team->delete();

        return redirect()->route('tenant.organization', $company)
            ->with('success', '小組已刪除');
    }
}
