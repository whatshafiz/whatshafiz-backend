<?php

namespace App\Traits;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

trait S3Trait
{
    /**
     * @param  string  $path
     * @return string
     */
    private function generatePreSignedUrl(string $path): string
    {
        if (App::isLocal()) {
            return $path;
        }

        $client = Storage::disk('s3')->getClient();
        $expire = "+60 minutes";
        $command = $client->getCommand('GetObject', [
            'Bucket' => config('filesystems.disks.s3.bucket'),
            'Key' => $path,
        ]);
        $request = $client->createPresignedRequest($command, $expire);

        return (string)$request->getUri();
    }

    /**
     * @param  string  $path
     * @return string
     */
    private function generateStorageUrl(string $path): string
    {
        if (App::isLocal()) {
            return $path;
        }

        return Storage::disk('s3')->url($path);
    }
}
