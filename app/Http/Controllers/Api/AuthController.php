<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function register(
        RegisterRequest $request
    ): JsonResponse {
        $data = $request->validated();

        $customerRole = Role::query()
            ->where('name', 'customer')
            ->first();

        if (! $customerRole) {
            return ApiResponse::error(
                message: 'دور الزبون غير مهيأ. تحقق من تشغيل Seeder.',
                status: 500,
            );
        }

        $user = DB::transaction(function () use ($data, $customerRole) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $user->roles()->syncWithoutDetaching([
                $customerRole->id,
            ]);

            return $user;
        });

        $token = $user
            ->createToken('tawseela-api')
            ->plainTextToken;

        return ApiResponse::success(
            message: 'تم إنشاء الحساب بنجاح',
            data: [
                'user' => $this->userData($user),
                'token' => $token,
            ],
            status: 201,
        );
    }

    public function login(
        LoginRequest $request
    ): JsonResponse {
        $data = $request->validated();

        $user = User::query()
            ->where('email', $data['email'])
            ->first();

        if (
            ! $user ||
            ! Hash::check($data['password'], $user->password)
        ) {
            return ApiResponse::error(
                message: 'البريد الإلكتروني أو كلمة المرور غير صحيحة',
                status: 401,
            );
        }

        $token = $user
            ->createToken('tawseela-api')
            ->plainTextToken;

        return ApiResponse::success(
            message: 'تم تسجيل الدخول بنجاح',
            data: [
                'user' => $this->userData($user),
                'token' => $token,
            ],
        );
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(
            message: 'تم جلب بيانات المستخدم بنجاح',
            data: [
                'user' => $this->userData(
                    $request->user()
                ),
            ],
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return ApiResponse::success(
            message: 'تم تسجيل الخروج بنجاح',
        );
    }

    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}
