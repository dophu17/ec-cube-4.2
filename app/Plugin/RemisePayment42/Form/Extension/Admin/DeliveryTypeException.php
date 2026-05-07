<?php
namespace Plugin\RemisePayment42\Form\Extension\Admin;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Eccube\Form\Type\Admin\DeliveryType;
use Eccube\Form\Type\Front\EntryType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Plugin\RemisePayment42\Repository\RemiseSaleTypeRepository;
use Plugin\RemisePayment42\Service\UtilService;

class DeliveryTypeException extends AbstractTypeExtension
{

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
     *
     * @param EntityManagerInterface $entityManager
     * @param RemiseSaleTypeRepository $remiseSaleTypeRepository
     * @param UtilService $utilService
     */
    public function __construct(EntityManagerInterface $entityManager, RemiseSaleTypeRepository $remiseSaleTypeRepository, UtilService $utilService)
    {
        $this->entityManager = $entityManager;
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

        // サブミット時の入力チェック
        $builder->addEventListener(FormEvents::POST_SUBMIT, function ($event) {
            $form = $event->getForm();

            $saleType = $form->get('sale_type')
            ->getViewData();

            $payments = $form->get('payments')
            ->getViewData();

            $RemiseSaleType = $this->remiseSaleTypeRepository->findOneBy([
                'id' => $saleType
            ]);

            // 販売種別が定期購買商品の場合のみチェック
            if ($RemiseSaleType) {

                $RemisePayment = $this->utilService->getRemisePaymentByKind(trans('remise_payment4.common.label.payment.kind.card'));
                if(!$RemisePayment){
                    return;
                }

                $hitFlg = false;
                foreach ($payments as $payment){
                    if(strcmp($payment,$RemisePayment[0]->getId()) != 0){
                        $form['payments']->addError(new FormError(trans('remise_payment4.ac.admin_delivery_edit.text.payments_err')));
                        return;
                    }else{
                        $hitFlg = true;
                    }
                }
                if(!$hitFlg){
                    $form['payments']->addError(new FormError(trans('remise_payment4.ac.admin_delivery_edit.text.payments_err')));
                }

            }else{
                return;
            }
        });
    }

    /**
     *
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return DeliveryType::class;
    }
    
    /**
     * Return the class of the type being extended.
     */
    public static function getExtendedTypes(): iterable
    {
        return [DeliveryType::class];
    }
}
