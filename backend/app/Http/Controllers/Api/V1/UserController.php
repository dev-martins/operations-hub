<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return UserResource::collection(
            User::query()
                ->where('tenant_id', $request->user()->tenant_id)
                ->orderBy('name')
                ->get()
        );
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user): UserResource
    {
        abort_unless($user->tenant_id === $request->user()->tenant_id, Response::HTTP_NOT_FOUND);

        if ($request->user()->is($user) && $request->string('role')->toString() !== $user->role) {
            throw ValidationException::withMessages([
                'role' => 'O próprio papel não pode ser alterado nesta etapa.',
            ]);
        }

        $user->forceFill([
            'role' => $request->string('role')->toString(),
        ])->save();

        return new UserResource($user->fresh());
    }
}
