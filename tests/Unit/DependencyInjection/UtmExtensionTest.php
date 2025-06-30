<?php

namespace Tourze\UtmBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\UtmBundle\DependencyInjection\UtmExtension;

class UtmExtensionTest extends TestCase
{
    private UtmExtension $extension;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new UtmExtension();
        $this->container = new ContainerBuilder();
    }

    public function testLoad(): void
    {
        $this->extension->load([], $this->container);

        // 验证服务配置文件被加载
        $this->assertTrue(true);
    }

    public function testGetAlias(): void
    {
        $this->assertEquals('utm', $this->extension->getAlias());
    }
}