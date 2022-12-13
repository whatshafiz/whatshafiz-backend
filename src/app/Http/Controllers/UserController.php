<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
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

    /**
     * @return JsonResponse
     */
    public function sendVerificationCode(): JsonResponse
    {
        $user = Auth::user();
        $verificaitonCodeValidDuration = 3;

        if (!is_null($user->phone_number_verified_at)) {
            return response()->json(['message' => 'Telefon No daha önce doğrulanmış'], Response::HTTP_BAD_REQUEST);
        }

        if ($user->verification_code_valid_until && Carbon::now()->lessThan($user->verification_code_valid_until)) {
            return response()->json(
                ['message' => $verificaitonCodeValidDuration . ' dakika içinde bir kere kod isteyebilirsiniz.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $user->verification_code = random_int(100000, 999999);
        $user->verification_code_valid_until = Carbon::now()->addMinutes($verificaitonCodeValidDuration);
        $user->save();

        Queue::connection('messenger-sqs')
            ->pushRaw(json_encode([
                'phone' => $user->phone_number,
                'text' => 'Whats eğitim modeli, kayıt için doğrulama kodunuz: ' . $user->verification_code,
            ]));

        return response()->json([
            'message' => 'Doğrulama kodu whatsapp ile telefonunuza gönderildi.',
            'verification_code_valid_until' => $user->verification_code_valid_until->format('d-m-Y H:i:s'),
        ]);
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function verifyVerificationCode(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|integer|min:100000|max:999999']);
        $user = Auth::user();

        if (!is_null($user->phone_number_verified_at)) {
            return response()->json(
                ['message' => 'Telefon numaranız daha önceden doğrulanmış.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!$user->verification_code ||
            !$user->verification_code_valid_until ||
            Carbon::now()->greaterThan($user->verification_code_valid_until) ||
            $request->code !== $user->verification_code
        ) {
            return response()->json(
                ['message' => 'Doğrulama kodu geçerli değil, lütfen tekrar deneyin.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $user->phone_number_verified_at = Carbon::now();
        $user->verification_code = null;
        $user->verification_code_valid_until = null;
        $user->save();

        return response()->json(['message' => 'Telefon numaranız başarılı şekilde doğrulandı.']);
    }
}
