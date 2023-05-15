<?php

namespace App\Models;

use App\Traits\S3Trait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

class WhatsappMessengerNumber extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use S3Trait;

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
     * @return string
     */
    public function getQrcodeUrlAttribute(): string
    {
        return $this->generatePreSignedUrl($this->attributes['qrcode_url']);
    }

    /**
     * @return array
     */
    public function getScreenshotsAttribute(): array
    {
        $screenshots = Storage::allFiles('/whatsmessenger/screenshots/' . $this->instance_id);

        if (empty($screenshots)) {
            return [];
        }

        rsort($screenshots);
        $screenshots = array_slice($screenshots, 0, 10);

        foreach ($screenshots as $key => $screenshot) {
            $screenshots[$key] = $this->generatePreSignedUrl($screenshot);
        }

        return $screenshots;
    }
}
