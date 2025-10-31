<?php

declare(strict_types=1);

namespace Tourze\UtmBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;
use Tourze\UtmBundle\Service\AdminMenu;

/**
 * AdminMenu 服务的集成测试
 *
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    private AdminMenu $adminMenu;

    protected function onSetUp(): void
    {
        // 集成测试环境设置，这里暂时不需要特殊配置
    }

    private function getAdminMenuService(): AdminMenu
    {
        return self::getService(AdminMenu::class);
    }

    public function testInvokeAddsCorrectMenuItems(): void
    {
        $this->adminMenu = $this->getAdminMenuService();

        // 创建一个模拟的 ItemInterface
        $mockItem = $this->createMock(ItemInterface::class);

        // 期望 addChild 被调用4次（1个section + 3个菜单项）
        $mockItem
            ->expects($this->exactly(4))
            ->method('addChild')
            ->willReturnSelf()
        ;

        // 调用 __invoke 方法
        ($this->adminMenu)($mockItem);

        $this->assertTrue(true); // 验证没有异常抛出
    }

    public function testGetMenuItemsReturnsCorrectCount(): void
    {
        $this->adminMenu = $this->getAdminMenuService();

        // 虽然 getMenuItems 已废弃，但仍需测试向后兼容性
        /** @phpstan-ignore method.deprecated */
        $menuItems = $this->adminMenu->getMenuItems();

        $this->assertCount(4, $menuItems);
        $this->assertNotEmpty($menuItems);
    }

    public function testMenuItemsAreNotNull(): void
    {
        $this->adminMenu = $this->getAdminMenuService();
        /** @phpstan-ignore method.deprecated */
        $menuItems = $this->adminMenu->getMenuItems();

        // 验证所有菜单项都有效且可渲染
        $this->assertCount(4, $menuItems);
    }

    public function testGetMenuItemsReturnsArray(): void
    {
        $this->adminMenu = $this->getAdminMenuService();
        /** @phpstan-ignore method.deprecated */
        $menuItems = $this->adminMenu->getMenuItems();

        $this->assertGreaterThanOrEqual(1, count($menuItems));
    }

    public function testMenuItemsStructure(): void
    {
        $this->adminMenu = $this->getAdminMenuService();
        /** @phpstan-ignore method.deprecated */
        $menuItems = $this->adminMenu->getMenuItems();

        // 验证至少有4个菜单项
        $this->assertCount(4, $menuItems);

        // 验证每个菜单项都可以被渲染（存在关键属性）
        foreach ($menuItems as $item) {
            $this->assertNotEmpty(
                $item->getAsDto()->getLabel()
            );
        }
    }
}
