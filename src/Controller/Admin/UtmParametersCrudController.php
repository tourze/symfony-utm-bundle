<?php

namespace Tourze\UtmBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\UtmBundle\Entity\UtmParameter;

/**
 * UTM参数管理控制器
 * @extends AbstractCrudController<UtmParameter>
 */
#[AdminCrud(
    routePath: '/utm/parameters',
    routeName: 'utm_parameters',
)]
final class UtmParametersCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return UtmParameter::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('UTM参数')
            ->setEntityLabelInPlural('UTM参数')
            ->setPageTitle('index', 'UTM参数列表')
            ->setPageTitle('detail', fn (UtmParameter $utm) => sprintf('UTM参数详情 - %s', $utm->getSource() ?? 'N/A'))
            ->setPageTitle('edit', fn (UtmParameter $utm) => sprintf('编辑UTM参数 - %s', $utm->getSource() ?? 'N/A'))
            ->setPageTitle('new', '创建UTM参数')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['id', 'source', 'medium', 'campaign', 'term', 'content'])
            ->setHelp('index', '这里列出了所有的UTM参数记录，包括来源渠道、媒介和活动信息。')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm()
        ;

        yield TextField::new('source', 'UTM来源')
            ->setHelp('流量来源（如：google, facebook, newsletter）')
        ;

        yield TextField::new('medium', 'UTM媒介')
            ->setHelp('营销媒介（如：cpc, email, social）')
        ;

        yield TextField::new('campaign', 'UTM活动')
            ->setHelp('营销活动名称')
        ;

        yield TextField::new('term', 'UTM关键词')
            ->setHelp('付费关键词')
        ;

        yield TextField::new('content', 'UTM内容')
            ->setHelp('区分相似内容/广告')
        ;

        yield ArrayField::new('additionalParameters', '附加参数')
            ->hideOnIndex()
            ->setHelp('自定义UTM参数')
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('source', 'UTM来源'))
            ->add(TextFilter::new('medium', 'UTM媒介'))
            ->add(TextFilter::new('campaign', 'UTM活动'))
            ->add(TextFilter::new('term', 'UTM关键词'))
            ->add(TextFilter::new('content', 'UTM内容'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, Action::DETAIL)
            ->add(Crud::PAGE_NEW, Action::INDEX)
        ;
    }
}
