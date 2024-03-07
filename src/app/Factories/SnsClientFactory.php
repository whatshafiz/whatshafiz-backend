<?php

namespace App\Factories;

use Aws\Credentials\Credentials;
use Aws\Sns\SnsClient;

class SnsClientFactory
{
    /**
     * @return SnsClient
     */
    public static function create(): SnsClient
    {
        return new SnsClient([
            'version' => '2010-03-31',
            'region' => config('services.sns.region'),
            'credentials' => new Credentials(config('services.sns.key'), config('services.sns.secret'))
        ]);
    }
}
