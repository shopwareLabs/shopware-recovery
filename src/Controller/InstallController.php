<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;

class InstallController
{
    #[Route('/install')]
    public function index(Request $request): StreamedResponse
    {
        $process = new Process([
            $request->getSession()->get('phpBinary'),
            $_SERVER['SCRIPT_FILENAME'],
            'composer',
            'create-project',
            'shopware/production:dev-flex',
            '--no-interaction',
            '--no-ansi',
        ]);

        $process->start();

        return new StreamedResponse(function () use ($process) {
            while ($process->isRunning()) {
                echo $process->getIncrementalOutput();
                echo $process->getIncrementalErrorOutput();
                flush();
                sleep(1);
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
}
