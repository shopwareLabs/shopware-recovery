<?php

namespace App\Controller;

use App\Services\FlexMigrator;
use App\Services\RecoveryManager;
use App\Services\ReleaseInfoProvider;
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
        private readonly FlexMigrator $flexMigrator
    ) {
    }

    #[Route('/update', name: 'update')]
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

        $process = new Process([
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

        $process->start();

        return new StreamedResponse(function () use ($process) {
            foreach ($process->getIterator() as $item) {
                echo $item;
                flush();
            }

            if (!$process->isSuccessful()) {
                echo $process->getErrorOutput();
                flush();
            }

            echo json_encode([
                'success' => $process->isSuccessful()
            ]);
        });
    }

    #[Route('/update/_prepare', name: 'update_prepare')]
    public function prepare(Request $request): Response
    {
        $shopwarePath = $this->recoveryManager->getShopwareLocation();

        if ($shopwarePath === false) {
            return $this->redirectToRoute('index');
        }

        $process = new Process([
            $request->getSession()->get('phpBinary'),
            $shopwarePath . '/bin/console',
            'system:update:prepare',
            '--no-interaction',
        ]);

        $process->start();

        return new StreamedResponse(function () use ($process, $request) {
            foreach ($process->getIterator() as $item) {
                echo $item;
                flush();
            }

            if (!$process->isSuccessful()) {
                echo $process->getErrorOutput();
                flush();
            }

            echo json_encode([
                'success' => $process->isSuccessful()
            ]);
        });
    }

    #[Route('/update/_finish', name: 'update_finish')]
    public function finish(Request $request): Response
    {
        $shopwarePath = $this->recoveryManager->getShopwareLocation();

        if ($shopwarePath === false) {
            return $this->redirectToRoute('index');
        }

        $process = new Process([
            $request->getSession()->get('phpBinary'),
            $shopwarePath . '/bin/console',
            'system:update:finish',
            '--no-interaction',
        ]);

        $process->start();

        return new StreamedResponse(function () use ($process, $request) {
            foreach ($process->getIterator() as $item) {
                echo $item;
                flush();
            }

            if (!$process->isSuccessful()) {
                echo $process->getErrorOutput();
                flush();
            }

            echo json_encode([
                'success' => $process->isSuccessful()
            ]);
        });
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
