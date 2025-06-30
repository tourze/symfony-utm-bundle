<?php

namespace Tourze\UtmBundle\Controller\Admin;

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
use Tourze\UtmBundle\Entity\UtmParameters;

/**
 * UTM参数管理控制器
 */
class UtmParametersCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return UtmParameters::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('UTM参数')
            ->setEntityLabelInPlural('UTM参数')
            ->setPageTitle('index', 'UTM参数列表')
            ->setPageTitle('detail', fn (UtmParameters $utm) => sprintf('UTM参数详情 - %s', $utm->getSource() ?? 'N/A'))
            ->setPageTitle('edit', fn (UtmParameters $utm) => sprintf('编辑UTM参数 - %s', $utm->getSource() ?? 'N/A'))
            ->setPageTitle('new', '创建UTM参数')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['id', 'source', 'medium', 'campaign', 'term', 'content'])
            ->setHelp('index', '这里列出了所有的UTM参数记录，包括来源渠道、媒介和活动信息。');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm();

        yield TextField::new('source', 'UTM来源')
            ->setHelp('流量来源（如：google, facebook, newsletter）');

        yield TextField::new('medium', 'UTM媒介')
            ->setHelp('营销媒介（如：cpc, email, social）');

        yield TextField::new('campaign', 'UTM活动')
            ->setHelp('营销活动名称');

        yield TextField::new('term', 'UTM关键词')
            ->setHelp('付费关键词');

        yield TextField::new('content', 'UTM内容')
            ->setHelp('区分相似内容/广告');

        yield ArrayField::new('additionalParameters', '附加参数')
            ->hideOnIndex()
            ->setHelp('自定义UTM参数');

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('source', 'UTM来源'))
            ->add(TextFilter::new('medium', 'UTM媒介'))
            ->add(TextFilter::new('campaign', 'UTM活动'))
            ->add(TextFilter::new('term', 'UTM关键词'))
            ->add(TextFilter::new('content', 'UTM内容'))
            ->add(DateTimeFilter::new('createTime', '创建时间'));
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, Action::EDIT, Action::DELETE])
            ->add(Crud::PAGE_EDIT, Action::DETAIL)
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn (Action $action) => $action->setCssClass('text-danger')->displayIf(fn (UtmParameters $utm) => !$this->hasRelatedRecords($utm)));
    }

    /**
     * 检查UTM参数是否有关联记录
     */
    private function hasRelatedRecords(UtmParameters $utm): bool
    {
        // 这里应该实现检查逻辑，判断UTM参数是否被会话或转化记录引用
        // 如果被引用，则不应该允许删除
        // 现在先返回false作为示例
        return false;
    }
}
