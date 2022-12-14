<?php
declare(strict_types=1);

namespace App\Services;

class RecoveryManager
{
    public function getProjectDir(): string
    {
        return \dirname($_SERVER['SCRIPT_FILENAME']);
    }

    public function getShopwareLocation(): string|bool
    {
        $projectDir = $this->getProjectDir();

        $composerLookups = [
            \dirname($projectDir).'/composer.json',
            $projectDir.'/composer.json',
            $projectDir.'/shopware/composer.json',
        ];

        foreach ($composerLookups as $composerLookup) {
            if (file_exists($composerLookup)) {
                $composerJson = json_decode(file_get_contents($composerLookup), true, \JSON_THROW_ON_ERROR);

                if (isset($composerJson['require']['shopware/core']) || isset($composerJson['require']['shopware/platform'])) {
                    return \dirname($composerLookup);
                }
            }
        }

        return false;
    }

    public function getCurrentShopwareVersion(string $shopwarePath): string
    {
        $lockFile = $shopwarePath.'/composer.lock';

        if (!file_exists($lockFile)) {
            return 'unknown';
        }

        $composerLock = json_decode(file_get_contents($lockFile), true, \JSON_THROW_ON_ERROR);

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
