<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Acl\Permission;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null || ! $user->hasPermission(Permission::from($permission))) {
            return new JsonResponse([
                'message' => 'Usuário sem permissão para executar esta ação.',
                'required_permission' => $permission,
            ], 403);
        }

        return $next($request);
    }
}
