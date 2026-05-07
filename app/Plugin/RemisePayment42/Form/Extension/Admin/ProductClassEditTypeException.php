<?php
namespace Plugin\RemisePayment42\Form\Extension\Admin;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Eccube\Form\Type\Admin\ProductClassEditType;
use Eccube\Form\Type\Front\EntryType;
use Eccube\Form\Type\PriceType;
use Eccube\Form\Type\ToggleSwitchType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Plugin\RemisePayment42\Repository\RemiseACTypeRepository;
use Plugin\RemisePayment42\Repository\RemiseSaleTypeRepository;
use Plugin\RemisePayment42\Service\UtilService;

class ProductClassEditTypeException extends AbstractTypeExtension
{

    /**
     *
     * @var RemiseACTypeRepository
     */
    protected $remiseACTypeRepository;

    /**
     *
     * @var RemiseSaleTypeRepository
     */
    protected $remiseSaleTypeRepository;

    /**
     *
     * @var UtilService
     */
    protected $utilService;

    /**
     * MasterdataType constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param RemiseACTypeRepository $remiseACTypeRepository
     * @param RemiseSaleTypeRepository $remiseSaleTypeRepository
     * @param UtilService $utilService
     */
    public function __construct(EntityManagerInterface $entityManager, RemiseACTypeRepository $remiseACTypeRepository, RemiseSaleTypeRepository $remiseSaleTypeRepository, UtilService $utilService)
    {
        $this->entityManager = $entityManager;
        $this->remiseACTypeRepository = $remiseACTypeRepository;
        $this->remiseSaleTypeRepository = $remiseSaleTypeRepository;
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

        // ルミーズ定期購買種別取得
        $remiseACTypes = $this->remiseACTypeRepository->findBy([
            'visible' => '1'
        ], [
            'sort_no' => 'DESC'
        ]);
        $actypes = Array();
        foreach ($remiseACTypes as $remiseACType) {
            $actypes[$remiseACType->getName()] = $remiseACType->getId();
        }

        // form設定:定期購買金額
        $builder->add('remise_payment4_ac_amount', PriceType::class, [
            'label' => trans('remise_payment4.ac.admin_product.label.amount.caption'),
            'required' => false,
            'constraints' => [
                new Assert\Range([
                    'min' => 1,
                    'max' => 99999999,
                    'minMessage' => trans('remise_payment4.ac.admin_product.text.amount.unmatch.length'),
                    'maxMessage' => trans('remise_payment4.ac.admin_product.text.amount.unmatch.length')
                ])
            ],
        ]);

        // form設定:定期購買設定
        $builder->add('remise_payment4_ac_actype_id', ChoiceType::class, [
            'label' => trans('remise_payment4.ac.admin_product.label.actype.caption'),
            'required' => false,
            'choices' => $actypes,
            'expanded' => false,
            'multiple' => false,
        ]);

        // form設定:定期購買設定
        $builder->add('remise_payment4_ac_point_flg', ToggleSwitchType::class, [
            'label' => trans('remise_payment4.ac.admin_product.label.acpoint.caption'),
            'required' => false,
        ]);

        // サブミット時の入力チェック
        $builder->addEventListener(FormEvents::POST_SUBMIT, function ($event) {
            $form = $event->getForm();

            $saleType = $form->get('sale_type')
                ->getViewData();

            $RemiseSaleType = $this->remiseSaleTypeRepository->findOneBy([
                'id' => $saleType
            ]);

            // 販売種別が定期購買商品の場合のみチェック
            if ($RemiseSaleType) {
                // 定期購買金額
                $acAmount = $form->get('remise_payment4_ac_amount')
                    ->getViewData();
                if ($acAmount == "") {
                    $form['remise_payment4_ac_amount']->addError(new FormError(trans('remise_payment4.ac.admin_product.text.amount.blank')));
                }

                // 定期購買設定
                $acAcType = $form->get('remise_payment4_ac_actype_id')
                    ->getViewData();
                if ($acAcType == "") {
                    $form['remise_payment4_ac_actype_id']->addError(new FormError(trans('remise_payment4.ac.admin_product.text.actype.blank')));
                }
            }else{
                // 定期購買金額
                $acAmount = $form->get('remise_payment4_ac_amount')
                ->getViewData();
                if ($acAmount != "") {
                    $form['remise_payment4_ac_amount']->addError(new FormError(trans('remise_payment4.ac.admin_product.text.amount.error')));
                }

                // 定期購買設定
                $acAcType = $form->get('remise_payment4_ac_actype_id')
                ->getViewData();
                if ($acAcType != "") {
                    $form['remise_payment4_ac_actype_id']->addError(new FormError(trans('remise_payment4.ac.admin_product.text.actype.error')));
                }
            }
        });
    }

    /**
     *
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ProductClassEditType::class;
    }
    
    /**
     * Return the class of the type being extended.
     */
    public static function getExtendedTypes(): iterable
    {
        return [ProductClassEditType::class];
    }
}
