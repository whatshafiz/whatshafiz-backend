<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

class WhatsappMessengerNumber extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime:d-m-Y H:i',
        'updated_at' => 'datetime:d-m-Y H:i',
        'deleted_at' => 'datetime:d-m-Y H:i',
        'last_activity_at' => 'datetime:d-m-Y H:i:s',
    ];
    protected $appends = ['screenshots'];

    /**
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->last_activity_at = Carbon::now();
        });
    }

    /**
     * @return array
     */
    public function getScreenshotsAttribute(): array
    {
        return Storage::allFiles('/whatsmessenger/screenshots/' . $this->instance_id);
    }
}
