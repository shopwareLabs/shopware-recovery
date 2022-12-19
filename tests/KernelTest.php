<?php

namespace App\Tests;

use App\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;

/**
 * @covers \App\Kernel
 */
class KernelTest extends TestCase
{
    public function testKernel(): void
    {
        $kernel = new Kernel('test', true);

        static::assertSame('test', $kernel->getEnvironment());
    }

    public function testBundles(): void
    {
        $kernel = new Kernel('test', true);

        $bundles = $kernel->registerBundles();

        static::assertCount(2, $bundles);
        static::assertInstanceOf(FrameworkBundle::class, $bundles[0]);
        static::assertInstanceOf(TwigBundle::class, $bundles[1]);
    }

    public function testProjectDir(): void
    {
        $kernel = new Kernel('test', true);

        static::assertSame(realpath(__DIR__ . '/../src'), $kernel->getProjectDir());
    }

    public function testCacheDir(): void
    {
        $kernel = new Kernel('test', true);

        static::assertSame(sys_get_temp_dir() . '/shopware-recovery/', $kernel->getCacheDir());
        static::assertSame(sys_get_temp_dir() . '/shopware-recovery/', $kernel->getLogDir());
    }

    public function testBuildKernel(): void
    {
        $kernel = new Kernel('test', true);
        $kernel->boot();

        static::assertTrue($kernel->getContainer()->has('router'));

        $router = $kernel->getContainer()->get('router');
        static::assertCount(10, $router->getRouteCollection());
    }
}
