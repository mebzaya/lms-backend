<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Relations to eager-load for the "my profile" view, so the page can
     * show role-specific context (class, subjects taught, children) with
     * no extra requests.
     *
     * @var array<int, string>
     */
    protected const PROFILE_RELATIONS = [
        'enrolledClass:id,name,level',
        'subjectsTaught:id,name,class_id',
        'subjectsTaught.schoolClass:id,name,level',
        'children:id,name,email,class_id',
        'children.enrolledClass:id,name,level',
        'parents:id,name,email',
    ];

    /**
     * Authenticate a user and return a JWT. Accounts (and their passwords)
     * are created by an admin — there is no public self-registration.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $credentials = $request->only('email', 'password');
        $token=Auth::guard('api')->attempt($credentials);
        if (! $token ) {
            return response()->json([
                'message' => 'Please enter correct username and password.',
            ], 401);
        }

        return $this->respondWithToken($token, Auth::guard('api')->user());
    }

    /**
     * Get the authenticated user, with role-specific profile context.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()->load(self::PROFILE_RELATIONS),
        ]);
    }

    /**
     * Update the authenticated user's own profile (currently just their name
     * — email/role/etc. stay admin-managed).
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $user->name = $request->input('name');
        $user->save();

        return response()->json(['user' => $user->fresh(self::PROFILE_RELATIONS)]);
    }

    /**
     * Change the authenticated user's own password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return response()->json([
                'message' => 'Your current password is incorrect.',
                'errors' => ['current_password' => ['Your current password is incorrect.']],
            ], 422);
        }

        $user->password = $request->input('password');
        $user->save();

        return response()->json(['message' => 'Password updated successfully.']);
    }

    /**
     * Invalidate the current token (log out).
     */
    public function logout(): JsonResponse
    {
        Auth::guard('api')->logout();

        return response()->json([
            'message' => 'Successfully logged out.',
        ]);
    }

    /**
     * Exchange a token for a fresh one — accepts a token that has already
     * expired, as long as it's within the (much longer) refresh window, so
     * a still-active session never gets treated as logged out just because
     * the short-lived access token ran out mid-use.
     */
    public function refresh(): JsonResponse
    {
        $newToken = Auth::guard('api')->refresh();

        // Auth::guard('api')->user() re-resolves from the ORIGINAL request's
        // (now-invalidated) token and returns null — the new token has to be
        // looked up explicitly to find who it belongs to.
        $user = JWTAuth::setToken($newToken)->toUser();

        return $this->respondWithToken($newToken, $user);
    }

    /**
     * Build a token response.
     */
    protected function respondWithToken(string $token, User $user, int $status = 200): JsonResponse
    {
        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ], $status);
    }
}
