<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserHandleRequest;
use App\Models\User;
use App\Support\ReservedHandles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserHandleController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/handle', [
            'currentHandle' => $user->handle,
        ]);
    }

    public function update(UpdateUserHandleRequest $request): RedirectResponse
    {
        $user = $request->user();
        $handle = (string) $request->validated('handle');

        $user->forceFill(['handle' => $handle])->save();

        return redirect()->route('settings.handle.show')
            ->with('success', "代號已更新為「{$handle}」。");
    }

    public function checkAvailability(Request $request): JsonResponse
    {
        $handle = strtolower(trim((string) $request->input('handle', '')));

        if ($handle === '') {
            return response()->json(['available' => false, 'reason' => 'invalid']);
        }

        if (preg_match('/^[a-z0-9_-]{3,30}$/', $handle) !== 1) {
            return response()->json(['available' => false, 'reason' => 'invalid']);
        }

        if (ReservedHandles::isReserved($handle)) {
            return response()->json(['available' => false, 'reason' => 'reserved']);
        }

        $exists = User::query()
            ->where('handle', $handle)
            ->where('id', '!=', $request->user()->id)
            ->exists();

        if ($exists) {
            return response()->json(['available' => false, 'reason' => 'taken']);
        }

        return response()->json(['available' => true]);
    }
}
