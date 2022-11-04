<?php

namespace App\Services;

use Symfony\Component\Filesystem\Filesystem;

class FlexMigrator
{
    private const REMOVE_FILES = [
        '.dockerignore',
        'Dockerfile',
        'PLATFORM_COMMIT_SHA',
        'README.md',
        'config/services.xml',
        'config/services_test.xml',
        'easy-coding-standard.php',
        'license.txt',
        'phpstan.neon',
        'phpunit.xml.dist',
        'psalm.xml',
        'public/index.php',
        'src/TestBootstrap.php',
        'var/plugins.json',
    ];

    private const REMOVE_DIRECTORIES = [
        '.github',
        '.gitlab-ci',
        'gitlab-ci.yml',
        'bin',
        'config/etc',
        'config/services'
    ];

    /**
     * Delete all files and directories that are not needed anymore
     */
    public function cleanup(string $projectDir): void
    {
        $fs = new Filesystem();

        foreach (self::REMOVE_FILES as $file) {
            $path = $projectDir . '/' . $file;
            if ($fs->exists($path)) {
                $fs->remove($path);
            }
        }

        foreach (self::REMOVE_DIRECTORIES as $directory) {
            $path = $projectDir . '/' . $directory;

            if ($fs->exists($path)) {
                $fs->remove($path);
            }
        }

        $fs->mkdir($projectDir . '/bin');
    }

