<?php

namespace BugCompanion\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;

class DebugContextController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'max:25'],
            'ids.*' => ['required', 'uuid', 'distinct'],
        ]);

        $contexts = collect($validated['ids'])->mapWithKeys(
            fn (string $id): array => [$id => Cache::get("bug-companion:ctx:{$id}")],
        );

        return response()->json(['contexts' => $contexts]);
    }
}
