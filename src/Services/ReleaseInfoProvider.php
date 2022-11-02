<?php

namespace App\Services;

class ReleaseInfoProvider
{
    public function fetchLatestRelease(): string
    {
        $ch = curl_init('https://api.github.com/repos/shopware/platform/releases/latest');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: Shopware Recovery'
        ]);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new \RuntimeException('Error: "' . curl_error($ch) . '" - Code: ' . curl_errno($ch));
        }

        curl_close($ch);

        $result = json_decode($result, true);

        return ltrim($result['tag_name'], 'v');
    }
}
