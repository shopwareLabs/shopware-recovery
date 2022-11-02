<?php

namespace App\Controller;

use App\Services\RecoveryManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    public function __construct(private readonly RecoveryManager $recoveryManager)
    {
    }

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        if ($this->recoveryManager->getShopwareLocation() === false) {
            return $this->redirectToRoute('install');
        }

        return $this->redirectToRoute('update');
    }
}
