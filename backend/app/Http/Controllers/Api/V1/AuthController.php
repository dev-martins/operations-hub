<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\AuthUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()
            ->with('tenant')
            ->where('email', $request->string('email'))
            ->first();

        if ($user === null || ! Hash::check($request->string('password')->toString(), $user->password)) {
            return response()->json([
                'message' => 'Credenciais inválidas.',
                'errors' => [
                    'email' => ['As credenciais informadas não são válidas.'],
                ],
            ], 422);
        }

        if ($user->tenant === null || ! $user->tenant->active) {
            return response()->json([
                'message' => 'Usuário sem tenant ativo para autenticação.',
            ], 403);
        }

        $token = $user->createToken('operations-hub-web')->accessToken;

        return response()->json([
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => AuthUserResource::make($user)->resolve(),
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user()->load('tenant');

        return response()->json([
            'data' => AuthUserResource::make($user)->resolve(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $token = $user->token();

        if ($token !== null) {
            $token->revoke();
        }

        return response()->json([
            'message' => 'Sessão encerrada com sucesso.',
        ]);
    }
}
