<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class MemberApprovalController extends Controller
{
    public function store(Company $company, User $member): Response
    {
        abort(404, __('Member approval workflow is not yet available.'));
    }
}
