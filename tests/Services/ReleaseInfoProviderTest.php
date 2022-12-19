<?php

namespace App\Tests\Services;

use App\Services\ReleaseInfoProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @covers \App\Services\ReleaseInfoProvider
 */
class ReleaseInfoProviderTest extends TestCase
{
    public function testGetReleaseInfo(): void
    {
        $mockClient = new MockHttpClient([
            new MockResponse(json_encode([
                'packages' => [
                    'shopware/core' => [
                        [
                            'version' => 'dev-trunk',
                        ],
                        [
                            'version' => '6.4.12.0',
                        ],
                        [
                            'version' => '6.4.11.0',
                        ],
                        [
                            'version' => '6.3.5.0',
                        ],
                    ]
                ]
            ], JSON_THROW_ON_ERROR)),
        ]);

        $releaseInfoProvider = new ReleaseInfoProvider($mockClient);

        $releaseInfo = $releaseInfoProvider->fetchLatestRelease();

        static::assertArrayHasKey('6.3', $releaseInfo);
        static::assertArrayHasKey('6.4', $releaseInfo);
        static::assertSame('6.4.12.0', $releaseInfo['6.4']);
        static::assertSame('6.3.5.0', $releaseInfo['6.3']);
    }
}
