<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function profile(): JsonResponse
    {
        $user = Auth::user()->load(['country', 'city', 'university', 'universityFaculty', 'universityDepartment']);
        $permissions = $user->permissions()->orderBy('name')->pluck('name');
        $roles = $user->roles()->orderBy('name')->pluck('name');
        unset($user->permissions);
        unset($user->roles);

        return response()->json(compact('user', 'permissions', 'roles'));
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function saveProfile(Request $request): JsonResponse
    {
        $validatedUserData = $this->validate(
            $request,
            [
                'name' => 'required|string|max:50',
                'surname' => 'required|string|max:50',
                'email' => 'nullable|email|unique:users,email,' . Auth::id(),
                'gender' => 'required|string|in:male,female',
                'country_id' => 'nullable|integer|min:1|exists:countries,id',
                'city_id' => 'nullable|integer|min:1|exists:cities,id',
                'university_id' => 'nullable|integer|min:1|exists:universities,id',
                'university_faculty_id' => 'nullable|integer|min:1|exists:university_faculties,id',
                'university_department_id' => 'nullable|integer|min:1|exists:university_departments,id',
            ]
        );

        Auth::user()->update($validatedUserData);

        return $this->profile();
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function check(Request $request): JsonResponse
    {
        $request->validate(['phone_number' => 'required|string|min:7|max:30']);

        $user = User::where('phone_number', $request->phone_number)->first();

        return response()->json([
            'phone_number' => $request->phone_number,
            'is_registered' => (bool) $user,
            'is_banned' => $user && $user->is_banned,
        ]);
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'phone_number' => [
                'required',
                'string',
                'min:7',
                'max:30',
                function ($attribute, $phone_number, $fail) {
                    if (User::where('phone_number', $phone_number)->where('is_banned', true)->exists()) {
                        $fail($phone_number . ' telefon numarası sistemde engellenmiştir.');
                    }
                },
                'unique:users,phone_number',
            ],
            'password' => 'required|string|min:5|confirmed',
        ]);

        $user = User::create([
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'token' => $user->newToken(),
            'profile' => $user->toArray()
        ]);
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'phone_number' => [
                'required',
                'string',
                'min:7',
                'max:30',
                function ($attribute, $phone_number, $fail) {
                    if (User::where('phone_number', $phone_number)->where('is_banned', true)->exists()) {
                        $fail($phone_number . ' telefon numarası sistemde engellenmiştir.');
                    }
                },
                'exists:users,phone_number',
            ],
            'password' => 'required|string|min:5',
        ]);


        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            return response()->json([
                'token' => $user->newToken(),
                'profile' => $user->toArray()
            ]);
        }

        return response()->json(['message' => 'Telefon No veya Parola Hatalı'], Response::HTTP_BAD_REQUEST);
    }
}
