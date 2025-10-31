<?php

declare(strict_types=1);

namespace Tourze\UtmBundle\Service;

use EasyCorp\Bundle\EasyAdminBundle\Config\Menu\CrudMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Menu\SectionMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\UtmBundle\Controller\Admin\UtmConversionCrudController;
use Tourze\UtmBundle\Controller\Admin\UtmParametersCrudController;
use Tourze\UtmBundle\Controller\Admin\UtmSessionCrudController;

/**
 * UTM营销参数跟踪模块的管理菜单
 */
#[Autoconfigure(public: true)]
class AdminMenu implements MenuProviderInterface
{
    public function __invoke(ItemInterface $item): void
    {
        $item->addChild('utm-section', [
            'label' => 'UTM营销跟踪',
            'attributes' => [
                'class' => 'nav-header',
                'icon' => 'fa fa-chart-line',
            ],
        ]);

        $item->addChild('utm-parameters', [
            'label' => 'UTM参数',
            'route' => 'admin',
            'routeParameters' => [
                'crudAction' => 'index',
                'crudControllerFqcn' => UtmParametersCrudController::class,
            ],
            'attributes' => [
                'icon' => 'fa fa-tags',
            ],
        ]);

        $item->addChild('utm-sessions', [
            'label' => 'UTM会话',
            'route' => 'admin',
            'routeParameters' => [
                'crudAction' => 'index',
                'crudControllerFqcn' => UtmSessionCrudController::class,
            ],
            'attributes' => [
                'icon' => 'fa fa-users',
            ],
        ]);

        $item->addChild('utm-conversions', [
            'label' => 'UTM转化',
            'route' => 'admin',
            'routeParameters' => [
                'crudAction' => 'index',
                'crudControllerFqcn' => UtmConversionCrudController::class,
            ],
            'attributes' => [
                'icon' => 'fa fa-trophy',
            ],
        ]);
    }

    /**
     * 获取UTM模块的菜单项
     * @return array<int, CrudMenuItem|SectionMenuItem>
     * @deprecated 使用 __invoke 方法替代
     */
    public function getMenuItems(): array
    {
        return [
            MenuItem::section('UTM营销跟踪', 'fa fa-chart-line')->setPermission('ROLE_ADMIN'),
            MenuItem::linkToCrud('UTM参数', 'fa fa-tags', UtmParametersCrudController::class)
                ->setPermission('ROLE_ADMIN'),
            MenuItem::linkToCrud('UTM会话', 'fa fa-users', UtmSessionCrudController::class)
                ->setPermission('ROLE_ADMIN'),
            MenuItem::linkToCrud('UTM转化', 'fa fa-trophy', UtmConversionCrudController::class)
                ->setPermission('ROLE_ADMIN'),
        ];
    }
}
