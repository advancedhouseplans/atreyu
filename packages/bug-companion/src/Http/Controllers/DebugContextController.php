<?php

namespace BugCompanion\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;

class DebugContextController extends Controller
{
    public function show(string $ids): JsonResponse
    {
        $out = [];
        foreach (explode('|', $ids) as $id) {
            $out[$id] = Cache::get("bug-companion:ctx:{$id}");
        }

        return response()->json($out);
    }
}
