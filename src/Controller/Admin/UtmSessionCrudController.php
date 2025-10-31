<?php

namespace Tourze\UtmBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\UtmBundle\Entity\UtmParameter;
use Tourze\UtmBundle\Entity\UtmSession;

/**
 * UTM会话管理控制器
 * @extends AbstractCrudController<UtmSession>
 */
#[AdminCrud(
    routePath: '/utm/session',
    routeName: 'utm_session',
)]
final class UtmSessionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return UtmSession::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('UTM会话')
            ->setEntityLabelInPlural('UTM会话')
            ->setPageTitle('index', 'UTM会话列表')
            ->setPageTitle('detail', fn (UtmSession $session) => sprintf('UTM会话详情 - %s', $session->getSessionId()))
            ->setPageTitle('edit', fn (UtmSession $session) => sprintf('编辑UTM会话 - %s', $session->getSessionId()))
            ->setPageTitle('new', '创建UTM会话')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['id', 'sessionId', 'userIdentifier', 'clientIp'])
            ->setHelp('index', '这里列出了所有的UTM会话记录，包含会话信息和关联的UTM参数。')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm()
        ;

        yield TextField::new('sessionId', '会话ID');

        yield AssociationField::new('parameters', 'UTM参数')
            ->setFormTypeOption('choice_label', function ($utm) {
                if (!$utm instanceof UtmParameter) {
                    return 'N/A';
                }

                return sprintf('%s / %s / %s', $utm->getSource(), $utm->getMedium(), $utm->getCampaign());
            })
        ;

        yield TextField::new('userIdentifier', '用户标识符')
            ->setHelp('用户唯一标识，通常是用户ID或用户名')
        ;

        yield TextField::new('clientIp', '客户端IP')
            ->hideOnIndex()
        ;

        yield TextField::new('userAgent', '用户代理')
            ->hideOnIndex()
            ->setTemplatePath('@UtmBundle/admin/field/user_agent.html.twig')
        ;

        yield ArrayField::new('metadata', '元数据')
            ->hideOnIndex()
            ->setHelp('会话额外元数据')
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('expiresAt', '过期时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('sessionId', '会话ID'))
            ->add(TextFilter::new('userIdentifier', '用户标识符'))
            ->add(TextFilter::new('clientIp', '客户端IP'))
            ->add(EntityFilter::new('parameters', 'UTM参数'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('expiresAt', '过期时间'))
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
