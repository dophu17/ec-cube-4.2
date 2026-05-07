<?php
namespace Plugin\RemisePayment42\Form\Type\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Plugin\RemisePayment42\Entity\RemiseACType;
use Eccube\Common\EccubeConfig;
use Eccube\Form\Type\ToggleSwitchType;
use Eccube\Form\Validator\TwigLint;

/**
 * 定期購買設定編集・登録画面
 */
class AcTypeEditType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(
        EccubeConfig $eccubeConfig
    ) {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // 名称
        $builder->add('name', TextType::class, [
            'label' => trans('remise_payment4.ac.admin_type_edit.label.block.edit.name.caption'),
            'required' => false,
            'constraints' => [
                new Assert\NotBlank([
                    'message' => trans('remise_payment4.ac.admin_type_edit.text.block.edit.name.blank')
                ])
            ]
        ]);
        // 購買回数
        $builder->add('count', TextType::class, [
            'label' => trans('remise_payment4.ac.admin_type_edit.label.block.edit.count.caption1'),
            'required' => false,
            'constraints' => [
                new Assert\Range([
                    'min' => 1,
                    'max' => 999,
                    'minMessage' => trans('remise_payment4.ac.admin_type_edit.text.block.edit.count.unmatch.length'),
                    'maxMessage' => trans('remise_payment4.ac.admin_type_edit.text.block.edit.count.unmatch.length')
                ])
            ]
        ]);
        // 購買回数無制限
        $builder->add('limitless', ChoiceType::class, [
            'label' => trans('remise_payment4.ac.admin_type_edit.label.block.edit.count.caption1'),
            'required' => false,
            'choices' => [
                trans('remise_payment4.ac.admin_type_edit.label.block.edit.count.caption3') => true,
                trans('remise_payment4.ac.admin_type_edit.label.block.edit.count.caption4') => false
            ],
            'placeholder' => false,
            'expanded' => true
        ]);
        // 課金間隔(数値)
        $builder->add('interval_value', TextType::class, [
            'label' => trans('remise_payment4.ac.admin_type_edit.label.block.edit.interval.caption1'),
            'required' => false,
            'constraints' => [
                new Assert\NotBlank([
                    'message' => trans('remise_payment4.ac.admin_type_edit.text.block.edit.interval.blank1')
                ]),
                new Assert\Range([
                    'min' => 1,
                    'max' => 999,
                    'minMessage' => trans('remise_payment4.ac.admin_type_edit.text.block.edit.interval.unmatch.length'),
                    'maxMessage' => trans('remise_payment4.ac.admin_type_edit.text.block.edit.interval.unmatch.length')
                ])
            ]
        ]);
        // 課金間隔(単位)
        $builder->add('interval_mark', ChoiceType::class, [
            'label' => trans('remise_payment4.ac.admin_type_edit.label.block.edit.interval.caption1'),
            'required' => false,
            'choices' => $this->getIntervalMarks(),
            'expanded' => false,
            'multiple' => false,
            'constraints' => [
                new Assert\NotBlank([
                    'message' => trans('remise_payment4.ac.admin_type_edit.text.block.edit.interval.blank2')
                ])
            ]
        ]);
        // 課金日
        $builder->add('day_of_month', ChoiceType::class, [
            'label' => trans('remise_payment4.ac.admin_type_edit.label.block.edit.day_of_month.caption1'),
            'required' => false,
            'choices' => $this->getDayOfMonths(),
            'expanded' => false,
            'multiple' => false
        ]);
        // 開始時期(数値)
        $builder->add('after_value', TextType::class, [
            'label' => trans('remise_payment4.ac.admin_type_edit.label.block.edit.after.caption1'),
            'required' => false,
            'constraints' => [
                new Assert\NotBlank([
                    'message' => trans('remise_payment4.ac.admin_type_edit.text.block.edit.after.blank1')
                ]),
                new Assert\Range([
                    'min' => 1,
                    'max' => 999,
                    'minMessage' => trans('remise_payment4.ac.admin_type_edit.text.block.edit.after.unmatch.length'),
                    'maxMessage' => trans('remise_payment4.ac.admin_type_edit.text.block.edit.after.unmatch.length')
                ])
            ]
        ]);
        // 開始時期(単位)
        $builder->add('after_mark', ChoiceType::class, [
            'label' => trans('remise_payment4.ac.admin_type_edit.label.block.edit.after.caption1'),
            'required' => false,
            'choices' => $this->getAfterMarks(),
            'expanded' => false,
            'multiple' => false,
            'constraints' => [
                new Assert\NotBlank([
                    'message' => trans('remise_payment4.ac.admin_type_edit.text.block.edit.after.blank2')
                ])
            ]
        ]);
        // スキップ
        $builder->add('skip', ChoiceType::class, [
            'label' => trans('remise_payment4.ac.admin_type_edit.label.block.edit.skip.caption'),
            'required' => false,
            'choices' => $this->getSkipFlgs(),
            'expanded' => false,
            'multiple' => false,
            'constraints' => [
                new Assert\NotBlank([
                    'message' => trans('remise_payment4.ac.admin_type_edit.text.block.edit.skip.blank')
                ])
            ]
        ]);
        // 定期購買の解約
        $builder->add('stop', ChoiceType::class, [
            'label' => trans('remise_payment4.ac.admin_type_edit.label.block.edit.stop.caption'),
            'required' => false,
            'choices' => $this->getStopFlgs(),
            'expanded' => false,
            'multiple' => false,
            'constraints' => [
                new Assert\NotBlank([
                    'message' => trans('remise_payment4.ac.admin_type_edit.text.block.edit.usage.blank2')
                ])
            ]
        ]);
        // 解約不許可の表示内容
        $builder->add('stop_display', TextType::class, [
            'label' => trans('remise_payment4.ac.admin_type_edit.label.block.edit.stop_display.caption'),
            'required' => false,
            'constraints' => [
                new Assert\Length([
                    'max' => $this->eccubeConfig['eccube_stext_len'],
                ])
            ]
        ]);        
        // 最低利用期間(数値)
        $builder->add('usage_value', TextType::class, [
            'label' => trans('remise_payment4.ac.admin_type_edit.label.block.edit.usage.caption1'),
            'required' => false,
            'constraints' => [
                new Assert\Range([
                    'min' => 0,
                    'max' => 999,
                    'minMessage' => trans('remise_payment4.ac.admin_type_edit.text.block.edit.usage.unmatch.length'),
                    'maxMessage' => trans('remise_payment4.ac.admin_type_edit.text.block.edit.usage.unmatch.length')
                ])
            ]
        ]);
        // 最低利用期間(単位)
        $builder->add('usage_mark', ChoiceType::class, [
            'label' => trans('remise_payment4.ac.admin_type_edit.label.block.edit.usage.caption1'),
            'required' => false,
            'choices' => $this->getUsageMarks(),
            'expanded' => false,
            'multiple' => false
        ]);

        // 補足説明
        $builder->add('free_area', TextareaType::class, [
            'label' => trans('remise_payment4.ac.admin_type_edit.label.block.edit.free_area.caption'),
            'purify_html' => true,
            'attr' => array(
                'placeholder' => 'remise_payment4.ac.admin_type_edit.text.block.edit.free_area.sample',
            ),
            'required' => false,
            'constraints' => [
                new TwigLint(),
                new Assert\Length(['max' => $this->eccubeConfig['eccube_lltext_len']]),
            ],
        ]);


        // 購入日
        $builder->add('order_date', DateType::class, array(
            'label' => trans('remise_payment4.ac.admin_type_edit.label.block.simulation.purchase date.caption'),
            'required' => false,
            'input' => 'datetime',
            'widget' => 'single_text',
            'data' => new \DateTime(),
            'mapped' => false,
            'attr' => [
                'class' => 'datetimepicker-input',
                'data-target' => '#ac_type_edit_order_date',
                'data-toggle' => 'datetimepicker',
            ],
        ));
    }

    /**
     *
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RemiseACType::class,
            'constraints' => [
                new Callback([
                    'callback' => [
                        $this,
                        'validate'
                    ]
                ])
            ]
        ]);
    }

    public function validate(RemiseACType $RemiseACType, ExecutionContextInterface $context)
    {
        // 名称
        if (strlen($RemiseACType->getName()) > 255) {
            $context->buildViolation(trans('remise_payment4.ac.admin_type_edit.text.block.edit.name.unmatch.length'))
                ->atPath('name')
                ->addViolation();
        }

        // 購買回数
        if (! $RemiseACType->isLimitless()) {
            if ($RemiseACType->getCount() == null || $RemiseACType->getCount() == '') {
                $context->buildViolation(trans('remise_payment4.ac.admin_type_edit.text.block.edit.count.blank'))
                    ->atPath('count')
                    ->addViolation();
            }
        }

        // 課金日
        if (strcmp($RemiseACType->getIntervalMark(), trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.interval_marks.m.key')) == 0) {
            if (! $RemiseACType->getDayOfMonth()) {
                $context->buildViolation(trans('remise_payment4.ac.admin_type_edit.text.block.edit.day_of_month.blank'))
                    ->atPath('day_of_month')
                    ->addViolation();
            }
        }

        // 開始時期
        if (strcmp($RemiseACType->getIntervalMark(), trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.interval_marks.m.key')) == 0) {
            if (strcmp($RemiseACType->getAfterMark(), trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.after_marks.m.key')) != 0) {
                $context->buildViolation(trans('remise_payment4.ac.admin_type_edit.text.block.edit.after.month_select_error'))
                    ->atPath('after_mark')
                    ->addViolation();
            }
        }

        // 解約不許可の表示内容
        if ($RemiseACType->getStop() == trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.stop.false.key')) {
            if ($RemiseACType->getStopDisplay() == null || $RemiseACType->getStopDisplay() == '') {
                $context->buildViolation(trans('remise_payment4.ac.admin_type_edit.text.block.edit.stop_display.blank'))
                    ->atPath('stop_display')
                    ->addViolation();
            }
        }

        // 最低利用期間
        if ($RemiseACType->getStop() == trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.stop.usage.key')) {
            if ($RemiseACType->getUsageValue() == null || $RemiseACType->getUsageValue() == '') {
                $context->buildViolation(trans('remise_payment4.ac.admin_type_edit.text.block.edit.usage.blank1'))
                    ->atPath('usage_value')
                    ->addViolation();
            }
            if (! $RemiseACType->getUsageMark()) {
                $context->buildViolation(trans('remise_payment4.ac.admin_type_edit.text.block.edit.usage.blank2'))
                    ->atPath('usage_mark')
                    ->addViolation();
            }
        }
    }

    /**
     * 課金間隔一覧の取得
     *
     * @return array 課金間隔一覧
     */
    private static function getIntervalMarks()
    {
        $intervalMarks = array(
            '--' => '',
            trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.interval_marks.m.value') => trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.interval_marks.m.key'),
            trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.interval_marks.d.value') => trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.interval_marks.d.key')
        );
        return $intervalMarks;
    }

    /**
     * 課金日一覧の取得
     *
     * @return array 課金日一覧
     */
    private static function getDayOfMonths()
    {
        $dayOfMonths = array();
        $dayOfMonths['--'] = '';
        for ($i = 1; $i <= 31; $i ++) {
            $dayOfMonths[$i] = $i;
        }
        $dayOfMonths[trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.day_of_month.99.value')] = 99;
        return $dayOfMonths;
    }

    /**
     * 開始時期一覧の取得
     *
     * @return array 開始時期一覧
     */
    private static function getAfterMarks()
    {
        $afterMarks = array(
            '--' => '',
            trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.after_marks.m.value') => trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.after_marks.m.key'),
            trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.after_marks.d.value') => trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.after_marks.d.key')
        );
        return $afterMarks;
    }

    /**
     * 定期購買のスキップ一覧の取得
     *
     * @return array 定期購買のスキップ一覧
     */
    private static function getSkipFlgs()
    {
        $skipFlgs = array(
            '--' => '',
            trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.skip.true.value') => trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.skip.true.key'),
            trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.skip.false.value') => trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.skip.false.key')
        );
        return $skipFlgs;
    }

    /**
     * 定期購買の解約一覧の取得
     *
     * @return array 定期購買の解約一覧
     */
    private static function getStopFlgs()
    {
        $stopFlgs = array(
            '--' => '',
            trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.stop.true.value') => trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.stop.true.key'),
            trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.stop.false.value') => trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.stop.false.key'),
            trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.stop.usage.value') => trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.stop.usage.key')
        );
        return $stopFlgs;
    }

    /**
     * 最低利用期間一覧の取得
     *
     * @return array 最低利用期間一覧
     */
    private static function getUsageMarks()
    {
        $usageMarks = array(
            '--' => '',
            trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.usage_mark.m.value') => trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.usage_mark.m.key'),
            trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.usage_mark.d.value') => trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.usage_mark.d.key')
        );
        return $usageMarks;
    }
}
