<?php
namespace Plugin\RemisePayment42\Form\Type\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Plugin\RemisePayment42\Entity\RemiseACMember;
use Eccube\Form\Type\ToggleSwitchType;
use Eccube\Form\Type\PriceType;
use Plugin\RemisePayment42\Service\AcApiService;

/**
 * 定期購買設定編集・登録画面
 */
class AcManagementEditType extends AbstractType
{

    /**
     *
     * @var AcApiService
     */
    protected $acApiService;

    /**
     * AcManagementEditType constructor.
     *
     * @param AcApiService $acApiService
     */
    public function __construct(AcApiService $acApiService)
    {
        $this->acApiService = $acApiService;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        // 定期購買更新用URLの有効有無を取得
        $useKeizokuEditExtend = $this->acApiService->useKeizokuEditExtend();

        // 処理モード
        $builder->add('mode', HiddenType::class, [
            'mapped' => false,
        ]);
        // 停止オプション
        $builder->add('stop_option', ToggleSwitchType::class, [
            'mapped' => false,
        ]);
        // 再開オプション
        $builder->add('restart_option', ToggleSwitchType::class, [
            'mapped' => false,
        ]);
        // 更新オプション
        $builder->add('update_option', ToggleSwitchType::class, [
            'mapped' => false,
            'data' => $useKeizokuEditExtend,
        ]);
        // 次回課金日
        $builder->add('next_date', DateType::class, [
            'label' => trans('remise_payment4.ac.admin_management_edit.label.block.acmember.next_date'),
            'required' => false,
            'input' => 'datetime',
            'widget' => 'single_text',
            'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
            'attr' => [
                'class' => 'datetimepicker-input',
                'data-target' => '#'.$this->getBlockPrefix().'_next_date',
                'data-toggle' => 'datetimepicker',
            ],
        ]);
        // 定期購買金額
        $builder->add('total', PriceType::class, [
            'label' => trans('remise_payment4.ac.admin_management_edit.label.block.acmember.total'),
            'required' => false,
        ]);
        // 購買回数
        $builder->add('count', TextType::class, [
            'label' => trans('remise_payment4.ac.admin_management_edit.label.block.acmember.count.caption1'),
            'required' => false,
            'constraints' => [
                new Assert\Range([
                    'min' => 1,
                    'max' => 999,
                    'minMessage' => trans('remise_payment4.ac.admin_type_edit.text.block.acmember.count.unmatch.length'),
                    'maxMessage' => trans('remise_payment4.ac.admin_type_edit.text.block.acmember.count.unmatch.length')
                ])
            ]
        ]);
        // 購買回数無制限
        $builder->add('limitless', ChoiceType::class, [
            'label' => trans('remise_payment4.ac.admin_management_edit.label.block.acmember.count.caption1'),
            'required' => false,
            'choices' => [
                trans('remise_payment4.ac.admin_management_edit.label.block.acmember.count.caption3') => true,
                trans('remise_payment4.ac.admin_management_edit.label.block.acmember.count.caption4') => false
            ],
            'placeholder' => false,
            'expanded' => true
        ]);
        // 課金間隔(数値)
        $builder->add('interval_value', TextType::class, [
            'label' => trans('remise_payment4.ac.admin_management_edit.label.block.acmember.interval.caption1'),
            'required' => false,
            'constraints' => [
                new Assert\NotBlank([
                    'message' => trans('remise_payment4.ac.admin_management_edit.text.block.acmember.interval.blank1')
                ]),
                new Assert\Range([
                    'min' => 1,
                    'max' => 999,
                    'minMessage' => trans('remise_payment4.ac.admin_management_edit.text.block.acmember.interval.unmatch.length'),
                    'maxMessage' => trans('remise_payment4.ac.admin_management_edit.text.block.acmember.interval.unmatch.length')
                ])
            ]
        ]);
        // 課金間隔(単位)
        $builder->add('interval_mark', ChoiceType::class, [
            'label' => trans('remise_payment4.ac.admin_management_edit.label.block.acmember.interval.caption1'),
            'required' => false,
            'choices' => $this->getIntervalMarks(),
            'expanded' => false,
            'multiple' => false,
            'constraints' => [
                new Assert\NotBlank([
                    'message' => trans('remise_payment4.ac.admin_management_edit.text.block.acmember.interval.blank2')
                ])
            ]
        ]);
        // スキップ
        $builder->add('skip', ChoiceType::class, [
            'label' => trans('remise_payment4.ac.admin_management_edit.label.block.acmember.skip.caption'),
            'required' => false,
            'choices' => $this->getSkipFlgs(),
            'expanded' => false,
            'multiple' => false,
            'constraints' => [
                new Assert\NotBlank([
                    'message' => trans('remise_payment4.ac.admin_management_edit.text.block.acmember.skip.blank')
                ])
            ]
        ]);
        // 定期購買の解約
        $builder->add('stop', ChoiceType::class, [
            'label' => trans('remise_payment4.ac.admin_management_edit.label.block.acmember.stop.caption'),
            'required' => false,
            'choices' => $this->getStopFlgs(),
            'expanded' => false,
            'multiple' => false,
            'constraints' => [
                new Assert\NotBlank([
                    'message' => trans('remise_payment4.ac.admin_management_edit.text.block.acmember.usage.blank2')
                ])
            ]
        ]);
        // 最低利用期間(数値)
        $builder->add('usage_value', TextType::class, [
            'label' => trans('remise_payment4.ac.admin_management_edit.label.block.acmember.usage.caption1'),
            'required' => false,
            'constraints' => [
                new Assert\Range([
                    'min' => 0,
                    'max' => 999,
                    'minMessage' => trans('remise_payment4.ac.admin_management_edit.text.block.acmember.usage.unmatch.length'),
                    'maxMessage' => trans('remise_payment4.ac.admin_management_edit.text.block.acmember.usage.unmatch.length')
                ])
            ]
        ]);
        // 最低利用期間(単位)
        $builder->add('usage_mark', ChoiceType::class, [
            'label' => trans('remise_payment4.ac.admin_management_edit.label.block.acmember.usage.caption1'),
            'required' => false,
            'choices' => $this->getUsageMarks(),
            'expanded' => false,
            'multiple' => false
        ]);
        // メモ欄
        $builder->add('note', TextareaType::class, [
            'label' => trans('remise_payment4.ac.admin_management_edit.label.block.acmember.note.caption'),
            'required' => false,
            'attr' => ['rows' => 5],
        ]);

        // サブミット時の入力チェック
        $builder->addEventListener(FormEvents::POST_SUBMIT, function ($event) {

            // 更新オプションチェック
            $form = $event->getForm();
            $mode = $form->get('mode')->getViewData();

            if ($mode != "note"){

                $update_option = $form->get('update_option')
                ->getViewData();
                if ($update_option) {
                    $useKeizokuEditExtend = $this->acApiService->useKeizokuEditExtend();
                    if(!$useKeizokuEditExtend){
                        $form['update_option']->addError(new FormError(trans('remise_payment4.ac.admin_management_edit.text.block.option.update_option.error')));
                    }
                }

                if($mode != "stop"){
                    // 購買回数
                    $limitless = $form->get('limitless')->getViewData();
                    $count = $form->get('count')->getViewData();
                    if (! $limitless) {
                        if ($count == null || $count == '') {
                            $form['count']->addError(new FormError(trans('remise_payment4.ac.admin_management_edit.text.block.acmember.count.blank')));
                        }
                    }

                    // 最低利用期間
                    $stop = $form->get('stop')->getViewData();
                    $usageValue = $form->get('usage_value')->getViewData();
                    $usageMark = $form->get('usage_mark')->getViewData();
                    if ($stop == trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.stop.usage.key')) {
                        if ($usageValue == null || $usageValue == '') {
                            $form['usage_value']->addError(new FormError(trans('remise_payment4.ac.admin_management_edit.text.block.acmember.usage.blank1')));
                        }
                        if (! $usageMark) {
                            $form['usage_mark']->addError(new FormError(trans('remise_payment4.ac.admin_management_edit.text.block.acmember.usage.blank2')));
                        }
                    }

                    // 次回課金日
                    $nextDate = $form->get('next_date')->getViewData();
                    if ($nextDate) {
                        $nextDateStr = str_replace("-", "", $nextDate);
                        $nowDateStr = (new \DateTime())->format('Ymd');
                        // 過去日判定
                        if (strcmp($nextDateStr, $nowDateStr) <= 0) {
                            $form['next_date']->addError(new FormError(trans('remise_payment4.ac.admin_management_edit.label.block.acmember.next_date.error')));
                        }
                    }
                }
            }
        });

    }

    /**
     *
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RemiseACMember::class,
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

    public function validate(RemiseACMember $RemiseACMember, ExecutionContextInterface $context)
    {
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

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'ac_management_edit';
    }
}
