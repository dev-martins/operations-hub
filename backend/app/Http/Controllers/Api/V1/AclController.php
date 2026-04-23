<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Acl\AclCatalogue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AclController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user()->load('tenant');

        return response()->json([
            'data' => [
                'current_user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->roleContext(),
                    'permissions' => $user->permissionContexts(),
                    'tenant' => [
                        'id' => $user->tenant?->id,
                        'name' => $user->tenant?->name,
                        'slug' => $user->tenant?->slug,
                    ],
                ],
                'roles' => AclCatalogue::roleDefinitions(),
                'permissions' => AclCatalogue::permissionDefinitions(),
            ],
        ]);
    }
}
