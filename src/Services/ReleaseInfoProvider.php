<?php
declare(strict_types=1);

namespace App\Services;

class ReleaseInfoProvider
{
    public function fetchLatestRelease(): string
    {
        $ch = curl_init('https://repo.packagist.org/p2/shopware/core.json');
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, \CURLOPT_HTTPHEADER, [
            'User-Agent: Shopware Recovery',
        ]);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new \RuntimeException('Error: "'.curl_error($ch).'" - Code: '.curl_errno($ch));
        }

        curl_close($ch);

        $result = json_decode($result, true);

        foreach ($result['packages']['shopware/core'] as $version) {
            return $version['version'];
        }

        throw new \RuntimeException('Could not find latest version');
    }
}