    public function patchRootComposerJson(string $projectDir): void
    {
        $composerJsonPath = $projectDir . '/composer.json';
        $composerJson = json_decode(file_get_contents($composerJsonPath), true, JSON_THROW_ON_ERROR);

        $composerJson['require']['symfony/flex'] = '^2';
        $composerJson['require']['symfony/runtime'] = '^5.4';

        if (!isset($composerJson['config'])) {
            $composerJson['config'] = [];
        }

        if (!isset($composerJson['config']['allow-plugins'])) {
            $composerJson['config']['allow-plugins'] = [];
        }

        $composerJson['config']['allow-plugins']['symfony/flex'] = true;
        $composerJson['config']['allow-plugins']['symfony/runtime'] = true;

        unset($composerJson['config']['platform']);

        $composerJson['scripts'] = [
            'auto-scripts' => [
                'assets:install' => 'symfony-cmd'
            ],
            'post-install-cmd' => [
                '@auto-scripts'
            ],
            'post-update-cmd' => [
                '@auto-scripts'
            ]
        ];

        $composerJson['extra']['symfony'] = [
            'allow-contrib' => true,
            'endpoint' => [
                "https://raw.githubusercontent.com/shopware/recipes/flex/main/index.json",
                "flex://defaults"
            ]
        ];

        $composerJson['require-dev'] = [
            'fakerphp/faker' => '^1.20',
            'maltyxx/images-generator' => '^1.0',
            'mbezhanov/faker-provider-collection' => '^2.0',
            'symfony/stopwatch' => '^5.4',
            'symfony/web-profiler-bundle' => '^5.4'
        ];

        file_put_contents($composerJsonPath, json_encode($composerJson, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function copyNewTemplateFiles(string $projectDir): void
    {
        $fs = new Filesystem();

        $fs->mirror(__DIR__ . '/../Resources/flex-config/', $projectDir);
    }

    public function migrateEnvFile(string $projectDir): void
    {
        if (!file_exists($projectDir . '/.env')) {
            $envTemplate = <<<EOT
###> symfony/lock ###
# Choose one of the stores below
# postgresql+advisory://db_user:db_password@localhost/db_name
LOCK_DSN=flock
###< symfony/lock ###

###> symfony/messenger ###
# Choose one of the transports below
# MESSENGER_TRANSPORT_DSN=doctrine://default
# MESSENGER_TRANSPORT_DSN=amqp://guest:guest@localhost:5672/%2f/messages
# MESSENGER_TRANSPORT_DSN=redis://localhost:6379/messages
###< symfony/messenger ###

###> symfony/mailer ###
# MAILER_DSN=null://null
###< symfony/mailer ###

###> shopware/core ###
APP_ENV=prod
APP_URL=http://127.0.0.1:8000
APP_SECRET=7628a40c75b25f8a1f14b3812c3b250b
INSTANCE_ID=41322ddd2b70bd29ef65d402f025c785
BLUE_GREEN_DEPLOYMENT=0
DATABASE_URL=mysql://root:root@localhost/shopware
# With Shopware 6.4.17.0 the MAILER_DSN variable will be used in this template instead of MAILER_URL
MAILER_URL=null://null
###< shopware/core ###

###> shopware/elasticsearch ###
OPENSEARCH_URL=http://localhost:9200
SHOPWARE_ES_ENABLED=0
SHOPWARE_ES_INDEXING_ENABLED=0
SHOPWARE_ES_INDEX_PREFIX=sw
SHOPWARE_ES_THROW_EXCEPTION=1
###< shopware/elasticsearch ###

###> shopware/storefront ###
STOREFRONT_PROXY_URL=http://localhost
SHOPWARE_HTTP_CACHE_ENABLED=1
SHOPWARE_HTTP_DEFAULT_TTL=7200
###< shopware/storefront ###
EOT;

            file_put_contents($projectDir . '/.env', $envTemplate);
            return;
        }

        $env = array_filter(explode("\n", file_get_contents($projectDir . '/.env')));

        $newEnv = [];

        $newEnv[] = '###> symfony/lock ###';
        $newEnv[] = '# Choose one of the stores below';
        $newEnv[] = '# postgresql+advisory://db_user:db_password@localhost/db_name';
        $newEnv[] = $this->pickEnvValue($env, 'LOCK_DSN', 'semaphore');
        $newEnv[] = '###< symfony/lock ###';
        $newEnv[] = '';

        $newEnv[] = '###> symfony/messenger ###';
        $newEnv[] = '# Choose one of the transports below';
        $newEnv[] = '# MESSENGER_TRANSPORT_DSN=doctrine://default';
        $newEnv[] = '# MESSENGER_TRANSPORT_DSN=amqp://guest:guest@localhost:5672/%2f/messages';
        $newEnv[] = '# MESSENGER_TRANSPORT_DSN=redis://localhost:6379/messages';
        $newEnv[] = '###< symfony/messenger ###';
        $newEnv[] = '';

        $newEnv[] = '###> symfony/mailer ###';
        $newEnv[] = $this->pickEnvValue($env, 'MAILER_URL', 'null://null', 'MAILER_DSN');
        $newEnv[] = '###< symfony/mailer ###';

        $newEnv[] = '###> shopware/core ###';
        $newEnv[] = $this->pickEnvValue($env, 'APP_ENV', 'prod');
        $newEnv[] = $this->pickEnvValue($env, 'APP_URL', 'http://127.0.0.1:8000');
        $newEnv[] = $this->pickEnvValue($env, 'APP_SECRET', '7628a40c75b25f8a1f14b3812c3b250b');
        $newEnv[] = $this->pickEnvValue($env, 'INSTANCE_ID', '41322ddd2b70bd29ef65d402f025c785');
        $newEnv[] = $this->pickEnvValue($env, 'BLUE_GREEN_DEPLOYMENT', '0');
        $newEnv[] = $this->pickEnvValue($env, 'DATABASE_URL', 'mysql://root:root@localhost/shopware');
        $newEnv[] = '###< shopware/core ###';
        $newEnv[] = '';

        $newEnv[] = '###> shopware/elasticsearch ###';
        $newEnv[] = $this->pickEnvValue($env, 'SHOPWARE_ES_HOSTS', 'http://localhost:9200', 'OPENSEARCH_URL');
        $newEnv[] = $this->pickEnvValue($env, 'SHOPWARE_ES_ENABLED', '0');
        $newEnv[] = $this->pickEnvValue($env, 'SHOPWARE_ES_INDEXING_ENABLED', '0');
        $newEnv[] = $this->pickEnvValue($env, 'SHOPWARE_ES_INDEX_PREFIX', 'sw');
        $newEnv[] = $this->pickEnvValue($env, 'SHOPWARE_ES_THROW_EXCEPTION', '1');
        $newEnv[] = '###< shopware/elasticsearch ###';
        $newEnv[] = '';

        $newEnv[] = '###> shopware/storefront ###';
        $newEnv[] = $this->pickEnvValue($env, 'STOREFRONT_PROXY_URL', 'http://localhost');
        $newEnv[] = $this->pickEnvValue($env, 'SHOPWARE_HTTP_CACHE_ENABLED', '1');
        $newEnv[] = $this->pickEnvValue($env, 'SHOPWARE_HTTP_DEFAULT_TTL', '7200');
        $newEnv[] = '###< shopware/storefront ###';
        $newEnv[] = '';

        file_put_contents($projectDir . '/.env', implode("\n", array_merge($newEnv, $env)));
    }

    private function pickEnvValue(array $userEnv, string $key, string $default, ?string $newKey = null): string
    {
        foreach ($userEnv as $item) {
            if (str_starts_with($item, $key . '=')) {
                if ($newKey) {
                    return $newKey . '=' . substr($item, strlen($key) + 1);
                }

                return $item;
            }
        }

        return ($newKey ?? $key) . '=' . $default;
    }
}
