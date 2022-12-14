<?php
declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FinishController extends AbstractController
{
    #[Route('/finish', name: 'finish')]
    public function default(Request $request): Response
    {
        return $this->render('finish.html.twig');
    }
}
