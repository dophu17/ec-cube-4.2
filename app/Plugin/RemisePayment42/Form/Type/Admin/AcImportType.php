<?php
namespace Plugin\RemisePayment42\Form\Type\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * 定期購買取込一覧画面
 */
class AcImportType extends AbstractType
{

    /**
     *
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // メンバーID（ルミーズ発番のID）
        $builder->add('member_id', TextType::class, [
            'label' => trans('remise_payment4.ac.admin_import_index.label.import_form.member_id'),
            'constraints' => array(
                new Assert\Regex(array(
                    'pattern' => "/^[a-zA-Z0-9]+$/u",
                    'message' => trans('remise_payment4.ac.admin_import_index.label.import_form.member_id.regex'),
                )),
            ),
            'required' => false,
            'mapped' => false,
        ]);

        // 取込対象の課金日
        $builder->add('import_date', DateType::class, [
            'label' => trans('remise_payment4.ac.admin_import_index.label.import_form.import_date'),
            'required' => false,
            'mapped' => false,
            'input' => 'datetime',
            'widget' => 'single_text',
            'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
            'constraints' => [
                new Assert\NotBlank([
                    'message' => trans('remise_payment4.ac.admin_import_index.label.import_form.import_date.blank')
                ]),
            ],
            'attr' => [
                'class' => 'datetimepicker-input',
                'data-target' => '#'.$this->getBlockPrefix().'_import_date',
                'data-toggle' => 'datetimepicker',
            ],
            'data' => new \DateTime()
        ]);

        // 取込件数
        $builder->add('import_count_choice', ChoiceType::class, [
            'label' => trans('remise_payment4.ac.admin_import_index.label.import_form.import_count'),
            'required' => false,
            'mapped' => false,
            'choices' => [
                trans('remise_payment4.ac.admin_import_index.label.import_form.import_count_all') => 'all' ,
                trans('remise_payment4.ac.admin_import_index.label.import_form.import_count_set') => 'set'
            ],
            'placeholder' => false,
            'expanded' => true,
            'data' => 'all'
        ]);

        // 取込開始件数
        $builder->add('import_count_start', TextType::class, [
            'label' => trans('remise_payment4.ac.admin_import_index.label.import_form.import_count_start'),
            'required' => false,
            'mapped' => false,
            'constraints' => [
                new Assert\NotBlank([
                    'message' => trans('remise_payment4.ac.admin_import_index.label.import_form.import_count_start.blank')
                ]),
                new Assert\Range([
                    'min' => 1,
                    'max' => 999999,
                    'minMessage' => trans('remise_payment4.ac.admin_import_index.label.import_form.import_count_start.length'),
                    'maxMessage' => trans('remise_payment4.ac.admin_import_index.label.import_form.import_count_start.length')
                ])
            ],
            'data' => '1'
        ]);

        // 取込終了件数
        $builder->add('import_count_end', TextType::class, [
            'label' => trans('remise_payment4.ac.admin_import_index.label.import_form.import_count_end'),
            'required' => false,
            'mapped' => false,
            'constraints' => [
                new Assert\NotBlank([
                    'message' => trans('remise_payment4.ac.admin_import_index.label.import_form.import_count_end.blank')
                ]),
                new Assert\Range([
                    'min' => 1,
                    'max' => 999999,
                    'minMessage' => trans('remise_payment4.ac.admin_import_index.label.import_form.import_count_end.length'),
                    'maxMessage' => trans('remise_payment4.ac.admin_import_index.label.import_form.import_count_end.length')
                ])
            ],
            'data' => '999999'
        ]);

    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'ac_import';
    }
}
