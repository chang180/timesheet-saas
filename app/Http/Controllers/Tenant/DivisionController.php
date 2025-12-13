<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreDivisionRequest;
use App\Http\Requests\Tenant\UpdateDivisionRequest;
use App\Models\Company;
use App\Models\Division;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class DivisionController extends Controller
{
    /**
     * Display a listing of divisions.
     */
    public function index(Company $company): JsonResponse
    {
        $divisions = Division::query()
            ->where('company_id', $company->getKey())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'divisions' => $divisions->map(fn ($division) => [
                'id' => $division->id,
                'name' => $division->name,
                'slug' => $division->slug,
                'description' => $division->description,
                'sort_order' => $division->sort_order,
                'is_active' => (bool) $division->is_active,
            ]),
        ]);
    }

    /**
     * Store a newly created division.
     */
    public function store(StoreDivisionRequest $request, Company $company): JsonResponse
    {
        $data = $request->validated();

        if (! isset($data['slug']) || $data['slug'] === '') {
            $data['slug'] = Str::slug($data['name']);
        }

        $division = Division::create([
            'company_id' => $company->getKey(),
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json([
            'division' => [
                'id' => $division->id,
                'name' => $division->name,
                'slug' => $division->slug,
                'description' => $division->description,
                'sort_order' => $division->sort_order,
                'is_active' => (bool) $division->is_active,
            ],
        ], 201);
    }

    /**
     * Update the specified division.
     */
    public function update(UpdateDivisionRequest $request, Company $company, Division $division): JsonResponse
    {
        if ($division->company_id !== $company->getKey()) {
            abort(404);
        }

        $data = $request->validated();

        if (isset($data['name']) && (! isset($data['slug']) || $data['slug'] === '')) {
            $data['slug'] = Str::slug($data['name']);
        }

        $division->update($data);

        return response()->json([
            'division' => [
                'id' => $division->id,
                'name' => $division->name,
                'slug' => $division->slug,
                'description' => $division->description,
                'sort_order' => $division->sort_order,
                'is_active' => (bool) $division->is_active,
            ],
        ]);
    }

    /**
     * Remove the specified division.
     */
    public function destroy(Company $company, Division $division): JsonResponse
    {
        if ($division->company_id !== $company->getKey()) {
            abort(404);
        }

        $this->authorize('delete', $division);

        if ($division->users()->exists()) {
            abort(422, __('Cannot delete division with associated members. Please migrate members first.'));
        }

        if ($division->departments()->exists()) {
            abort(422, __('Cannot delete division with associated departments. Please delete or migrate departments first.'));
        }

        if ($division->teams()->exists()) {
            abort(422, __('Cannot delete division with associated teams. Please delete or migrate teams first.'));
        }

        $division->delete();

        return response()->json([], 204);
    }
}
