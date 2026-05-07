<?php

namespace Plugin\RemisePayment42\Form\Extension;

use Eccube\Form\Type\Front\EntryType;
use Eccube\Form\Type\Shopping\OrderType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class OrderTypeException extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // 支払方法
        $useMethod = [];
        $useMethod[trans('remise_payment4.common.label.card_method.' . 10)] = 10;
        $useMethod[trans('remise_payment4.common.label.card_method.' . 61)] = 61;
        $useMethod[trans('remise_payment4.common.label.card_method.' . 80)] = 80;
        // 分割回数
        $usePtimes = [];
        foreach (explode(',', trans('remise_payment4.common.label.card_ptimes')) as $value)
        {
            $usePtimes[$value . trans('remise_payment4.common.label.card_ptimes_count')] = $value;
        }

        // 初期値
        $use_payquick = '0';
        $method = '';
        $regist_payquick = '0';

        $builder
            // ペイクイック登録
            ->add('remise_payment4_regist_payquick', ChoiceType::class, [
                'label' => trans('remise_payment4.front.label.regist_payquick'),
                'required' => false,
                'choices'  => [
                    trans('remise_payment4.front.label.regist_payquick') => '1',
                ],
                'expanded' => true,
                'multiple' => true,
                'mapped' => false,
            ])
            // ペイクイック利用
            ->add('remise_payment4_use_payquick', ChoiceType::class, [
                'required' => false,
                'choices'  => [
                    trans('remise_payment4.front.label.use_payquick.use') => '1',
                    trans('remise_payment4.front.label.use_payquick.notuse') => '0',
                ],
                'expanded' => true,
                'mapped' => false,
                'placeholder' => false,
            ])
            // 支払方法
            ->add('remise_payment4_method', ChoiceType::class, [
                'label' => trans('remise_payment4.front.label.method'),
                'required' => false,
                'choices'  => $useMethod,
                'expanded' => true,
                'mapped' => false,
                'placeholder' => false,
            ])
            // 分割回数
            ->add('remise_payment4_ptimes', ChoiceType::class, [
                'label' => trans('remise_payment4.front.label.ptimes'),
                'required' => false,
                'choices'  => $usePtimes,
                'expanded' => false,
                'multiple' => false,
                'mapped' => false,
                'placeholder' => false,
            ])
            ;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return OrderType::class;
    }
    
    /**
     * Return the class of the type being extended.
     */
    public static function getExtendedTypes(): iterable
    {
        return [OrderType::class];
    }
}
