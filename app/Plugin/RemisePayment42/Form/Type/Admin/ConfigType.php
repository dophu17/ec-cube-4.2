<?php

namespace Plugin\RemisePayment42\Form\Type\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Plugin\RemisePayment42\Entity\ConfigInfo;

/**
 * プラグイン設定画面
 */
class ConfigType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // 分割回数
        $ptimes = [];
        foreach (explode(',', trans('remise_payment4.common.label.card_ptimes')) as $value)
        {
            $ptimes[$value . trans('remise_payment4.common.label.card_ptimes_count')] = $value;
        }

        // 支払期限
        $paydate = [];
        $paydate['--'] = '';
        for ($i = 2; $i <= 30; $i++)
        {
            $paydate[$i] = $i;
        }

        // 設定情報を取得
        $ConfigInfo = $options['data'];

        $builder
            // 加盟店コード
            ->add('shopco', TextType::class, [
                'label' => trans('remise_payment4.admin_config.label.shopco'),
                'attr' => [
                    'placeholder' => trans('remise_payment4.admin_config.label.example') . ': RMS00000',
                ],
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => trans('remise_payment4.admin_config.text.shopco.blank'),
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^RMS[A-Z0-9]{5}$/',
                        'message' => trans('remise_payment4.admin_config.text.shopco.unmatch.length'),
                    ]),
                ],
            ])
            // ホスト番号
            ->add('hostid', TextType::class, [
                'label' => trans('remise_payment4.admin_config.label.hostid'),
                'attr' => [
                    'placeholder' => trans('remise_payment4.admin_config.label.example') . ': 00000002',
                ],
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => trans('remise_payment4.admin_config.text.hostid.blank'),
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[A-Z0-9]{5}[0-9]{3}$/',
                        'message' => trans('remise_payment4.admin_config.text.hostid.unmatch.length'),
                    ]),
                ],
            ])
            // ご利用の決済方法
            ->add('use_payment', ChoiceType::class, [
                'label' => trans('remise_payment4.admin_config.label.use_payment'),
                'required' => false,
                'choices'  => [
                    trans('remise_payment4.admin_config.label.use_payment.card') => '1',
                    trans('remise_payment4.admin_config.label.use_payment.cvs') => '2',
                ],
                'expanded' => true,
                'multiple' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => trans('remise_payment4.admin_config.text.use_payment.blank'),
                    ]),
                ],
            ])
            // ご利用の追加オプション
            ->add('use_option', ChoiceType::class, [
                'label' => trans('remise_payment4.admin_config.label.use_option'),
                'required' => false,
                'choices'  => [
                    trans('remise_payment4.admin_config.label.use_option.extset') => '1',
                    trans('remise_payment4.admin_config.label.use_option.ac') => '2',
                ],
                'expanded' => true,
                'multiple' => true,
            ])

            // 結果通知URL
            ->add('recv_url', TextType::class, [
                'label' => trans('remise_payment4.admin_config.label.recv_url'),
                'mapped' => false,
            ])
            // 決済情報送信先URL
            ->add('card_url', TextType::class, [
                'label' => trans('remise_payment4.admin_config.label.card_url'),
                'attr' => [
                    'placeholder' => trans('remise_payment4.admin_config.label.example') . ': https://...',
                ],
                'required' => false,
            ])
            // 処理区分
            ->add('job', ChoiceType::class, [
                'label' => trans('remise_payment4.admin_config.label.job'),
                'required' => false,
                'choices'  => [
                    trans('remise_payment4.admin_config.label.job.auth') => 'AUTH',
                    trans('remise_payment4.admin_config.label.job.capture') => 'CAPTURE',
                ],
                'expanded' => true,
            ])
            // ペイクイック
            ->add('payquick_flag', ChoiceType::class, [
                'label' => trans('remise_payment4.admin_config.label.payquick_flag'),
                'required' => false,
                'choices'  => [
                    trans('remise_payment4.admin_config.label.payquick_flag.use') => '1',
                    trans('remise_payment4.admin_config.label.payquick_flag.notuse') => '0',
                ],
                'expanded' => true,
            ])
            // 支払方法
            ->add('use_method', ChoiceType::class, [
                'label' => trans('remise_payment4.admin_config.label.use_method'),
                'required' => false,
                'choices'  => [
                    trans('remise_payment4.common.label.card_method.10') => '10',
                    trans('remise_payment4.common.label.card_method.61') => '61',
                    trans('remise_payment4.common.label.card_method.80') => '80',
                ],
                'expanded' => true,
                'multiple' => true,
            ])
            // 分割回数
            ->add('ptimes', ChoiceType::class, [
                'label' => trans('remise_payment4.admin_config.label.ptimes'),
                'required' => false,
                'choices'  => $ptimes,
                'expanded' => true,
                'multiple' => true,
            ])

            // 収納情報通知URL
            ->add('acpt_url', TextType::class, [
                'label' => trans('remise_payment4.admin_config.label.acpt_url'),
                'mapped' => false,
            ])
            // 決済情報送信先URL
            ->add('cvs_url', TextType::class, [
                'label' => trans('remise_payment4.admin_config.label.cvs_url'),
                'attr' => [
                    'placeholder' => trans('remise_payment4.admin_config.label.example') . ': https://...',
                ],
                'required' => false,
            ])
            // 支払期限
            ->add('pay_date', ChoiceType::class, [
                'label' => trans('remise_payment4.admin_config.label.pay_date'),
                'required' => false,
                'choices'  => $paydate,
            ])
            // 入金お知らせメール
            ->add('acpt_mail_flag', ChoiceType::class, [
                'label' => trans('remise_payment4.admin_config.label.acpt_mail_flag'),
                'required' => false,
                'choices'  => [
                    trans('remise_payment4.admin_config.label.acpt_mail_flag.use') => '1',
                    trans('remise_payment4.admin_config.label.acpt_mail_flag.notuse') => '0',
                ],
                'expanded' => true,
            ])
            // 拡張セットホスト番号
            ->add('extset_hostid', TextType::class, [
                'label' => trans('remise_payment4.extset.admin_config.label.extset_hostid'),
                'attr' => [
                    'placeholder' => trans('remise_payment4.admin_config.label.example') . ': 00000003',
                ],
                'required' => false,
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => '/^[A-Z0-9]{5}[0-9]{3}$/',
                        'message' => trans('remise_payment4.extset.admin_config.text.extset_hostid.unmatch.length'),
                    ]),
                ],
            ])
            // 拡張セット結果通知URL
            ->add('extset_recv_url', TextType::class, [
                'label' => trans('remise_payment4.extset.admin_config.label.extset_recv_url'),
                'mapped' => false,
            ])
            // 拡張セット決済情報送信先URL
            ->add('extset_card_url', TextType::class, [
                'label' => trans('remise_payment4.extset.admin_config.label.extset_card_url'),
                'attr' => [
                    'placeholder' => trans('remise_payment4.admin_config.label.example') . ': https://...',
                ],
                'required' => false,
            ])
            // 定期購買結果通知URL
            ->add('ac_recv_url', TextType::class, [
                'label' => trans('remise_payment4.extset.admin_config.label.ac_recv_url'),
                'mapped' => false,
            ])
            // 定期購買アプリケーションID
            ->add('ac_appid', TextType::class, [
                'label' => trans('remise_payment4.ac.admin_config.label.ac_appid'),
                'attr' => [
                    'placeholder' => trans('remise_payment4.admin_config.label.example') . ': A0000001',
                ],
                'required' => false,
                'constraints' => [
                     new Assert\Length(array('max' => 8, 'maxMessage' => trans('remise_payment4.ac.admin_config.text.ac_appid.unmatch.length'))),
                     new Assert\Length(array('min' => 8, 'minMessage' => trans('remise_payment4.ac.admin_config.text.ac_appid.unmatch.length'))),
                ],
            ])
            // 定期購買パスワード
            ->add('ac_password', TextType::class, [
                'label' => trans('remise_payment4.ac.admin_config.label.ac_password'),
                'required' => false,
            ])
            // 定期購買結果取込用URL
            ->add('ac_result_url', TextType::class, [
                'label' => trans('remise_payment4.ac.admin_config.label.ac_result_url'),
                'attr' => [
                    'placeholder' => trans('remise_payment4.admin_config.label.example') . ': https://...',
                ],
                'required' => false,
            ])
            // 定期購買更新用URL
            ->add('ac_edit_url', TextType::class, [
                'label' => trans('remise_payment4.ac.admin_config.label.ac_edit_url'),
                'attr' => [
                    'placeholder' => trans('remise_payment4.admin_config.label.example') . ': https://...',
                ],
                'required' => false,
            ])
            // 取込設定
            ->add('ac_acquisition_method', ChoiceType::class, [
                'label' => trans('remise_payment4.ac.admin_config.label.ac_acquisition_method'),
                'required' => false,
                'choices' => [
                    trans('remise_payment4.ac.admin_config.label.ac_acquisition_method_real') => 'real' ,
                    trans('remise_payment4.ac.admin_config.label.ac_acquisition_method_batch') => 'batch'
                ],
                'placeholder' => false,
                'expanded' => true,
                'data' => empty($ConfigInfo->getAcAcquisitionMethod()) ? 'real' : $ConfigInfo->getAcAcquisitionMethod()
            ])
            // 上限件数
            ->add('ac_max_number_acquisitions', TextType::class, [
                'label' => trans('remise_payment4.ac.admin_config.label.ac_max_number_acquisitions'),
                'required' => false,
                'constraints' => [
                    new Assert\Range([
                        'min' => 1,
                        'max' => 9999,
                        'minMessage' => trans('remise_payment4.ac.admin_config.label.ac_max_number_acquisitions.length'),
                        'maxMessage' => trans('remise_payment4.ac.admin_config.label.ac_max_number_acquisitions.length')
                    ])
                ]
            ])
            ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ConfigInfo::class,
            'constraints' => [
                new Callback([
                    'callback' => [$this, 'validate'],
                ]),
            ],
        ]);
    }

    public function validate(ConfigInfo $ConfigInfo, ExecutionContextInterface $context)
    {
        // カード決済利用時
        if ($ConfigInfo->usePaymentCard())
        {
            // 決済情報送信先URL
            if ($ConfigInfo->getCardUrl() == null || $ConfigInfo->getCardUrl() === "")
            {
                $context->buildViolation(trans('remise_payment4.admin_config.label.card_url.blank'))
                        ->atPath('card_url')
                        ->addViolation();
            }
            // 処理区分
            if ($ConfigInfo->getJob() == null || $ConfigInfo->getJob() === "")
            {
                $context->buildViolation(trans('remise_payment4.admin_config.label.job.blank'))
                        ->atPath('job')
                        ->addViolation();
            }
            // ペイクイック
            if ($ConfigInfo->getPayquickFlag() == null || $ConfigInfo->getPayquickFlag() === "")
            {
                $context->buildViolation(trans('remise_payment4.admin_config.label.payquick_flag.blank'))
                        ->atPath('payquick_flag')
                        ->addViolation();
            }

            // ペイクイック利用時
            if ($ConfigInfo->getPayquickFlag() === '1')
            {
                // 支払方法
                if ($ConfigInfo->getUseMethod() == null || empty($ConfigInfo->getUseMethod()))
                {
                    $context->buildViolation(trans('remise_payment4.admin_config.label.use_method.blank'))
                            ->atPath('use_method')
                            ->addViolation();
                }

                // 分割回数
                if ($ConfigInfo->getUseMethod() != null)
                {
                    $useMethod = $ConfigInfo->getUseMethod();
                    foreach ($useMethod as $key => $value)
                    {
                        if ($value === '61') {
                            if ($ConfigInfo->getPtimes() == null || empty($ConfigInfo->getPtimes()))
                            {
                                $context->buildViolation(trans('remise_payment4.admin_config.label.ptimes.blank'))
                                        ->atPath('ptimes')
                                        ->addViolation();
                            }
                        }
                    }
                }
            }
        }

        // マルチ決済利用時
        if ($ConfigInfo->usePaymentCvs())
        {
            // 決済情報送信先URL
            if ($ConfigInfo->getCvsUrl() == null || $ConfigInfo->getCvsUrl() === "")
            {
                $context->buildViolation(trans('remise_payment4.admin_config.label.cvs_url.blank'))
                        ->atPath('cvs_url')
                        ->addViolation();
            }
            // 支払期限
            if ($ConfigInfo->getPayDate() == null || $ConfigInfo->getPayDate() === "")
            {
                $context->buildViolation(trans('remise_payment4.admin_config.label.pay_date.blank'))
                        ->atPath('pay_date')
                        ->addViolation();
            }
            // 入金お知らせメール
            if ($ConfigInfo->getAcptMailFlag() == null || $ConfigInfo->getAcptMailFlag() === "")
            {
                $context->buildViolation(trans('remise_payment4.admin_config.label.acpt_mail_flag.blank'))
                        ->atPath('acpt_mail_flag')
                        ->addViolation();
            }
        }

        // 拡張セット利用時
        if ($ConfigInfo->useOptionExtset())
        {
            // 拡張セットホスト番号
            if ($ConfigInfo->getExtsetHostid() == null || $ConfigInfo->getExtsetHostid() === "")
            {
                $context->buildViolation(trans('remise_payment4.extset.admin_config.text.extset_hostid.blank'))
                ->atPath('extset_hostid')
                ->addViolation();
            }
            // 拡張セット決済情報送信先URL
            if ($ConfigInfo->getExtsetCardUrl() == null || $ConfigInfo->getExtsetCardUrl() === "")
            {
                $context->buildViolation(trans('remise_payment4.extset.admin_config.text.extset_card_url.blank'))
                ->atPath('extset_card_url')
                ->addViolation();
            }
        }

        // 定期購買のURL設定時
        if ($ConfigInfo->getAcResultUrl() != "" || $ConfigInfo->getAcEditUrl() != "")
        {
            // 定期購買アプリケーションID
            if ($ConfigInfo->getAcAppid() == null || $ConfigInfo->getAcAppid() === "")
            {
                $context->buildViolation(trans('remise_payment4.ac.admin_config.text.ac_appid.blank'))
                ->atPath('ac_appid')
                ->addViolation();
            }

            // 定期購買パスワード
            if ($ConfigInfo->getAcPassword() == null || $ConfigInfo->getAcPassword() === "")
            {
                $context->buildViolation(trans('remise_payment4.ac.admin_config.text.ac_password.blank'))
                ->atPath('ac_password')
                ->addViolation();
            }
        }

        // バッチ取込選択時
        if ($ConfigInfo->getAcAcquisitionMethod() === 'batch')
        {
            if ($ConfigInfo->getAcMaxNumberAcquisitions() == null || $ConfigInfo->getAcMaxNumberAcquisitions() === "")
            {
                $context->buildViolation(trans('remise_payment4.ac.admin_config.label.ac_max_number_acquisitions.blank'))
                ->atPath('ac_max_number_acquisitions')
                ->addViolation();
            }
        }
    }
}
