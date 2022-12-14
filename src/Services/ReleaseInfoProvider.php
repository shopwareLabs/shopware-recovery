<?php
declare(strict_types=1);

namespace App\Services;

class ReleaseInfoProvider
{
    public function fetchLatestRelease(): string
    {
        $ch = curl_init('https://repo.packagist.org/p2/shopware/core.json');
        \assert($ch instanceof \CurlHandle);
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, \CURLOPT_HTTPHEADER, [
            'User-Agent: Shopware Recovery',
        ]);

        $result = (string) curl_exec($ch);

        if (curl_errno($ch)) {
            throw new \RuntimeException('Error: "'.curl_error($ch).'" - Code: '.curl_errno($ch));
        }

        curl_close($ch);

        /** @var array{packages: array{"shopware/core": array{version: string}[]}} $response */
        $response = json_decode($result, true, JSON_THROW_ON_ERROR);

        foreach ($response['packages']['shopware/core'] as $version) {
            return $version['version'];
        }

        throw new \RuntimeException('Could not find latest version');
    }
}
