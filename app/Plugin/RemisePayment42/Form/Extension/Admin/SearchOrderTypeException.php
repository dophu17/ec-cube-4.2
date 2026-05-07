<?php
namespace Plugin\RemisePayment42\Form\Extension\Admin;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Eccube\Form\Type\Admin\SearchOrderType;
use Eccube\Form\Type\Front\EntryType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Plugin\RemisePayment42\Service\UtilService;

class SearchOrderTypeException extends AbstractTypeExtension
{
    /**
     *
     * @var UtilService
     */
    protected $utilService;

    /**
     * MasterdataType constructor.
     *
     * @param UtilService $utilService
     */
    public function __construct(
        UtilService $utilService)
    {
        $this->utilService = $utilService;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        // プラグイン設定情報の取得
        $Config = $this->utilService->getConfig();
        if (! $Config) {
            return;
        }

        // 設定情報の取得
        $ConfigInfo = $Config->getUnserializeInfo();
        if (! $ConfigInfo) {
            return;
        }

        // 定期購買が無効の場合
        if (! $ConfigInfo->useOptionAC()) {
            return;
        }

        // form設定:自動継続課金結果
        $builder->add('remise_payment4_ac_result_status', ChoiceType::class, [
            'label' => trans('remise_payment4.ac.admin_order_index.label.remise_payment4_ac_result_status'),
            'required' => false,
            'choices' => [
                trans('remise_payment4.ac.plg_remise_payment4_remise_ac_result.result.success.name')
                => trans('remise_payment4.ac.plg_remise_payment4_remise_ac_result.result.success.key'),
                trans('remise_payment4.ac.plg_remise_payment4_remise_ac_result.result.failure.name')
                => trans('remise_payment4.ac.plg_remise_payment4_remise_ac_result.result.failure.key'),
            ],
            'expanded' => true,
            'multiple' => true,
            'mapped' => false,
        ]);

        // form設定:メンバーID(ルミーズ発番のID)
        $builder->add('remise_payment4_ac_member_id', TextType::class, [
            'label' => trans('remise_payment4.ac.admin_order_index.label.remise_payment4_ac_member_id'),
            'required' => false,
            'constraints' => array(
                new Assert\Regex(array(
                    'pattern' => "/^[a-zA-Z0-9]+$/u",
                    'message' => trans('remise_payment4.ac.admin_order_index.label.remise_payment4_ac_member_id.regex'),
                )),
            ),
            'mapped' => false,
        ]);
    }

    /**
     *
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return SearchOrderType::class;
    }
    
    /**
     * Return the class of the type being extended.
     */
    public static function getExtendedTypes(): iterable
    {
        return [SearchOrderType::class];
    }
}
