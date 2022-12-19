<?php
declare(strict_types=1);

namespace App\Services;

use Symfony\Component\HttpFoundation\Request;

class RecoveryManager
{
    public function getBinary(): string
    {
        /** @var string $fileName */
        $fileName = $_SERVER['SCRIPT_FILENAME'];

        return $fileName;
    }

    public function getPHPBinary(Request $request): string
    {
        $phpBinary = $request->getSession()->get('phpBinary');
        \assert(\is_string($phpBinary));

        return $phpBinary;
    }

    public function getProjectDir(): string
    {
        /** @var string $fileName */
        $fileName = $_SERVER['SCRIPT_FILENAME'];

        return \dirname($fileName);
    }

    public function getShopwareLocation(): string
    {
        $projectDir = $this->getProjectDir();

        $composerLookups = [
            \dirname($projectDir).'/composer.json',
            $projectDir.'/composer.json',
            $projectDir.'/shopware/composer.json',
        ];

        foreach ($composerLookups as $composerLookup) {
            if (file_exists($composerLookup)) {
                /** @var array{require: array<string, string>} $composerJson */
                $composerJson = json_decode((string) file_get_contents($composerLookup), true, \JSON_THROW_ON_ERROR);

                if (isset($composerJson['require']['shopware/core']) || isset($composerJson['require']['shopware/platform'])) {
                    return \dirname($composerLookup);
                }
            }
        }

        throw new \RuntimeException('Could not find Shopware installation');
    }

    public function getCurrentShopwareVersion(string $shopwarePath): string
    {
        $lockFile = $shopwarePath.'/composer.lock';

        if (!file_exists($lockFile)) {
            return 'unknown';
        }

        /** @var array{packages: array{name: string, version: string}[]} $composerLock */
        $composerLock = json_decode((string) file_get_contents($lockFile), true, \JSON_THROW_ON_ERROR);

        foreach ($composerLock['packages'] as $package) {
            if ('shopware/core' === $package['name'] || 'shopware/platform' === $package['name']) {
                return $package['version'];
            }
        }

        return 'unknown';
    }

    public function isFlexProject(string $shopwarePath): bool
    {
        return file_exists($shopwarePath.'/symfony.lock');
    }
}
