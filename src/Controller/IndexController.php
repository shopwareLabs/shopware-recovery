<?php

namespace App\Controller;

use Symfony\Component\Routing\Route;
use Symfony\Flex\Response;

class IndexController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return new Response('Hello World!');
    }
}