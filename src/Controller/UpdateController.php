<?php

namespace App\Controller;

use App\Services\FlexMigrator;
use App\Services\RecoveryManager;
use App\Services\ReleaseInfoProvider;
use App\Services\StreamedCommandResponseGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Process\Process;
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

        if ($shopwarePath === false) {
            return $this->redirectToRoute('index');
        }

        return $this->render('update.html.twig', [
            'shopwarePath' => $shopwarePath,
            'currentShopwareVersion' => $this->recoveryManager->getCurrentShopwareVersion($shopwarePath),
            'isFlexProject' => $this->recoveryManager->isFlexProject($shopwarePath),
            'latestShopwareVersion' => $this->getLatestVersion($request),
        ]);
    }

    #[Route('/update/_migrate-template', name: 'migrate-template')]
    public function migrateTemplate(): Response
    {
        $shopwarePath = $this->recoveryManager->getShopwareLocation();

        if ($shopwarePath === false) {
            return $this->redirectToRoute('index');
        }

        $this->flexMigrator->cleanup($shopwarePath);
        $this->flexMigrator->patchRootComposerJson($shopwarePath);
        $this->flexMigrator->copyNewTemplateFiles($shopwarePath);
        $this->flexMigrator->migrateEnvFile($shopwarePath);

        return $this->redirectToRoute('update');
    }

    #[Route('/update/_run', name: 'update_run')]
    public function run(Request $request): Response
    {
        $shopwarePath = $this->recoveryManager->getShopwareLocation();

        if ($shopwarePath === false) {
            return $this->redirectToRoute('index');
        }

        return $this->streamedCommandResponseGenerator->runJSON([
            $request->getSession()->get('phpBinary'),
            $_SERVER['SCRIPT_FILENAME'],
            'composer',
            'update',
            '-d',
            $shopwarePath,
            '--no-interaction',
            '--no-ansi',
            '--with-all-dependencies', // update all packages
        ]);
    }

    #[Route('/update/_prepare', name: 'update_prepare')]
    public function prepare(Request $request): Response
    {
        $shopwarePath = $this->recoveryManager->getShopwareLocation();

        if ($shopwarePath === false) {
            return $this->redirectToRoute('index');
        }

        return $this->streamedCommandResponseGenerator->runJSON([
            $request->getSession()->get('phpBinary'),
            $shopwarePath . '/bin/console',
            'system:update:prepare',
            '--no-interaction',
        ]);
    }

    #[Route('/update/_finish', name: 'update_finish')]
    public function finish(Request $request): Response
    {
        $shopwarePath = $this->recoveryManager->getShopwareLocation();

        if ($shopwarePath === false) {
            return $this->redirectToRoute('index');
        }

        return $this->streamedCommandResponseGenerator->runJSON([
            $request->getSession()->get('phpBinary'),
            $shopwarePath . '/bin/console',
            'system:update:prepare',
            '--no-interaction',
        ]);
    }

    private function getLatestVersion(Request $request): string
    {
        if ($request->getSession()->has('latestVersion')) {
            return $request->getSession()->get('latestVersion');
        }

        $latestVersion = $this->releaseInfoProvider->fetchLatestRelease();

        $request->getSession()->set('latestVersion', $latestVersion);

        return $latestVersion;
    }
}
