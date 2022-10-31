<?php

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/vendor/autoload.php';

$debug = (bool) ($_SERVER['APP_DEBUG'] ?? 0);

// enable debug mode
$debug = true;

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'prod', $debug);

$request = Request::createFromGlobals();

$response = $kernel->handle($request);

$response->send();

$kernel->terminate($request, $response);
