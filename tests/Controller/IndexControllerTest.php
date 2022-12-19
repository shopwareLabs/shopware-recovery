<?php

namespace App\Tests\Controller;

use App\Controller\IndexController;
use App\Services\RecoveryManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Router;

class IndexControllerTest extends TestCase
{
    public function testIndexRedirectsToInstall(): void
    {
        $recovery = $this->createMock(RecoveryManager::class);
        $recovery->method('getShopwareLocation')->willReturn(false);

        $router = $this->createMock(Router::class);
        $router
            ->method('generate')
            ->willReturnArgument(0);

        $controller = new IndexController($recovery);
        $container = new Container();
        $container->set('router', $router);

        $controller->setContainer($container);

        $response = $controller->index();
        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertSame('install', $response->headers->get('Location'));
    }

    public function testIndexRedirectsToUpdate(): void
    {
        $recovery = $this->createMock(RecoveryManager::class);
        $recovery->method('getShopwareLocation')->willReturn('/var/www');

        $router = $this->createMock(Router::class);
        $router
            ->method('generate')
            ->willReturnArgument(0);

        $controller = new IndexController($recovery);
        $container = new Container();
        $container->set('router', $router);

        $controller->setContainer($container);

        $response = $controller->index();
        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertSame('update', $response->headers->get('Location'));
    }
}
