<?php

namespace App\Http\Controllers;

use App\Models\WhatsappMessengerNumber;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WhatsappMessengerNumberController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $whatsappMessengerNumbers = WhatsappMessengerNumber::where('is_active', true)->latest()->get()->toArray();

        return response()->json($whatsappMessengerNumbers);
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $numberData = $this->validate(
            $request,
            [
                'instance_id' => 'required|string|min:1|max:50',
                'phone_number' => 'nullable|string|min:5|max:50',
            ]
        );

        if (!empty($numberData['phone_number'])) {
            $phoneNumberActiveInstance = WhatsappMessengerNumber::where('phone_number', $numberData['phone_number'])
                ->whereNot('instance_id', $numberData['instance_id'])
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $numberData['qrcode_url'] = 'whatsmessenger/login_qrs/' . $numberData['instance_id'] . '.png';
        $numberData['screenshots_path'] = 'whatsmessenger/screenshots/' . $numberData['instance_id'];
        $numberData['is_active'] = true;

        $whatsappMessengerNumber = WhatsappMessengerNumber::updateOrCreate(
            ['instance_id' => $numberData['instance_id']],
            $numberData
        );

        return response()->json($whatsappMessengerNumber->toArray());
    }

    /**
     * @return JsonResponse
     */
    public function sendTestMessage(): JsonResponse
    {
        $user = Auth::user();
        $testMessage = 'Merhaba ' . $user->name . ' ' . $user->surname . ', Bu bir test mesajıdır.' .
            'Tarih ve saat şuan: ' . Carbon::now()->format('d-m-Y H:i:s');

        $user->sendMessage($testMessage);

        return response()->json(['message' => 'Mesaj gönderildi.']);
    }
}
