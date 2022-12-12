<?php

namespace App\Controller;

use App\Services\RecoveryManager;
use App\Services\StreamedCommandResponseGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;

class InstallController extends AbstractController
{
    public function __construct(
        private readonly RecoveryManager $recoveryManager,
        private readonly StreamedCommandResponseGenerator $streamedCommandResponseGenerator
    ) {
    }

    #[Route('/install', name: 'install')]
    public function index(): Response
    {
        // Check if Shopware is already installed
        if ($this->recoveryManager->getShopwareLocation() !== false) {
            return $this->redirectToRoute('index');
        }

        return $this->render('install.html.twig');
    }

    #[Route('/install/_run', name: 'install_run')]
    public function run(Request $request): StreamedResponse
    {
        $finish = function (Process $process) use ($request) {
            echo json_encode([
                'success' => $process->isSuccessful(),
                'newLocation' => $request->getBasePath() . '/shopware/public/',
            ]);
        };

        return $this->streamedCommandResponseGenerator->run([
            $request->getSession()->get('phpBinary'),
            $_SERVER['SCRIPT_FILENAME'],
            'composer',
            'create-project',
            'shopware/production:dev-flex',
            '--no-interaction',
            '--no-ansi',
            'shopware',
        ], $finish);
    }
}
