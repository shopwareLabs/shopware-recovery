<?php
declare(strict_types=1);

namespace App\Services;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ReleaseInfoProvider
{
    private HttpClientInterface $client;

    public function __construct(?HttpClientInterface $client = null)
    {
        $this->client = $client ?? HttpClient::create();
    }

    /**
     * @return array<string, string>
     */
    public function fetchLatestRelease(): array
    {
        /** @var array{packages: array{"shopware/core": array{version: string}[]}} $response */
        $response = $this->client->request('GET', 'https://repo.packagist.org/p2/shopware/core.json')->toArray();

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
