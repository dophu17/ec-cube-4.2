<?php
namespace Plugin\RemisePayment42\Form\Type\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Eccube\Form\Type\ToggleSwitchType;
use Eccube\Form\Type\PriceType;

/**
 * 定期購買管理画面
 */
class SearchAcManagementType extends AbstractType
{

    /**
     *
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // 会員番号・会員名・メンバーID
        $builder->add('multi', TextType::class, [
            'label' => trans('remise_payment4.ac.admin_management_index.label.search.multi'),
            'required' => false,
        ]);

        // 定期購買の状態
        $builder->add('status', ChoiceType::class, [
            'label' => trans('remise_payment4.ac.admin_management_index.label.search.status'),
            'choices' => array(
                '-' => '',
                'remise_payment4.ac.plg_remise_payment4_remise_ac_member.status.1' => '1',
                'remise_payment4.ac.plg_remise_payment4_remise_ac_member.status.0' => '0',
            ),
            'multiple' => false,
            'expanded' => false,
            'required' => false,
        ]);

        // メンバーID（ルミーズ発番のID）
        $builder->add('member_id', TextType::class, [
            'label' => trans('remise_payment4.ac.admin_management_index.label.search.member_id'),
            'constraints' => array(
                new Assert\Regex(array(
                    'pattern' => "/^[a-zA-Z0-9]+$/u",
                    'message' => trans('remise_payment4.ac.admin_management_index.label.search.member_id.regex'),
                )),
            ),
            'required' => false,
        ]);

        // 注文番号
        $builder->add('order_no', TextType::class, [
            'label' => trans('remise_payment4.ac.admin_management_index.label.search.order_no'),
            'constraints' => array(
                new Assert\Regex(array(
                    'pattern' => "/^[\d]+$/u",
                    'message' => trans('remise_payment4.ac.admin_management_index.label.search.order_no.regex'),
                )),
            ),
            'required' => false,
        ]);

        // 会員番号
        $builder->add('customer_id', TextType::class, [
            'label' => trans('remise_payment4.ac.admin_management_index.label.search.customer_id'),
            'constraints' => array(
                new Assert\Regex(array(
                    'pattern' => "/^[\d]+$/u",
                    'message' => trans('remise_payment4.ac.admin_management_index.label.search.customer_id.regex'),
                )),
            ),
            'required' => false,
        ]);

        // 会員名
        $builder->add('customer_name', TextType::class, [
            'label' => trans('remise_payment4.ac.admin_management_index.label.search.customer_name'),
            'required' => false,
        ]);

        // 会員名(フリガナ)
        $builder->add('customer_kana', TextType::class, [
            'label' => trans('remise_payment4.ac.admin_management_index.label.search.customer_kana'),
            'constraints' => array(
                new Assert\Regex(array(
                    'pattern' => "/^[ァ-ヶｦ-ﾟー]+$/u",
                    'message' => trans('remise_payment4.ac.admin_management_index.label.search.customer_kana.regex'),
                )),
            ),
            'required' => false,
        ]);

        // メールアドレス
        $builder->add('customer_mail', EmailType::class, [
            'label' => trans('remise_payment4.ac.admin_management_index.label.search.customer_mail'),
            'required' => false,
        ]);

        // 電話番号
        $builder->add('customer_tel', TextType::class, [
            'label' => trans('remise_payment4.ac.admin_management_index.label.search.customer_tel'),
            'constraints' => array(
                new Assert\Regex(array(
                    'pattern' => "/^[\d]+$/u",
                    'message' => trans('remise_payment4.ac.admin_management_index.label.search.customer_tel.regex'),
                )),
            ),
            'required' => false,
        ]);

        // 購入商品名
        $builder->add('product_name', TextType::class, [
            'label' => trans('remise_payment4.ac.admin_management_index.label.search.product_name'),
            'required' => false,
        ]);

        // 定期購買金額
        $builder->add('total_start', PriceType::class, [
            'label' => trans('remise_payment4.ac.admin_management_index.label.search.total_start'),
            'required' => false,
        ]);

        // 定期購買金額
        $builder->add('total_end', PriceType::class, [
            'label' => trans('remise_payment4.ac.admin_management_index.label.search.total_end'),
            'required' => false,
        ]);

        // 受注日
        $builder->add('order_date_start', DateType::class, [
            'label' => trans('remise_payment4.ac.admin_management_index.label.search.order_date_start'),
            'required' => false,
            'input' => 'datetime',
            'widget' => 'single_text',
            'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
            'attr' => [
                'class' => 'datetimepicker-input',
                'data-target' => '#'.$this->getBlockPrefix().'_order_date_start',
                'data-toggle' => 'datetimepicker',
            ],
        ]);

        // 受注日
        $builder->add('order_date_end', DateType::class, [
            'label' => trans('remise_payment4.ac.admin_management_index.label.search.order_date_end'),
            'required' => false,
            'input' => 'datetime',
            'widget' => 'single_text',
            'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
            'attr' => [
                'class' => 'datetimepicker-input',
                'data-target' => '#'.$this->getBlockPrefix().'_order_date_end',
                'data-toggle' => 'datetimepicker',
            ],
        ]);

        // 次回課金日
        $builder->add('next_date_start', DateType::class, [
            'label' => trans('remise_payment4.ac.admin_management_index.label.search.next_date_start'),
            'required' => false,
            'input' => 'datetime',
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd',
            'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
            'attr' => [
                'class' => 'datetimepicker-input',
                'data-target' => '#'.$this->getBlockPrefix().'_next_date_start',
                'data-toggle' => 'datetimepicker',
            ],
        ]);

        // 次回課金日
        $builder->add('next_date_end', DateType::class, [
            'label' => trans('remise_payment4.ac.admin_management_index.label.search.next_date_end'),
            'required' => false,
            'input' => 'datetime',
            'widget' => 'single_text',
            'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
            'attr' => [
                'class' => 'datetimepicker-input',
                'data-target' => '#'.$this->getBlockPrefix().'_next_date_end',
                'data-toggle' => 'datetimepicker',
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'search_ac_management';
    }
}
