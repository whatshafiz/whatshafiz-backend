<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OpenAI\Laravel\Facades\OpenAI;

class ChatgptQuestion extends BaseModel
{
    use HasFactory;

    /**
     * @return BelongsTo
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @param $page
     * @return string
     */
    public static function questionText($page): string
    {
        return 'Diyanet\'in Kuran\'ın ' . $page . '. sayfasının konusu ile alakalı 5 şıklı, şıklar farklı olacak şekilde bir soru oluştur. Soru json formatın da aşağıdaki şablonda olsun ve sadece json olarak dön.
{
"name" : "konuyu açıklayıcı bir isim",
"question": "soru metni"
"page_number": "sayfa numarası"
"option_1": "data",
"option_2": "data",
"option_3": "data",
"option_4": "data",
"option_5": "data",
"correct_option": "doğru cevap numarası"
}';
    }
}
