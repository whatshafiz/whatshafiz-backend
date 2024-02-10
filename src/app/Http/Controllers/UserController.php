<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\PasswordReset;
use App\Models\Role;
use App\Models\User;
use App\Models\WhatsappGroup;
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
        $this->authorize('viewAny', [User::class, $request]);

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
                'whatsapp_group_id' => 'nullable|integer|min:1|exists:whatsapp_groups,id',
                'course_id' => 'nullable|integer|min:1|exists:courses,id',
                'is_banned' => 'nullable|boolean',
            ]
        );

        $searchKey = $this->getTabulatorSearchKey($request);

        $users = User::when(isset($filters['gender']), function ($query) use ($filters) {
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
            ->when(isset($filters['whatsapp_group_id']), function ($query) use ($filters) {
                return $query->whereHas('whatsappGroups', function ($subQuery) use ($filters) {
                    return $subQuery->where('whatsapp_group_id', $filters['whatsapp_group_id']);
                });
            })
            ->when(isset($filters['course_id']), function ($query) use ($filters) {
                return $query->whereHas('courses', function ($subQuery) use ($filters) {
                    return $subQuery->where('course_id', $filters['course_id']);
                });
            })
            ->when(!empty($searchKey), function ($query) use ($searchKey) {
                return $query->where(function ($subQuery) use ($searchKey) {
                    return $subQuery->where('id', $searchKey)
                        ->orWhere(DB::raw('CONCAT(name, " ", surname)'), 'like', "%{$searchKey}%")
                        ->orWhere('name', 'like', "%{$searchKey}%")
                        ->orWhere('surname', 'like', "%{$searchKey}%")
                        ->orWhere('phone_number', 'like', "%{$searchKey}%")
                        ->orWhere('email', 'like', "%{$searchKey}%");
                });
            })
            ->orderByTabulator($request)
            ->paginate($request->size)
            ->appends(array_merge($this->filters, $filters));

        return response()->json($users->toArray());
    }

    /**
     * @param  User  $user
     * @return JsonResponse
     */
    public function show(User $user): JsonResponse
    {
        $this->authorize('view', [User::class, $user]);

        if (Auth::user()->hasPermissionTo('users.list')) {
            $user->load([
                    'roles',
                    'permissions',
                    'whatsappGroups',
                    'courses',
                    'country',
                    'city',
                    'university',
                    'universityFaculty',
                    'universityDepartment',
                ])
                ->toArray();
        }

        return response()->json(compact('user'));
    }

    /**
     * @return JsonResponse
     */
    public function profile(): JsonResponse
    {
        $user = Auth::user()->load(['country', 'city', 'university', 'universityFaculty', 'universityDepartment']);
        $permissions = $user->permissions()->orderBy('permissions.name')->pluck('permissions.name');
        $roles = $user->roles()->orderBy('roles.name')->pluck('roles.name');
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
                'country_id' => 'required|integer|min:1|exists:countries,id',
                'city_id' => 'required|integer|min:1|exists:cities,id',
                'education_level' => 'required|string|min:1|max:100',
                'university_id' => 'nullable|integer|min:1|exists:universities,id',
                'university_faculty_id' => 'nullable|integer|min:1|exists:university_faculties,id',
                'university_department_id' => 'nullable|integer|min:1|exists:university_departments,id',
            ],
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
            'is_teacher' => 'required_if:type,whatshafiz|boolean',
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
                    'is_teacher' => $request->is_teacher ?? false,
                    'applied_at' => Carbon::now(),
                ]
            );


            if ($course->type === 'whatshafiz') {
                $user->assignRole($request->is_teacher ? 'HafızKal' : 'HafızOl');
            } else {
                $user->assignRole(Str::ucfirst($course->type));
            }

            DB::commit();

            $message = '<br> <strong>Kaydınız başarılı şekilde oluşturuldu.</strong> <br><br> ';

            if ($course->type === 'whatshafiz') {                
                if ($request->is_teacher) {
                    $message .= 'Öğrenci atamalarınız HafızOl Kabul Sınavı tarihinde yapılacaktır. <br><br>' .
                        'Kabul sınavı başlangıç zamanından sonra, bu sisteme giriş yaparak size atanan öğrencileri görebilirsiniz. <br><br>' .
                        'Öğrencileriniz ile iletişime geçerek sınavlarını yapın ve kabul/red olarak işaretleyin. <br><br>' .
                        'Kursa kabul ettiğiniz öğrenciler ile birlikte whatsapp gruplarına kurs başlamadan ekleneceksiniz. <br><br>';
                } else {
                    $message .= 'Hocanızın ataması HafızOl Kabul Sınavı tarihinde yapılacaktır. <br><br>' .
                        'Kabul sınavı başlangıç zamanından sonra, bu sisteme giriş yaparak size atanan hocanızın bilgilerini görebilirsiniz. <br><br>' .
                        'Hocanız sizinle iletişime geçerek sınavınızı yapacak. <br><br>';
                }
            }

            if ($course->whatsapp_channel_join_url) {
                $user->sendMessage(
                    'Aşağıdaki linki kullanarak *' . $course->type .
                        '* kursu için whatsapp duyuru kanalına katılın ve buradan duyuruları takip edin. ↘️ ' .
                        $course->whatsapp_channel_join_url
                );

                $message .= '<br><br>' .
                    'Lütfen aşağıdaki <strong>Whatsapp Duyuru Kanalına Katıl</strong> butonunu kullanarak whatsapp duyuru kanalına katılın. <br><br>' .
                    'Kurs ile ilgili tüm duyurular bu whatsapp kanalı üzerinden yapılacaktır. Lütfen duyuruları takip edin. <br><br><br>' .
                    '<i>Bu buton ile katılım sağlayamazsanız, kanala katılmak için gerekli link size whatsapp üzerinden de gönderilecek.</i> <br><br>' .
                    '<i>Lütfen gelen mesajı <strong>SPAM DEĞİL</strong> veya <strong>TAMAM</strong> olarak işaretleyin.</i> <br><br>' .
                    '<i>Eğer gelen linke tıklayamıyorsanız mesaj gelen numarayı Kişilere Ekleyin</i> <br>';
            }

            return response()->json([
                'message' => $message,
                'whatsapp_channel_join_url' => ($course->whatsapp_channel_join_url ?? null),
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

    /**
     * @param  Request  $request
     * @param  User  $user
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function assignRole(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', User::class);

        $request->validate(['role_id' => 'required|integer|min:1|exists:roles,id']);

        $user->roles()->attach($request->role_id);

        return response()->json(null, Response::HTTP_CREATED);
    }

    /**
     * @param  Request  $request
     * @param  User  $user
     * @param  Role  $role
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function removeRole(Request $request, User $user, Role $role): JsonResponse
    {
        $this->authorize('update', User::class);

        $user->roles()->detach($role);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param  Request  $request
     * @param  User  $user
     * @param  Course  $course
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function removeCourse(Request $request, User $user, Course $course): JsonResponse
    {
        $this->authorize('update', User::class);

        $user->courses()->detach($course);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param  Request  $request
     * @param  User  $user
     * @param  WhatsappGroup  $whatsappGroup
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function removeWhatsappGroup(Request $request, User $user, WhatsappGroup $whatsappGroup): JsonResponse
    {
        $this->authorize('update', User::class);

        $user->whatsappGroups()->detach($whatsappGroup);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
