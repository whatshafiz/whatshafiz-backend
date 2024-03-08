<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    public static $actionKeys = [
        '*' => 'Tam Yetkili',
        'view' => 'Görüntüleme',
        'list' => 'Tümünü Görüntüleme',
        'update' => 'Düzenleme',
        'create' => 'Ekleme',
        'delete' => 'Silme',
        'user-view' => 'user-view',
        'user-update' => 'user-update',
    ];

    public static $resources = [
        'whatsappGroups' => 'Whatsapp Grubu',
        'users' => 'Kullanıcı',
        'roles' => 'Rol',
        'regulations' => 'Yönetmelik',
        'courses' => 'Kurs',
        'complaints' => 'Şikayet',
        'comments' => 'Kullanıcı Yorumu',
        'countries' => 'Ülke',
        'universities' => 'Üniversite',
        'permissions' => 'Rol Yetkisi',
        'quranQuestions' => 'Meal Sorusu',
        'answerAttempts' => 'Meal Sorusu Yanıtı',
    ];

    /**
     * @param  string  $permissionName
     * @return string
     */
    public static function generateLabel(string $permissionName): string
    {
        [$resource, $action] = explode('.', $permissionName);

        return self::$resources[$resource] . ' ' . self::$actionKeys[$action];
    }
}
