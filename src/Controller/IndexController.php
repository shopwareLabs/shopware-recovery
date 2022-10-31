<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class IndexController extends AbstractController
{
    #[Route('/')]
    public function index(): Response
    {
        return $this->render('index.html.twig');
    }

    #[Route('/test')]
    public function test2(): Response
    {
        return new Response('test2');
    }
}
