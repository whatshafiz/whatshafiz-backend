<?php

namespace App\Console\Commands;

use App\Factories\SnsClientFactory;
use App\Models\WhatsappMessengerNumber;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckWhatsappMessengerNumbers extends Command
{
    const MAX_DURATION_FOR_ALERT = 5;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:whatsapp-messenger-numbers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'It checks the whatsapp messenger numbers and send a SNS alert';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(SnsClientFactory $snsClientFactory): int
    {
        $notUpdatedNumbers = WhatsappMessengerNumber::where('is_active', true)
            ->where(
                'last_activity_at',
                '<=',
                Carbon::now()->subMinutes(self::MAX_DURATION_FOR_ALERT)->format('Y-m-d H:i:s')
            )
            ->get();

        if ($notUpdatedNumbers->count() > 0) {
            $snsClientFactory->create()
                ->publish([
                    'TopicArn' => config('services.sns.whatsapp-messenger-alert-topic-arn'),
                    'Message' => 'En az 5 dk\'dır aktif olmayan whatsapp messenger numaraları: ' .
                        implode(', ', $notUpdatedNumbers->pluck('phone_number')->toArray()),
                    'Subject' => 'Bazı Whatsapp Messenger Numaraları Aktif Değil',
                ]);
        }

        return Command::SUCCESS;
    }
}
