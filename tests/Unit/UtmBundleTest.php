<?php

namespace Tourze\UtmBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\UtmBundle\UtmBundle;

class UtmBundleTest extends TestCase
{
    public function testBundle(): void
    {
        $bundle = new UtmBundle();
        
        $this->assertInstanceOf(\Symfony\Component\HttpKernel\Bundle\Bundle::class, $bundle);
    }

    public function testBuild(): void
    {
        $bundle = new UtmBundle();
        $container = new ContainerBuilder();
        
        $bundle->build($container);
        
        // 测试 build 方法没有抛出异常
        $this->assertTrue(true);
    }
}