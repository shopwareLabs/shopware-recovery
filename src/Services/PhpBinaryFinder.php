<?php

namespace App\Services;

use Symfony\Component\Process\ExecutableFinder;

class PhpBinaryFinder
{
    private const PHP_BINARY_NAMES = ['php8.2', 'php8.1'];

    public function find(): string
    {
        if ($hosterSpecificBinary = $this->findHostedSpecificBinary()) {
            return $hosterSpecificBinary;
        }

        $finder = new ExecutableFinder();

        foreach (self::PHP_BINARY_NAMES as $name) {
            $binary = $finder->find($name);

            if ($binary !== null) {
                return $binary;
            }
        }

        $binary = $finder->find('php');

        if ($binary !== null) {
            return $binary;
        }

        if (defined('PHP_BINARY')) {
            $phpPath = dirname(PHP_BINARY);
            $fileName = explode('-', basename(PHP_BINARY), 2);
            $expectedPath = $phpPath . DIRECTORY_SEPARATOR . $fileName[0];

            if (file_exists($expectedPath)) {
                return $expectedPath;
            }
        }

        return '';
    }

    private function findHostedSpecificBinary(): ?string
    {
        // Profihost
        if (file_exists('/usr/local/php8.1/bin/php')) {
            return '/usr/local/php8.1/bin/php';
        }

        return null;
    }
}
