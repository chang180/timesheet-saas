<?php

namespace App\Http\Controllers\Hq;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hq\StoreCompanyRequest;
use App\Http\Requests\Hq\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HqCompanyController extends Controller
{
    /**
     * Display a paginated listing of all companies.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        $companies = Company::query()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => $companies->map(fn (Company $company) => [
                'id' => $company->id,
                'name' => $company->name,
                'slug' => $company->slug,
                'status' => $company->status,
                'user_limit' => $company->user_limit,
                'current_user_count' => $company->current_user_count,
                'timezone' => $company->timezone,
                'branding' => $company->branding,
                'onboarded_at' => $company->onboarded_at?->toIso8601String(),
                'suspended_at' => $company->suspended_at?->toIso8601String(),
                'created_at' => $company->created_at?->toIso8601String(),
                'updated_at' => $company->updated_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $companies->currentPage(),
                'last_page' => $companies->lastPage(),
                'per_page' => $companies->perPage(),
                'total' => $companies->total(),
            ],
        ]);
    }

    /**
     * Store a newly created company.
     */
    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $data = $request->validated();

        $company = Company::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'status' => $data['status'] ?? 'onboarding',
            'user_limit' => $data['user_limit'] ?? 50,
            'timezone' => $data['timezone'] ?? 'Asia/Taipei',
            'branding' => $data['branding'] ?? null,
        ]);

        CompanySetting::create([
            'company_id' => $company->id,
            'welcome_page' => null,
            'login_ip_whitelist' => [],
        ]);

        if (isset($data['admin_user_id'])) {
            $user = User::find($data['admin_user_id']);
            if ($user) {
                $user->update([
                    'company_id' => $company->id,
                    'role' => 'company_admin',
                ]);
                $company->increment('current_user_count');
            }
        }

        return response()->json([
            'data' => [
                'id' => $company->id,
                'name' => $company->name,
                'slug' => $company->slug,
                'status' => $company->status,
                'user_limit' => $company->user_limit,
                'current_user_count' => $company->current_user_count,
                'timezone' => $company->timezone,
                'branding' => $company->branding,
                'created_at' => $company->created_at?->toIso8601String(),
            ],
            'message' => 'Company created successfully.',
        ], 201);
    }

    /**
     * Display the specified company.
     */
    public function show(Company $company): JsonResponse
    {
        $company->load('settings');

        return response()->json([
            'data' => [
                'id' => $company->id,
                'name' => $company->name,
                'slug' => $company->slug,
                'status' => $company->status,
                'user_limit' => $company->user_limit,
                'current_user_count' => $company->current_user_count,
                'timezone' => $company->timezone,
                'branding' => $company->branding,
                'onboarded_at' => $company->onboarded_at?->toIso8601String(),
                'suspended_at' => $company->suspended_at?->toIso8601String(),
                'created_at' => $company->created_at?->toIso8601String(),
                'updated_at' => $company->updated_at?->toIso8601String(),
                'settings' => $company->settings ? [
                    'welcome_page' => $company->settings->welcome_page,
                    'login_ip_whitelist' => $company->settings->login_ip_whitelist,
                ] : null,
            ],
        ]);
    }

    /**
     * Update the specified company.
     */
    public function update(UpdateCompanyRequest $request, Company $company): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['status']) && $data['status'] === 'suspended' && $company->status !== 'suspended') {
            $data['suspended_at'] = now();
        } elseif (isset($data['status']) && $data['status'] !== 'suspended') {
            $data['suspended_at'] = null;
        }

        $company->update($data);

        return response()->json([
            'data' => [
                'id' => $company->id,
                'name' => $company->name,
                'slug' => $company->slug,
                'status' => $company->status,
                'user_limit' => $company->user_limit,
                'current_user_count' => $company->current_user_count,
                'timezone' => $company->timezone,
                'branding' => $company->branding,
                'suspended_at' => $company->suspended_at?->toIso8601String(),
                'updated_at' => $company->updated_at?->toIso8601String(),
            ],
            'message' => 'Company updated successfully.',
        ]);
    }

    /**
     * Update only the user_limit for the specified company.
     */
    public function updateUserLimit(Request $request, Company $company): JsonResponse
    {
        $request->validate([
            'user_limit' => ['required', 'integer', 'min:1', 'max:65535'],
        ]);

        $company->update([
            'user_limit' => $request->integer('user_limit'),
        ]);

        return response()->json([
            'data' => [
                'id' => $company->id,
                'slug' => $company->slug,
                'user_limit' => $company->user_limit,
                'current_user_count' => $company->current_user_count,
            ],
            'message' => 'User limit updated successfully.',
        ]);
    }
}
