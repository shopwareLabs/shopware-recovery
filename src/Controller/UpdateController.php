<?php

namespace App\Controller;

use App\Services\RecoveryManager;
use App\Services\ReleaseInfoProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UpdateController extends AbstractController
{
    public function __construct(private readonly RecoveryManager $recoveryManager, private readonly ReleaseInfoProvider $releaseInfoProvider)
    {
    }

    #[Route('/update', name: 'update')]
    public function index(Request $request): Response
    {
        $shopwarePath = $this->recoveryManager->getShopwareLocation();

        return $this->render('update.html.twig', [
            'shopwarePath' => $shopwarePath,
            'currentShopwareVersion' => $this->recoveryManager->getCurrentShopwareVersion($shopwarePath),
            'isFlexProject' => $this->recoveryManager->isFlexProject($shopwarePath),
            'latestShopwareVersion' => $this->getLatestVersion($request),
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
