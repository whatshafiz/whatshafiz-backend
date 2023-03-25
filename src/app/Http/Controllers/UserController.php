<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\PasswordReset;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    /**
     * @param  Request  $request
     * @return JsonResponse
     * @throws AuthorizationException|ValidationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $filters = $this->validate(
            $request,
            [
                'full_name' => 'nullable|string|max:150',
                'name' => 'nullable|string|max:50',
                'surname' => 'nullable|string|max:50',
                'email' => 'nullable|email',
                'gender' => 'nullable|string|in:male,female',
                'country_id' => 'nullable|integer|min:1|exists:countries,id',
                'city_id' => 'nullable|integer|min:1|exists:cities,id',
                'university_id' => 'nullable|integer|min:1|exists:universities,id',
                'university_faculty_id' => 'nullable|integer|min:1|exists:university_faculties,id',
                'university_department_id' => 'nullable|integer|min:1|exists:university_departments,id',
                'is_banned' => 'nullable|boolean',
            ]
        );

        $users = User::latest()
            ->when(isset($filters['full_name']), function ($query) use ($filters) {
                return $query->where(DB::raw('CONCAT(name, " ", surname)'), 'like', "%{$filters['full_name']}%");
            })
            ->when(isset($filters['name']), function ($query) use ($filters) {
                return $query->where('name', 'like', "%{$filters['name']}%");
            })
            ->when(isset($filters['surname']), function ($query) use ($filters) {
                return $query->where('surname', 'like', "%{$filters['surname']}%");
            })
            ->when(isset($filters['email']), function ($query) use ($filters) {
                return $query->where('email', 'like', "%{$filters['email']}%");
            })
            ->when(isset($filters['gender']), function ($query) use ($filters) {
                return $query->where('gender', $filters['gender']);
            })
            ->when(isset($filters['country_id']), function ($query) use ($filters) {
                return $query->where('country_id', $filters['country_id']);
            })
            ->when(isset($filters['city_id']), function ($query) use ($filters) {
                return $query->where('city_id', $filters['city_id']);
            })
            ->when(isset($filters['university_id']), function ($query) use ($filters) {
                return $query->where('university_id', $filters['university_id']);
            })
            ->when(isset($filters['university_faculty_id']), function ($query) use ($filters) {
                return $query->where('university_faculty_id', $filters['university_faculty_id']);
            })
            ->when(isset($filters['university_department_id']), function ($query) use ($filters) {
                return $query->where('university_department_id', $filters['university_department_id']);
            })
            ->when(isset($filters['is_banned']), function ($query) use ($filters) {
                return $query->where('is_banned', $filters['is_banned']);
            })
            ->paginate()
            ->appends($filters)
            ->toArray();

        return response()->json(compact('users'));
    }

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
     * @throws ValidationException
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

        $user->sendMessage('Whats eğitim modeli, kayıt için doğrulama kodunuz: ' . $user->verification_code);

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

        if (
            !$user->verification_code ||
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

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function saveCourse(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:whatshafiz,whatsenglish,whatsarapp',
            'is_teacher' => 'required|boolean',
        ]);

        $user = Auth::user();
        $course = Course::where('type', $request->type)->active()->available()->first();

        if (!$course) {
            return response()->json(
                ['message' => "Şuan {$request->type} için başvuruya açık dönem bulunmuyor."],
                Response::HTTP_BAD_REQUEST
            );
        }

        if ($user->courses()->where('course_id', $course->id)->exists()) {
            return response()->json(
                ['message' => 'Daha önceden başvuru yapmışsınız.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if ($user->courses()->active()->where('courses.type', $course->type)->exists()) {
            return response()->json(
                ['message' => 'Başvuru yaptığınız kurs tipinde zaten kaydınız var.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        DB::beginTransaction();

        try {
            $user->courses()->attach(
                $course->id,
                [
                    'type' => $course->type,
                    'is_teacher' => $request->is_teacher,
                    'applied_at' => Carbon::now(),
                ]
            );

            if ($course->type === 'whatshafiz') {
                $user->assignRole($request->is_teacher ? 'HafızKal' : 'HafızOl');
                DB::commit();

                return response()->json(['message' => 'Kaydınız başarılı şekilde oluşturuldu.']);
            }

            $user->assignRole(Str::ucfirst($course->type));
            $whatsappGroups = $course->whatsappGroups()
                ->where('gender', $user->gender)
                ->where('is_active', true)
                ->withCount('users')
                ->orderBy('users_count')
                ->take(3)
                ->get();

            if ($whatsappGroups->count() > 0) {
                $assignedWhatsappGroup = $whatsappGroups->random();
                $assignedWhatsappGroup->users()
                    ->create([
                        'user_id' => $user->id,
                        'joined_at' => Carbon::now(),
                        'role_type' => null,
                    ]);

                $user->sendMessage(
                    'Aşağıdaki linki kullanarak *' . $course->type .
                        '* kursu için atandığınız whatsapp grubuna katılın. ↘️ ' . $assignedWhatsappGroup->join_url
                );
            }

            DB::commit();

            return response()->json([
                'message' => 'Kaydınız başarılı şekilde oluşturuldu. ' .
                    'Whatsapp grubuna katılmak için gerekli link size whatsapp üzerinden gönderilecek.',
            ]);
        } catch (Exception $exception) {
            DB::rollback();

            throw $exception;
        }
    }

    /**
     * @return JsonResponse
     */
    public function getUserCourses(): JsonResponse
    {
        $userCourses = Auth::user()->courses()->get();

        return response()->json(compact('userCourses'));
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['phone_number' => 'required|string|min:7|max:30|exists:users,phone_number']);

        $user = User::where('phone_number', $request->phone_number)->first();

        if ($user->passwordResetCode()->valid()->exists()) {
            return response()->json(
                ['message' => PasswordReset::TOKEN_LIFETIME_IN_MINUTE . ' dakika içinde bir kere kod isteyebilirsiniz'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $passwordResetCode = random_int(100000, 999999);
        $passwordResetCodeCreatedAt = Carbon::now();
        PasswordReset::updateOrCreate(
            [
                'phone_number' => $user->phone_number
            ],
            ['token' => Hash::make($passwordResetCode), 'created_at' => $passwordResetCodeCreatedAt]
        );

        $user->sendMessage($passwordResetCode . ' doğrulama kodunu kullanarak parolanızı değiştirebilirsiniz.');

        return response()->json([
            'message' => 'Doğrulama kodu whatsapp ile telefonunuza gönderildi.',
            'password_reset_code_valid_until' =>
            $passwordResetCodeCreatedAt->addMinutes(PasswordReset::TOKEN_LIFETIME_IN_MINUTE)->format('d-m-Y H:i:s'),
        ]);
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'phone_number' => 'required|string|min:7|max:30|exists:users,phone_number',
            'verification_code' => 'required|integer',
            'password' => 'required|string|min:5|confirmed',
        ]);

        $user = User::where('phone_number', $request->phone_number)->first();
        $passwordResetCode = $user->passwordResetCode()->valid()->first();

        if (!$passwordResetCode || !Hash::check($request->verification_code, $passwordResetCode->token)) {
            return response()->json(
                ['message' => 'Kod hatalı veya süresi dolmuş, lütfen tekrar deneyin.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'message' => 'Parolanız başarılı bir şekilde değiştirildi.Yeni parolanızı kullanarak giriş yapabilirsiniz.',
        ]);
    }

    /**
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function banUser(Request $request, User $user): JsonResponse
    {
        $this->authorize('delete', User::class);

        $request->validate(['is_banned' => 'required|boolean']);

        $user->is_banned = $request->is_banned;
        $user->save();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
