<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'verification_code_valid_until' => 'datetime:d-m-Y H:i:s',
        'phone_number_verified_at' => 'datetime:d-m-Y H:i',
        'created_at' => 'datetime:d-m-Y H:i',
        'updated_at' => 'datetime:d-m-Y H:i',
        'deleted_at' => 'datetime:d-m-Y H:i',
    ];

    /**
     * @return string
     */
    public function newToken(): string
    {
        return $this->createToken('jwt')->plainTextToken;
    }

    /**
     * @param  string  $message
     * @return void
     */
    public function sendMessage(string $message): void
    {
        Queue::connection('messenger-sqs')->pushRaw(json_encode(['phone' => $this->phone_number, 'text' => $message]));
    }

    /**
     * @return BelongsTo
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return BelongsTo
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return BelongsTo
     */
    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    /**
     * @return BelongsTo
     */
    public function universityFaculty(): BelongsTo
    {
        return $this->belongsTo(UniversityFaculty::class);
    }

    /**
     * @return BelongsTo
     */
    public function universityDepartment(): BelongsTo
    {
        return $this->belongsTo(UniversityDepartment::class);
    }

    /**
     * @return BelongsToMany
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'user_course');
    }

    /**
     * @return HasOne
     */
    public function passwordResetCode(): HasOne
    {
        return $this->hasOne(PasswordReset::class, 'phone_number', 'phone_number');
    }
}
