<?php
declare(strict_types=1);

namespace App\Services;

class ReleaseInfoProvider
{
    /**
     * @return array<string, string>
     */
    public function fetchLatestRelease(): array
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

        $versions = array_column($response['packages']['shopware/core'], 'version');

        // Index them by major version
        $mappedVersions = [];

        foreach ($versions as $version) {
            if (str_contains($version, 'dev-') || str_contains($version, 'alpha') || str_contains($version, 'beta') || str_contains($version, 'rc')) {
                continue;
            }

            $major = substr($version, 0, 3);

            if (isset($mappedVersions[$major])) {
                continue;
            }

            $mappedVersions[$major] = $version;
        }

        return $mappedVersions;
    }
}
