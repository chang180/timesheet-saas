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
    public function store(StoreTeamRequest $request, Company $company): JsonResponse
    {
        $data = $request->validated();

        if (! isset($data['slug']) || $data['slug'] === '') {
            $data['slug'] = Str::slug($data['name']);
        }

        $division = $data['division_id'] ? Division::find($data['division_id']) : null;
        $department = $data['department_id'] ? Department::find($data['department_id']) : null;

        if ($department && $division && $department->division_id !== $division->id) {
            abort(422, __('The selected department does not belong to the provided division.'));
        }

        if ($department && ! $division) {
            $division = $department->division;
            $data['division_id'] = $division?->id;
        }

        $team = Team::create([
            'company_id' => $company->getKey(),
            'division_id' => $data['division_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json([
            'team' => [
                'id' => $team->id,
                'division_id' => $team->division_id,
                'department_id' => $team->department_id,
                'name' => $team->name,
                'slug' => $team->slug,
                'description' => $team->description,
                'sort_order' => $team->sort_order,
                'is_active' => (bool) $team->is_active,
            ],
        ], 201);
    }

    /**
     * Update the specified team.
     */
    public function update(UpdateTeamRequest $request, Company $company, Team $team): JsonResponse
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
            abort(422, __('The selected department does not belong to the provided division.'));
        }

        if ($department && ! $division) {
            $division = $department->division;
            $data['division_id'] = $division?->id;
        }

        $team->update($data);

        return response()->json([
            'team' => [
                'id' => $team->id,
                'division_id' => $team->division_id,
                'department_id' => $team->department_id,
                'name' => $team->name,
                'slug' => $team->slug,
                'description' => $team->description,
                'sort_order' => $team->sort_order,
                'is_active' => (bool) $team->is_active,
            ],
        ]);
    }

    /**
     * Remove the specified team.
     */
    public function destroy(Company $company, Team $team): JsonResponse
    {
        if ($team->company_id !== $company->getKey()) {
            abort(404);
        }

        $this->authorize('delete', $team);

        if ($team->users()->exists()) {
            abort(422, __('Cannot delete team with associated members. Please migrate members first.'));
        }

        $team->delete();

        return response()->json([], 204);
    }
}
