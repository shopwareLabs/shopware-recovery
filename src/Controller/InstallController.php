<?php

namespace App\Controller;

use Composer\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class InstallController
{
    #[Route('/install')]
    public function index(): StreamedResponse
    {
        // Disable time limit
        set_time_limit(0);
        ignore_user_abort(true);

        $application = new Application();
        $application->setAutoExit(false);

        return new StreamedResponse(function () use ($application) {
            $input = new ArrayInput([
                'command' => 'create-project',
                'package' => 'shopware/production:dev-flex',
                '--no-interaction' => true,
                '--no-ansi' => true,
            ]);

            $output = new class() extends BufferedOutput {
                protected function doWrite(string $message, bool $newline): void
                {
                    echo $message;
                    if ($newline) {
                        echo \PHP_EOL;
                    }

                    flush();
                }
            };

            $status = $application->run($input, $output);

            echo json_encode([
                'success' => $status === Command::SUCCESS,
            ]);
        });
    }
}
