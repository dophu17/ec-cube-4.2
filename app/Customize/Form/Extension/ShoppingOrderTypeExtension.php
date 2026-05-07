<?php

namespace Customize\Form\Extension;

use Eccube\Form\Type\Shopping\OrderType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ShoppingOrderTypeExtension extends AbstractTypeExtension
{

    public static function getExtendedTypes(): iterable
    {
        return [OrderType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('gmo_card_no', TextType::class, [
            'mapped' => false,
            'required' => false,
        ])->add('gmo_card_expire', TextType::class, [
            'mapped' => false,
            'required' => false,
        ])->add('gmo_card_security_code', TextType::class, [
            'mapped' => false,
            'required' => false,
        ]);
    }
}
