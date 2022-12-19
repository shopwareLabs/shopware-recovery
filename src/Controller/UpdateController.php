<?php
declare(strict_types=1);

namespace App\Controller;

use App\Services\FlexMigrator;
use App\Services\RecoveryManager;
use App\Services\ReleaseInfoProvider;
use App\Services\StreamedCommandResponseGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UpdateController extends AbstractController
{
    public function __construct(
        private readonly RecoveryManager $recoveryManager,
        private readonly ReleaseInfoProvider $releaseInfoProvider,
        private readonly FlexMigrator $flexMigrator,
        private readonly StreamedCommandResponseGenerator $streamedCommandResponseGenerator
    ) {
    }

    #[Route('/update', name: 'update', defaults: ['step' => 2])]
    public function index(Request $request): Response
    {
        $shopwarePath = $this->recoveryManager->getShopwareLocation();

        $currentShopwareVersion = $this->recoveryManager->getCurrentShopwareVersion($shopwarePath);
        $latestVersion = $this->getLatestVersion($request);

        if ($currentShopwareVersion === $latestVersion) {
            return $this->redirectToRoute('finish');
        }

        return $this->render('update.html.twig', [
            'shopwarePath' => $shopwarePath,
            'currentShopwareVersion' => $currentShopwareVersion,
            'isFlexProject' => $this->recoveryManager->isFlexProject($shopwarePath),
            'latestShopwareVersion' => $latestVersion,
        ]);
    }

    #[Route('/update/_migrate-template', name: 'migrate-template')]
    public function migrateTemplate(): Response
    {
        $shopwarePath = $this->recoveryManager->getShopwareLocation();

        $this->flexMigrator->cleanup($shopwarePath);
        $this->flexMigrator->patchRootComposerJson($shopwarePath);
        $this->flexMigrator->copyNewTemplateFiles($shopwarePath);
        $this->flexMigrator->migrateEnvFile($shopwarePath);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    #[Route('/update/_run', name: 'update_run')]
    public function run(Request $request): Response
    {
        $shopwarePath = $this->recoveryManager->getShopwareLocation();

        $this->updateComposerJsonConstraint($request, $shopwarePath.'/composer.json');

        return $this->streamedCommandResponseGenerator->runJSON([
            $this->recoveryManager->getPhpBinary($request),
            $this->recoveryManager->getBinary(),
            'composer',
            'update',
            '-d',
            $shopwarePath,
            '--no-interaction',
            '--no-ansi',
            '--no-scripts',
            '-v',
            '--with-all-dependencies', // update all packages
        ]);
    }

    #[Route('/update/_prepare', name: 'update_prepare')]
    public function prepare(Request $request): Response
    {
        $shopwarePath = $this->recoveryManager->getShopwareLocation();

        return $this->streamedCommandResponseGenerator->runJSON([
            $this->recoveryManager->getPhpBinary($request),
            $shopwarePath.'/bin/console',
            'system:update:prepare',
            '--no-interaction',
        ]);
    }

    #[Route('/update/_finish', name: 'update_finish')]
    public function finish(Request $request): Response
    {
        $shopwarePath = $this->recoveryManager->getShopwareLocation();

        return $this->streamedCommandResponseGenerator->runJSON([
            $this->recoveryManager->getPhpBinary($request),
            $shopwarePath.'/bin/console',
            'system:update:finish',
            '--no-interaction',
        ]);
    }

    private function getLatestVersion(Request $request): string
    {
        if ($request->getSession()->has('latestVersion')) {
            $sessionValue = $request->getSession()->get('latestVersion');
            \assert(\is_string($sessionValue));

            return $sessionValue;
        }

        $latestVersions = $this->releaseInfoProvider->fetchLatestRelease();

        $shopwarePath = $this->recoveryManager->getShopwareLocation();
        \assert(\is_string($shopwarePath));

        $currentVersion = $this->recoveryManager->getCurrentShopwareVersion($shopwarePath);
        $latestVersion = $latestVersions[substr($currentVersion, 0, 3)];

        // If the user is already on the latest version in the current major, we need to update to the next major
        if ($latestVersion === $currentVersion) {
            $first = (int) substr($currentVersion, 0, 1);
            $second = (int) substr($currentVersion, 2, 1);
            ++$second;

            if (isset($latestVersions[$first.'.'.$second])) {
                $latestVersion = $latestVersions[$first.'.'.$second];
            }
        }

        $request->getSession()->set('latestVersion', $latestVersion);

        return $latestVersion;
    }

    private function updateComposerJsonConstraint(Request $request, string $file): void
    {
        $shopwarePackages = [
            'shopware/core',
            'shopware/administration',
            'shopware/storefront',
            'shopware/elasticsearch',
        ];

        /** @var array{require: array<string, string>} $composerJson */
        $composerJson = json_decode((string) file_get_contents($file), true, \JSON_THROW_ON_ERROR);
        $latestVersion = $this->getLatestVersion($request);

        foreach ($shopwarePackages as $shopwarePackage) {
            if (!isset($composerJson['require'][$shopwarePackage])) {
                continue;
            }

            // Lock the composer version to that major version
            $composerJson['require'][$shopwarePackage] = '~'.substr($latestVersion, 0, 3).'.0';
        }

        file_put_contents($file, json_encode($composerJson, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES));
    }
}
