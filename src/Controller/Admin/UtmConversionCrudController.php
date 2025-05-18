<?php

namespace Tourze\UtmBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\UtmBundle\Entity\UtmConversion;

/**
 * UTM转化管理控制器
 */
class UtmConversionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return UtmConversion::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('UTM转化')
            ->setEntityLabelInPlural('UTM转化')
            ->setPageTitle('index', 'UTM转化列表')
            ->setPageTitle('detail', fn (UtmConversion $conversion) => sprintf('UTM转化详情 - %s', $conversion->getEventName()))
            ->setPageTitle('edit', fn (UtmConversion $conversion) => sprintf('编辑UTM转化 - %s', $conversion->getEventName()))
            ->setPageTitle('new', '创建UTM转化')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['id', 'eventName', 'userIdentifier'])
            ->setHelp('index', '这里列出了所有的UTM转化记录，包括关联的UTM参数、会话以及转化价值。');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm();

        yield TextField::new('eventName', '事件名称')
            ->setHelp('转化事件的名称，如注册、购买等');

        yield TextField::new('userIdentifier', '用户标识符')
            ->setHelp('用户唯一标识，通常是用户ID或用户名');

        yield AssociationField::new('parameters', 'UTM参数')
            ->setFormTypeOption('choice_label', function ($utm) {
                return $utm ? sprintf('%s / %s / %s', $utm->getSource(), $utm->getMedium(), $utm->getCampaign()) : 'N/A';
            });

        yield AssociationField::new('session', 'UTM会话')
            ->setFormTypeOption('choice_label', function ($session) {
                return $session ? $session->getSessionId() : 'N/A';
            });

        yield NumberField::new('value', '转化价值')
            ->setNumDecimals(2)
            ->setHelp('转化事件的价值，如购买金额');

        yield ArrayField::new('metadata', '元数据')
            ->hideOnIndex()
            ->setHelp('转化事件的额外元数据');

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('eventName', '事件名称'))
            ->add(TextFilter::new('userIdentifier', '用户标识符'))
            ->add(EntityFilter::new('parameters', 'UTM参数'))
            ->add(EntityFilter::new('session', 'UTM会话'))
            ->add(NumericFilter::new('value', '转化价值'))
            ->add(DateTimeFilter::new('createTime', '创建时间'));
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, Action::EDIT, Action::DELETE])
            ->add(Crud::PAGE_EDIT, Action::DETAIL)
            ->add(Crud::PAGE_NEW, Action::INDEX);
    }

    public function createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters): \Doctrine\ORM\QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        // 优化查询，预加载关联实体
        return $queryBuilder
            ->select('entity, parameters, session')
            ->leftJoin('entity.parameters', 'parameters')
            ->leftJoin('entity.session', 'session');
    }
}
