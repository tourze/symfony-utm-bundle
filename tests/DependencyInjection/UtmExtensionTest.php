<?php

namespace Tourze\UtmBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\UtmBundle\DependencyInjection\UtmExtension;

/**
 * @internal
 */
#[CoversClass(UtmExtension::class)]
final class UtmExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private UtmExtension $extension;

    private ContainerBuilder $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension = new UtmExtension();
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.environment', 'test');
    }

    public function testLoad(): void
    {
        $this->extension->load([], $this->container);

        $this->assertTrue($this->container->hasAlias('Tourze\UtmBundle\Service\Storage\UtmStorageStrategyInterface'));
        $this->assertFalse([] === $this->container->getDefinitions());
    }

    public function testGetAlias(): void
    {
        $this->assertEquals('utm', $this->extension->getAlias());
    }

    public function testPrependConfiguresTwigPaths(): void
    {
        $container = new ContainerBuilder();

        // 执行prepend方法
        $this->extension->prepend($container);

        // 获取prepend的扩展配置
        $twigConfig = $container->getExtensionConfig('twig');

        // 断言twig配置被正确prepend
        $this->assertNotEmpty($twigConfig);
        $this->assertArrayHasKey('paths', $twigConfig[0]);

        // 验证模板路径配置
        $paths = $twigConfig[0]['paths'];

        // 验证路径指向正确的模板目录
        $expectedPath = dirname(__DIR__, 2) . '/templates';

        // 验证关联数组结构：路径 => 命名空间
        self::assertIsArray($paths);
        $this->assertArrayHasKey($expectedPath, $paths);
        $this->assertEquals('UtmBundle', $paths[$expectedPath]);
        $this->assertStringEndsWith('symfony-utm-bundle/templates', $expectedPath);
    }
}
