<?php
namespace Plugin\RemisePayment42\Service;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\ExportCsvRow;
use Eccube\Entity\MailHistory;
use Eccube\Entity\MailTemplate;
use Eccube\Entity\Master\SaleType;
use Eccube\Entity\Master\CsvType;
use Eccube\Entity\Delivery;
use Eccube\Entity\DeliveryFee;
use Eccube\Entity\Payment;
use Eccube\Entity\PaymentOption;
use Eccube\Entity\Csv;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Shipping;
use Eccube\Util\StringUtil;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Master\TaxDisplayType;
use Eccube\Repository\MailHistoryRepository;
use Eccube\Repository\MailTemplateRepository;
use Eccube\Repository\Master\SaleTypeRepository;
use Eccube\Repository\Master\CsvTypeRepository;
use Eccube\Repository\Master\PrefRepository;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Repository\DeliveryRepository;
use Eccube\Repository\DeliveryFeeRepository;
use Eccube\Repository\PaymentRepository;
use Eccube\Repository\PaymentOptionRepository;
use Eccube\Repository\CsvRepository;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\ShippingRepository;
use Eccube\Repository\ProductClassRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\OrderItemRepository;
use Eccube\Repository\TaxRuleRepository;
use Eccube\Service\MailService;
use Eccube\Service\TaxRuleService;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\Processor\OrderNoProcessor;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Plugin\RemisePayment42\Entity\OrderResult;
use Plugin\RemisePayment42\Entity\OrderResultCard;
use Plugin\RemisePayment42\Entity\RemiseCsvType;
use Plugin\RemisePayment42\Entity\RemiseSaleType;
use Plugin\RemisePayment42\Entity\RemisePayment;
use Plugin\RemisePayment42\Entity\RemiseACType;
use Plugin\RemisePayment42\Entity\RemiseACMember;
use Plugin\RemisePayment42\Entity\RemiseACResult;
use Plugin\RemisePayment42\Entity\RemiseACImport;
use Plugin\RemisePayment42\Entity\RemiseACImportInfo;
use Plugin\RemisePayment42\Entity\RemiseACApiResult;
use Plugin\RemisePayment42\Repository\OrderResultRepository;
use Plugin\RemisePayment42\Repository\OrderResultCardRepository;
use Plugin\RemisePayment42\Repository\RemiseACImportStackRepository;
use Plugin\RemisePayment42\Repository\RemiseACImportStackResultRepository;
use Plugin\RemisePayment42\Repository\RemiseCsvTypeRepository;
use Plugin\RemisePayment42\Repository\RemiseSaleTypeRepository;
use Plugin\RemisePayment42\Repository\RemisePaymentRepository;
use Plugin\RemisePayment42\Repository\RemiseACTypeRepository;
use Plugin\RemisePayment42\Repository\RemiseACResultRepository;
use Plugin\RemisePayment42\Repository\RemiseACImportRepository;
use Plugin\RemisePayment42\Repository\RemiseACMemberRepository;
use Plugin\RemisePayment42\Service\LogService;
use Plugin\RemisePayment42\Service\UtilService;
use Plugin\RemisePayment42\Service\AcApiService;
use Plugin\RemisePayment42\Entity\RemiseACImportStack;
use Plugin\RemisePayment42\Entity\RemiseACImportStackResult;
use Twig\Environment as TwigEnvironment; // Twig\Environmentをインポート
/**
 * 定期購買処理
 */
class AcService
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /** @var BaseInfo */
    protected $baseInfo;

    /**
     *
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $entityManager;

    /**
     *
     * @var DeliveryRepository
     */
    protected $deliveryRepository;

    /**
     *
     * @var DeliveryFeeRepository
     */
    protected $deliveryFeeRepository;

    /**
     *
     * @var MailTemplateRepository
     *
     */
    protected $mailTemplateRepository;

    /**
     *
     * @var MailHistoryRepository
     *
     */
    protected $mailHistoryRepository;
    
    /**
     *
     * @var PrefRepository
     */
    protected $prefRepository;

    /**
     *
     * @var PaymentRepository
     */
    protected $paymentRepository;

    /**
     *
     * @var PaymentOptionRepository
     */
    protected $paymentOptionRepository;

    /**
     *
     * @var SaleTypeRepository
     */
    protected $saleTypeRepository;

    /**
     *
     * @var CsvTypeRepository
     */
    protected $csvTypeRepository;

    /**
     *
     * @var CsvRepository
     */
    protected $csvRepository;

    /**
     *
     * @var ShippingRepository
     */
    protected $shippingRepository;

    /**
     *
     * @var ProductClassRepository
     */
    protected $productClassRepository;

    /**
     *
     * @var TaxRuleService
     */
    protected $taxRuleService;

    /**
     *
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     *
     * @var OrderItemRepository
     */
    protected $orderItemRepository;

    /**
     * @var OrderStatusRepository
     */
    protected $orderStatusRepository;

    /**
     * @var TaxRuleRepository
     */
    protected $taxRuleRepository;

    /**
     * @var OrderNoProcessor
     */
    protected $orderNoProcessor;

    /**
     * @var TwigEnvironment;
     */
    protected $twig;

    /**
     * @var MailerInterface
     */
    protected $mailer;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     *
     * @var UtilService
     */
    protected $utilService;

    /**
     *
     * @var LogService
     */
    protected $logService;

    /**
     *
     * @var AcApiService
     */
    protected $acApiService;

    /**
     *
     * @var RemiseSaleTypeRepository
     */
    protected $remiseSaleTypeRepository;

    /**
     *
     * @var RemisePaymentRepository
     */
    protected $remisePaymentRepository;

    /**
     *
     * @var RemiseCsvTypeRepository
     */
    protected $remiseCsvTypeRepository;

    /**
     *
     * @var RemiseACTypeRepository
     */
    protected $remiseACTypeRepository;

    /**
     *
     * @var RemiseACResultRepository
     */
    protected $remiseACResultRepository;

    /**
     *
     * @var RemiseACImportRepository
     */
    protected $remiseACImportRepository;

    /**
     *
     * @var RemiseACMemberRepository
     */
    protected $remiseACMemberRepository;

    /**
     *
     * @var OrderResultRepository
     */
    protected $orderResultRepository;

    /**
     *
     * @var OrderResultCardRepository
     */
    protected $orderResultCardRepository;

    /**
     *
     * @var RemiseACImportStackRepository
     */
    protected $remiseACImportStackRepository;

    /**
     *
     * @var RemiseACImportStackResultRepository
     */
    protected $remiseACImportStackResultRepository;

    /**
     * @var string %kernel.project_dir%
     */
    private $projectRoot;

    /**
     * @var resource
     */
    protected $fp;

    /**
     * @var boolean
     */
    protected $closed = false;

    /**
     * @var \Closure
     */
    protected $convertEncodingCallBack;

    /**
     * コンストラクタ
     *
     * @param BaseInfoRepository $baseInfoRepository
     * @param EntityManagerInterface $entityManager
     * @param DeliveryRepository $deliveryRepository;
     * @param DeliveryFeeRepository $deliveryFeeRepository;
     * @param MailTemplateRepository $mailTemplateRepository
     * @param MailHistoryRepository $mailHistoryRepository
     * @param PrefRepository $prefRepository
     * @param PaymentRepository $paymentRepository
     * @param PaymentOptionRepository $paymentOptionRepository
     * @param SaleTypeRepository $saleTypeRepository
     * @param CsvTypeRepository $csvTypeRepository
     * @param CsvRepository $csvRepository
     * @param ShippingRepository $shippingRepository
     * @param ProductClassRepository $productClassRepository
     * @param OrderRepository $orderRepository
     * @param OrderItemRepository $orderItemRepository
     * @param TaxRuleRepository $taxRuleRepository
     * @param TaxRuleService $taxRuleService
     * @param OrderStatusRepository $orderStatusRepository
     * @param OrderNoProcessor $orderNoProcessor
     * @param TwigEnvironment  $twig
     * @param MailerInterface $mailer
     * @param MailService $mailService
     * @param UtilService $utilService
     * @param LogService $logService
     * @param AcApiService $acApiService
     * @param RemiseSaleTypeRepository $remiseSaleTypeRepository
     * @param RemisePaymentRepository $remisePaymentRepository
     * @param RemiseCsvTypeRepository $remiseCsvTypeRepository
     * @param RemiseACResultRepository $remiseACResultRepository
     * @param RemiseACMemberRepository $remiseACMemberRepository
     * @param RemiseACMemberRepository $remiseACMemberRepository
     * @param OrderResultRepository $orderResultRepository
     * @param OrderResultCardRepository $orderResultCardRepository
     * @param RemiseACImportStackRepository $remiseACImportStackRepository
     * @param RemiseACImportStackResultRepository $remiseACImportStackResultRepository
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(
        BaseInfoRepository $baseInfoRepository,
        EntityManagerInterface $entityManager,
        DeliveryRepository $deliveryRepository,
        DeliveryFeeRepository $deliveryFeeRepository,
        MailTemplateRepository $mailTemplateRepository,
        MailHistoryRepository $mailHistoryRepository,
        PrefRepository $prefRepository,
        PaymentRepository $paymentRepository,
        PaymentOptionRepository $paymentOptionRepository,
        SaleTypeRepository $saleTypeRepository,
        CsvTypeRepository $csvTypeRepository,
        CsvRepository $csvRepository,
        ShippingRepository $shippingRepository,
        ProductClassRepository $productClassRepository,
        TaxRuleRepository $taxRuleRepository,
        TaxRuleService $taxRuleService,
        OrderRepository $orderRepository,
        OrderItemRepository $orderItemRepository,
        OrderStatusRepository $orderStatusRepository,
        OrderNoProcessor $orderNoProcessor,
        TwigEnvironment $twig,
        MailerInterface $mailer,
        MailService $mailService,
        UtilService $utilService,
        LogService $logService,
        AcApiService $acApiService,
        RemiseSaleTypeRepository $remiseSaleTypeRepository,
        RemisePaymentRepository $remisePaymentRepository,
        RemiseCsvTypeRepository $remiseCsvTypeRepository,
        RemiseACTypeRepository $remiseACTypeRepository,
        RemiseACResultRepository $remiseACResultRepository,
        RemiseACImportRepository $remiseACImportRepository,
        RemiseACMemberRepository $remiseACMemberRepository,
        OrderResultRepository $orderResultRepository,
        OrderResultCardRepository $orderResultCardRepository,
        RemiseACImportStackRepository $remiseACImportStackRepository,
        RemiseACImportStackResultRepository $remiseACImportStackResultRepository,
        EccubeConfig $eccubeConfig) {
            $this->baseInfo = $baseInfoRepository->get();
            $this->entityManager = $entityManager;
            $this->deliveryRepository = $deliveryRepository;
            $this->deliveryFeeRepository = $deliveryFeeRepository;
            $this->mailTemplateRepository = $mailTemplateRepository;
            $this->mailHistoryRepository = $mailHistoryRepository;
            $this->prefRepository = $prefRepository;
            $this->paymentRepository = $paymentRepository;
            $this->paymentOptionRepository = $paymentOptionRepository;
            $this->saleTypeRepository = $saleTypeRepository;
            $this->csvTypeRepository = $csvTypeRepository;
            $this->csvRepository = $csvRepository;
            $this->shippingRepository = $shippingRepository;
            $this->productClassRepository = $productClassRepository;
            $this->taxRuleRepository = $taxRuleRepository;
            $this->taxRuleService = $taxRuleService;
            $this->orderRepository = $orderRepository;
            $this->orderItemRepository = $orderItemRepository;
            $this->orderStatusRepository = $orderStatusRepository;
            $this->orderNoProcessor = $orderNoProcessor;
            $this->twig = $twig;
            $this->mailer = $mailer;
            $this->mailService = $mailService;
            $this->utilService = $utilService;
            $this->logService = $logService;
            $this->acApiService = $acApiService;
            $this->remiseSaleTypeRepository = $remiseSaleTypeRepository;
            $this->remisePaymentRepository = $remisePaymentRepository;
            $this->remiseCsvTypeRepository = $remiseCsvTypeRepository;
            $this->remiseACTypeRepository = $remiseACTypeRepository;
            $this->remiseACResultRepository = $remiseACResultRepository;
            $this->remiseACImportRepository = $remiseACImportRepository;
            $this->remiseACMemberRepository = $remiseACMemberRepository;
            $this->orderResultRepository = $orderResultRepository;
            $this->orderResultCardRepository = $orderResultCardRepository;
            $this->remiseACImportStackRepository = $remiseACImportStackRepository;
            $this->remiseACImportStackResultRepository = $remiseACImportStackResultRepository;
            $this->eccubeConfig = $eccubeConfig;
            $this->projectRoot = $eccubeConfig->get('kernel.project_dir');
    }

    /**
     * ルミーズ販売種別の追加
     */
    public function insertRemiseSaleType()
    {
        // ルミーズ販売種別の既存レコード確認
        $RemiseSaleTypes = $this->remiseSaleTypeRepository->findBy([
            'sale_type' => trans('remise_payment4.ac.sale_type')
        ], [
            'id' => 'DESC'
        ]);

        // 存在する場合
        if ($RemiseSaleTypes) {
            $RemiseSaleTypeCount = 0;
            foreach ($RemiseSaleTypes as $RemiseSaleType) {

                // ルミーズ販売種別に紐づく販売種別マスタを取得
                $SaleType = $this->saleTypeRepository->findOneBy([
                    'id' => $RemiseSaleType->getId()
                ]);

                // 販売種別マスタが存在しない場合、ルミーズ販売種別を削除
                if (! $SaleType) {
                    $this->entityManager->remove($RemiseSaleType);
                    $this->entityManager->flush();
                } else {
                    $RemiseSaleTypeCount ++;
                }
            }
            // 1件以上販売種別が存在すればリターン
            if ($RemiseSaleTypeCount >= 1) {
                return;
            }
        }

        // 販売種別マスタのID最大値取得
        $SaleTypeIdMax = $this->saleTypeRepository->findOneBy([], [
            'id' => 'DESC'
        ]);
        // 販売種別マスタのソートNo最大値取得
        $SaleTypeSortNoMax = $this->saleTypeRepository->findOneBy([], [
            'sort_no' => 'DESC'
        ]);

        // 販売種別マスタ追加
        $SaleType = new SaleType();
        $SaleType->setId($SaleTypeIdMax->getId() + 1);
        $SaleType->setSortNo($SaleTypeSortNoMax->getSortNo() + 1);
        $SaleType->setName(trans('remise_payment4.ac.sale_type.name'));
        $this->entityManager->persist($SaleType);
        $this->entityManager->flush($SaleType);

        // ルミーズ販売種別追加
        $RemiseSaleType = new RemiseSaleType();
        $RemiseSaleType->setId($SaleType->getId());
        $RemiseSaleType->setName(trans('remise_payment4.ac.sale_type.name'));
        $RemiseSaleType->setSaleType(trans('remise_payment4.ac.sale_type'));
        $RemiseSaleType->setCreateDate(new \DateTime());
        $RemiseSaleType->setUpdateDate(new \DateTime());
        $this->entityManager->persist($RemiseSaleType);
        $this->entityManager->flush($RemiseSaleType);

        return;
    }

    /**
     * ルミーズ販売種別の追加（マスターデータ管理からの追加）
     */
    public function insertRemiseSaleTypeMasterAdd()
    {
        // 販売種別マスタのID最大値取得
        $SaleTypeIdMax = $this->saleTypeRepository->findOneBy([], [
            'id' => 'DESC'
        ]);
        // 販売種別マスタのソートNo最大値取得
        $SaleTypeSortNoMax = $this->saleTypeRepository->findOneBy([], [
            'sort_no' => 'DESC'
        ]);

        // 販売種別マスタ追加
        $SaleType = new SaleType();
        $SaleType->setId($SaleTypeIdMax->getId() + 1);
        $SaleType->setSortNo($SaleTypeSortNoMax->getSortNo() + 1);
        $SaleType->setName(trans('remise_payment4.ac.sale_type.name'));
        $this->entityManager->persist($SaleType);
        $this->entityManager->flush($SaleType);

        // ルミーズ販売種別追加
        $RemiseSaleType = new RemiseSaleType();
        $RemiseSaleType->setId($SaleType->getId());
        $RemiseSaleType->setName(trans('remise_payment4.ac.sale_type.name'));
        $RemiseSaleType->setSaleType(trans('remise_payment4.ac.sale_type'));
        $RemiseSaleType->setCreateDate(new \DateTime());
        $RemiseSaleType->setUpdateDate(new \DateTime());
        $this->entityManager->persist($RemiseSaleType);
        $this->entityManager->flush($RemiseSaleType);

        return;
    }

    /**
     * 配送先の追加
     */
    public function insertDelivery()
    {
        // ルミーズ販売種別の既存レコード取得(1件目の自動登録されたレコードのみを対象にする。)
        $RemiseSaleType = $this->remiseSaleTypeRepository->findOneBy([
            'sale_type' => trans('remise_payment4.ac.sale_type')
        ], [
            'id' => 'ASC'
        ]);
        // ルミーズ販売種別に紐づく販売種別マスタを取得
        $SaleType = $this->saleTypeRepository->findOneBy([
            'id' => $RemiseSaleType->getId()
        ]);

        // ルミーズ販売種別に紐づく配送先を取得
        $Deliverys = $this->deliveryRepository->findBy([
            'SaleType' => $RemiseSaleType->getId()
        ]);

        // 配送先が存在する場合、リターン
        if ($Deliverys) {
            return;
        }

        // 配送先のソートNo最大値取得
        $delivery = $this->deliveryRepository->findOneBy([], [
            'sort_no' => 'DESC'
        ]);
        $sortNo = 1;
        if ($delivery) {
            $sortNo = $delivery->getSortNo() + 1;
        }

        // 配送先作成
        $delivery = new Delivery();
        $delivery->setSortNo($sortNo)
            ->setVisible(true)
            ->setSaleType($SaleType)
            ->setName(trans('remise_payment4.ac.delivery.name'))
            ->setServiceName(trans('remise_payment4.ac.delivery.service_name'))
            ->setCreateDate(new \DateTime())
            ->setUpdateDate(new \DateTime());

        $this->entityManager->persist($delivery);
        $this->entityManager->flush();

        // 都道府県別送料
        $Prefs = $this->prefRepository->findAll();
        foreach ($Prefs as $Pref) {
            $deliveryFee = new DeliveryFee();
            $deliveryFee->setPref($Pref)->setDelivery($delivery);
            $deliveryFee->setFee(trans('remise_payment4.ac.delivery_fee.fee'));
            $delivery->addDeliveryFee($deliveryFee);
        }

        $this->entityManager->persist($deliveryFee);
        $this->entityManager->persist($delivery);
        $this->entityManager->flush();

        // 支払方法
        $remisePaymentCard = $this->remisePaymentRepository->findOneBy([
            'kind' => trans('remise_payment4.common.label.payment.kind.card')
        ]);
        $payment = $this->paymentRepository->findOneBy([
            'id' => $remisePaymentCard->getId()
        ]);
        $paymentOption = new PaymentOption();
        $paymentOption->setPaymentId($remisePaymentCard->getId())
            ->setPayment($payment)
            ->setDeliveryId($delivery->getId())
            ->setDelivery($delivery);

        $this->entityManager->persist($paymentOption);
        $this->entityManager->flush();

        return;
    }

    /**
     * ルミーズCSV種別の取得
     */
    public function getRemiseCsvType()
    {
        // ルミーズCSV種別の既存レコード確認
        $RemiseCsvType = $this->remiseCsvTypeRepository->findOneBy([
            'csv_type' => trans('remise_payment4.ac.csv_type')
        ], [
            'id' => 'ASC'
        ]);

        // 存在する場合
        if ($RemiseCsvType) {

            // ルミーズCSV種別に紐づくCSV種別マスタを取得
            $CsvType = $this->csvTypeRepository->findOneBy([
                'id' => $RemiseCsvType->getId()
            ]);

            // CSV種別マスタが存在しない場合、ルミーズCSV種別を削除
            if ($CsvType) {
                return $RemiseCsvType;
            }
        }
        return null;
    }

    /**
     * ルミーズCSV種別の取得
     */
    public function getRemiseCsvTypeAcResult()
    {
        // ルミーズCSV種別の既存レコード確認
        $RemiseCsvType = $this->remiseCsvTypeRepository->findOneBy([
            'csv_type' => trans('remise_payment4.ac.csv_type.ac_result')
        ], [
            'id' => 'ASC'
        ]);

        // 存在する場合
        if ($RemiseCsvType) {

            // ルミーズCSV種別に紐づくCSV種別マスタを取得
            $CsvType = $this->csvTypeRepository->findOneBy([
                'id' => $RemiseCsvType->getId()
            ]);

            // CSV種別マスタが存在しない場合、ルミーズCSV種別を削除
            if ($CsvType) {
                return $RemiseCsvType;
            }
        }
        return null;
    }

    /**
     * ルミーズ定期購買商品情報登録
     *
     * @param ProductClass $ProductClass
     *            商品規格情報
     * @param decimal $amount
     *            定期購買金額
     * @param int $acTypeId
     *            ルミーズ定期購買商品情報ID
     */
    public function saveACProduct($ProductClass, $amount, $acTypeId, $acPointFlg = true)
    {
        if ($acTypeId == "") {
            $acTypeId = null;
        }
        if ($amount == "") {
            $amount = null;
        }
        $ProductClass->setRemisePayment4AcAcTypeId($acTypeId);
        $ProductClass->setRemisePayment4AcAmount($amount);
        $ProductClass->setRemisePayment4AcPointFlg($acPointFlg);

        $this->entityManager->persist($ProductClass);
        $this->entityManager->flush();

        return;
    }

    /**
     * ルミーズ定期購買情報作成
     *
     * @param Order $Order
     *            商品情報
     */
    public function createRemiseACMember($Order, $remiseACMemberEntity = null)
    {
        $insertFlg = false;
        if (! $remiseACMemberEntity) {
            $remiseACMemberEntity = new RemiseACMember();
            $insertFlg = true;
        }
        $remiseACTypeEntity = new RemiseACType();
        $ProductEntity = new Product();
        $ProductClassEntity = new ProductClass();
        $quantity = 0;

        $OrderItems = $Order->getItems();
        if (! $OrderItems) {
            return null;
        }

        foreach ($OrderItems as $OrderItem) {
            // 商品明細を取る
            if ($OrderItem->isProduct()) {
                // 商品を取得
                $ProductEntity = $OrderItem->getProduct();
                if (! $ProductEntity) {
                    return null;
                }

                // 商品規格を取得
                $ProductClassEntity = $OrderItem->getProductClass();
                if (! $ProductClassEntity) {
                    return null;
                }

                // ルミーズ定期購買種別情報を取得
                $remiseACTypeEntity = $this->remiseACTypeRepository->findOneBy([
                    'id' => $ProductClassEntity->getRemisePayment4AcAcTypeId()
                ]);
                if (! $remiseACTypeEntity) {
                    return null;
                }

                // 数量を取得
                $quantity = $OrderItem->getQuantity();
                if (! $quantity || $quantity <= 0) {
                    return null;
                }

                // 定期購買商品は1つしか選べない仕様なので、1回ヒットしたら抜ける。
                break;
            }
        }

        // 初回課金日を算出
        $firstDate = $this->getFirstDate($remiseACTypeEntity);
        // 課金停止日を算出
        $stopDate = null;
        if (! $remiseACTypeEntity->isLimitless()) {
            $stopDate = $this->getStopDate($remiseACTypeEntity, $firstDate);
        }

        // 税込金額を取得
        $priceIncTax = $this->taxRuleService->getPriceIncTax($ProductClassEntity->getRemisePayment4AcAmount(), $ProductEntity, $ProductClassEntity);
        $priceIncTaxTotal = $priceIncTax * $quantity;

        // 送料を取得
        $deliveryFee = 0;
        $deliveryFeeIndividual = 0;
        $isDeliveryFee = false;
        if ($this->baseInfo->getDeliveryFreeQuantity()) {
            // 送料無料条件（数量）
            if ($this->baseInfo->getDeliveryFreeQuantity() <= $quantity) {
                $deliveryFee = 0;
                $isDeliveryFee = true;
            }
        }
        if (! $isDeliveryFee && $this->baseInfo->getDeliveryFreeAmount()) {
            // 送料無料条件（金額）
            if ($this->baseInfo->getDeliveryFreeAmount() <= $priceIncTaxTotal) {
                $deliveryFee = 0;
                $isDeliveryFee = true;
            }
        }
        if (! $isDeliveryFee) {
            // 商品ごとの送料設定が有効の場合
            if ($this->baseInfo->isOptionProductDeliveryFee()) {
                if ($ProductClassEntity->getDeliveryFee() != null) {
                    $deliveryFeeIndividual = $ProductClassEntity->getDeliveryFee();
                    // ECCUBEでは商品ごとに設定しても個別の送料+共通の送料が合計送料として請求する仕様となっている。
                    // $isDeliveryFee = true;
                }
            }
        }
        if (! $isDeliveryFee) {
            // 配送方法の送料を取得
            $Shipping = $this->shippingRepository->findOneBy([
                'Order' => $Order->getId()
            ]);
            if (! $Shipping) {
                return null;
            }
            $prefId = $Shipping->getPref()->getId();
            $DeliveryEntity = $Shipping->getDelivery();
            if (! $DeliveryEntity) {
                return null;
            }
            $DeliveryFeeEntity = $this->deliveryFeeRepository->findOneBy([
                'Delivery' => $DeliveryEntity->getId(),
                'Pref' => $prefId
            ]);
            if ($DeliveryFeeEntity) {
                $deliveryFee = $DeliveryFeeEntity->getFee();
                $isDeliveryFee = true;
            }
        }
        if (! $isDeliveryFee) {
            return null;
        }

        // 共通の送料+個別の総量
        $deliveryFee = $deliveryFee + $deliveryFeeIndividual;

        // 合計金額 (税込金額×数量)＋送料
        $totalPrice = $priceIncTaxTotal + $deliveryFee;

        if ($insertFlg) {
            $remiseACMemberEntity->setId(null);
            $remiseACMemberEntity->setTotal($totalPrice);
            $remiseACMemberEntity->setCount($remiseACTypeEntity->getCount());
            $remiseACMemberEntity->setLimitless($remiseACTypeEntity->isLimitless());
            $remiseACMemberEntity->setIntervalValue($remiseACTypeEntity->getIntervalValue());
            $remiseACMemberEntity->setIntervalMark($remiseACTypeEntity->getIntervalMark());
            $remiseACMemberEntity->setDayOfMonth($remiseACTypeEntity->getDayOfMonth());
            $remiseACMemberEntity->setAfterValue($remiseACTypeEntity->getAfterValue());
            $remiseACMemberEntity->setAfterMark($remiseACTypeEntity->getAfterMark());
            $remiseACMemberEntity->setSkip($remiseACTypeEntity->getSkip());
            $remiseACMemberEntity->setStop($remiseACTypeEntity->getStop());
            $remiseACMemberEntity->setUsageValue($remiseACTypeEntity->getUsageValue());
            $remiseACMemberEntity->setUsageMark($remiseACTypeEntity->getUsageMark());
            $remiseACMemberEntity->setCardparts(null);
            $remiseACMemberEntity->setExpire(null);
            $remiseACMemberEntity->setNextDate($firstDate);
            $remiseACMemberEntity->setStatus(1);
            $remiseACMemberEntity->setSkipped(false);
            $remiseACMemberEntity->setSkippedDate(null);
            $remiseACMemberEntity->setSkippedNextDate(null);
            $remiseACMemberEntity->setStartDate($firstDate);
            $remiseACMemberEntity->setStopDate($stopDate);
            $remiseACMemberEntity->setCreateDate(new \DateTime());
            $remiseACMemberEntity->setUpdateDate(new \DateTime());
        } else {
            $remiseACMemberEntity->setTotal($totalPrice);
            $remiseACMemberEntity->setCount($remiseACTypeEntity->getCount());
            $remiseACMemberEntity->setLimitless($remiseACTypeEntity->isLimitless());
            $remiseACMemberEntity->setIntervalValue($remiseACTypeEntity->getIntervalValue());
            $remiseACMemberEntity->setIntervalMark($remiseACTypeEntity->getIntervalMark());
            $remiseACMemberEntity->setDayOfMonth($remiseACTypeEntity->getDayOfMonth());
            $remiseACMemberEntity->setAfterValue($remiseACTypeEntity->getAfterValue());
            $remiseACMemberEntity->setAfterMark($remiseACTypeEntity->getAfterMark());
            $remiseACMemberEntity->setSkip($remiseACTypeEntity->getSkip());
            $remiseACMemberEntity->setStop($remiseACTypeEntity->getStop());
            $remiseACMemberEntity->setUsageValue($remiseACTypeEntity->getUsageValue());
            $remiseACMemberEntity->setUsageMark($remiseACTypeEntity->getUsageMark());
            $remiseACMemberEntity->setNextDate($firstDate);
            $remiseACMemberEntity->setStatus(1);
            $remiseACMemberEntity->setSkipped(false);
            $remiseACMemberEntity->setSkippedDate(null);
            $remiseACMemberEntity->setSkippedNextDate(null);
            $remiseACMemberEntity->setStartDate($firstDate);
            $remiseACMemberEntity->setStopDate($stopDate);
            $remiseACMemberEntity->setUpdateDate(new \DateTime());
        }

        return $remiseACMemberEntity;
    }

    /**
     * ルミーズ定期購買メンバー受注情報取得処理
     *
     * @param RemiseAcMember $RemiseAcMember
     */
    public function setRemiseAcMemberRelatedOrder(RemiseACMember $RemiseAcMember = null)
    {
        if (! $RemiseAcMember) {
            return null;
        }

        $orderResultCards = $this->orderResultCardRepository->getOrderResultCardsMemberIdJoinACResult($RemiseAcMember->getId());

        foreach ($orderResultCards as $orderResultCard) {
            $orderResult = $this->orderResultRepository->findOneBy([
                'id' => $orderResultCard->getId()
            ]);
            if (! $orderResult) {
                continue;
            }
            $order = $this->orderRepository->findOneBy([
                'id' => $orderResult->getId()
            ]);
            if (! $order) {
                continue;
            }
            $remiseACResult = $this->remiseACResultRepository->findOneBy([
                'id' => $orderResult->getId()
            ]);
            $orderResult->setOrder($order);
            $orderResultCard->setOrderResult($orderResult);
            $orderResultCard->setRemiseACResult($remiseACResult);
            $RemiseAcMember->addOrderResultCard($orderResultCard);
        }
    }

    /**
     * ルミーズ定期購買結果情報作成
     *
     * @param order_id
     * @param result
     * @param charge_date
     *
     * @return $RemiseACResult
     */
    public function createRemiseACResult($id, $result, $chargeDate = null)
    {
        if($chargeDate == null){
            $chargeDate = new \DateTime();
        }

        $RemiseACResult = $this->remiseACResultRepository->findOneBy([
            'id' => $id
        ]);
        if(!$RemiseACResult)
        {
            $RemiseACResult = new RemiseACResult();
            $RemiseACResult->setId($id);
            $RemiseACResult->setChargeDate($chargeDate);
            $RemiseACResult->setCreateDate(new \DateTime());
        }
        $RemiseACResult->setResult($result);
        $RemiseACResult->setUpdateDate(new \DateTime());

        return $RemiseACResult;
    }

    /**
     * ショッピングカート定期購買商品重複判定
     *
     * @param Cart $Cart
     *            カート情報
     * @return cartKeys
     */
    public function checkACCartDuplicate($Carts)
    {
        $cntDuplicate = 0;
        $cartKeys = Array();

        if (! $Carts) {
            return null;
        }

        foreach ($Carts as $Cart) {
            foreach ($Cart->getCartItems() as $CartItem) {
                // 商品規格を取得
                $ProductClassEntity = $CartItem->getProductClass();
                if (! $ProductClassEntity) {
                    continue;
                }

                $SaleTypeEntity = $ProductClassEntity->getSaleType();
                if (! $SaleTypeEntity) {
                    continue;
                }

                // ルミーズ販売種別情報を取得
                $remiseSaleTypeEntity = $this->remiseSaleTypeRepository->findOneBy([
                    'id' => $SaleTypeEntity->getId(),
                    'sale_type' => trans('remise_payment4.ac.sale_type')
                ]);

                if ($remiseSaleTypeEntity) {
                    $cntDuplicate ++;
                    $cartKeys[$Cart->getCartKey()] = $Cart->getCartKey();
                } else {
                    continue;
                }
            }
        }

        if ($cntDuplicate >= 2) {
            return $cartKeys;
        } else {
            return null;
        }
    }

    /**
     * 受注情報定期購買商品重複判定
     *
     * @param Order $Order
     *            カート情報
     * @return cartKeys
     */
    public function checkACOrderDuplicate($Order)
    {
        $cntDuplicate = 0;

        if (! $Order) {
            return true;
        }

        $OrderItems = $Order->getItems();
        if (! $OrderItems) {
            return true;
        }

        foreach ($OrderItems as $OrderItem) {
            // 商品明細を取る
            if ($OrderItem->isProduct()) {
                // 商品を取得
                $ProductEntity = $OrderItem->getProduct();
                if (! $ProductEntity) {
                    continue;
                }

                // 商品規格を取得
                $ProductClassEntity = $OrderItem->getProductClass();
                if (! $ProductClassEntity) {
                    continue;
                }

                $SaleTypeEntity = $ProductClassEntity->getSaleType();
                if (! $SaleTypeEntity) {
                    continue;
                }

                // ルミーズ販売種別情報を取得
                $remiseSaleTypeEntity = $this->remiseSaleTypeRepository->findOneBy([
                    'id' => $SaleTypeEntity->getId(),
                    'sale_type' => trans('remise_payment4.ac.sale_type')
                ]);

                if ($remiseSaleTypeEntity) {
                    $cntDuplicate ++;
                } else {
                    continue;
                }
            }
        }

        if ($cntDuplicate >= 2) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 受注情報定期購買商品判定
     *
     * @param Order $Order
     *            商品情報
     * @return bool
     */
    public function checkACOrder($Order)
    {
        $ProductEntity = new Product();
        $ProductClassEntity = new ProductClass();

        $OrderItems = $Order->getItems();
        if (! $OrderItems) {
            return false;
        }

        foreach ($OrderItems as $OrderItem) {
            // 商品明細を取る
            if ($OrderItem->isProduct()) {
                // 商品を取得
                $ProductEntity = $OrderItem->getProduct();
                if (! $ProductEntity) {
                    return false;
                }

                // 商品規格を取得
                $ProductClassEntity = $OrderItem->getProductClass();
                if (! $ProductClassEntity) {
                    return false;
                }

                $SaleTypeEntity = $ProductClassEntity->getSaleType();
                if (! $SaleTypeEntity) {
                    return false;
                }

                // ルミーズ販売種別情報を取得
                $remiseSaleTypeEntity = $this->remiseSaleTypeRepository->findOneBy([
                    'id' => $SaleTypeEntity->getId(),
                    'sale_type' => trans('remise_payment4.ac.sale_type')
                ]);

                if ($remiseSaleTypeEntity) {
                    return true;
                } else {
                    return false;
                }
            }
        }
    }

    /**
     * 初回課金日を算出する
     *
     * @param RemiseACType $RemiseACType
     *            定期購買設定情報
     * @param \DateTime $date
     *            起算日
     *
     * @return \DateTime $firstDate 初回課金日
     */
    public function getFirstDate($RemiseACType, $date = null)
    {
        $firstDate = null;

        // 起算日
        $dt = new \DateTime();
        if ($date != null)
            $dt = clone $date;

        $nowDay = $dt->format('d');

        // ｘ日ごと課金
        if ($RemiseACType->getIntervalMark() == "D") {
            // ｘ日後から開始
            if ($RemiseACType->getAfterMark() == "D") {
                $firstDate = $dt->modify('+' . $RemiseACType->getAfterValue() . 'day');
            } // ｘヶ月後から開始
            else {
                $dt = $dt->modify('first day of this months'); // 起算日の月初めから
                $dt = $dt->modify('+' . $RemiseACType->getAfterValue() . 'month'); // ｘヶ月後を算出
                $lastDay = $dt->modify('last day of this months')->format('d'); // ｘヶ月後の末日を取得
                                                                                // 起算日の日付がｘヶ月後の末日に収まる場合は、起算日の日付で生成
                if ($nowDay <= $lastDay) {
                    $firstDate = new \DateTime($dt->format('Y-m') . '-' . str_pad($nowDay, 2, 0, STR_PAD_LEFT));
                } // 起算日の日付がｘヶ月後の末日を超える場合は、末日で生成
                else {
                    $firstDate = new \DateTime($dt->format('Y-m') . '-' . str_pad($lastDay, 2, 0, STR_PAD_LEFT));
                }
            }
        } // ｘヶ月ごと課金
        else {
            $dt = $dt->modify('first day of this months'); // 起算日の月初めから
            $dt = $dt->modify('+' . $RemiseACType->getAfterValue() . 'month'); // ｘヶ月後を算出
            $lastDay = $dt->modify('last day of this months')->format('d'); // ｘヶ月後の末日を取得
                                                                            // 起算日の日付がｘヶ月後の末日に収まる場合は、課金日の日付で生成
            $dayOfMonth = $RemiseACType->getDayOfMonth();
            if ($dayOfMonth == 99) {
                if ($date != null) {
                    $dayOfMonth = $date->format('d');
                } else {
                    $dayOfMonth = $nowDay;
                }
            }
            if ($dayOfMonth <= $lastDay) {
                $firstDate = new \DateTime($dt->format('Y-m') . '-' . str_pad($dayOfMonth, 2, 0, STR_PAD_LEFT));
            } // 起算日の日付がｘヶ月後の末日を超える場合は、末日で生成
            else {
                $firstDate = new \DateTime($dt->format('Y-m') . '-' . str_pad($lastDay, 2, 0, STR_PAD_LEFT));
            }
        }

        return $firstDate;
    }

    /**
     * 次回課金日を算出する
     *
     * @param RemiseACType $RemiseACType
     *            定期購買設定情報
     * @param \DateTime $date
     *            起算日
     *
     * @return \DateTime $nextDate 次回課金日
     */
    public function getNextDate($RemiseACType, $date = null)
    {
        $nextDate = null;

        // 起算日
        $dt = new \DateTime();
        if ($date != null)
            $dt = clone $date;

        $nowDay = $dt->format('d');

        // ｘ日ごと課金
        if ($RemiseACType->getIntervalMark() == "D") {
            $nextDate = $dt->modify('+' . $RemiseACType->getIntervalValue() . 'day');
        } // ｘヶ月ごと課金
        else {
            $dt = $dt->modify('first day of this months'); // 起算日の月初めから
            $dt = $dt->modify('+' . $RemiseACType->getIntervalValue() . 'month'); // ｘヶ月後を算出
            $lastDay = $dt->modify('last day of this months')->format('d'); // ｘヶ月後の末日を取得
                                                                            // 起算日の日付がｘヶ月後の末日に収まる場合は、課金日の日付で生成
            $dayOfMonth = $RemiseACType->getDayOfMonth();
            if ($dayOfMonth == 99) {
                if ($date != null) {
                    $dayOfMonth = $date->format('d');
                } else {
                    $dayOfMonth = $nowDay;
                }
            }
            if ($dayOfMonth <= $lastDay) {
                $nextDate = new \DateTime($dt->format('Y-m') . '-' . str_pad($dayOfMonth, 2, 0, STR_PAD_LEFT));
            } // 起算日の日付がｘヶ月後の末日を超える場合は、末日で生成
            else {
                $nextDate = new \DateTime($dt->format('Y-m') . '-' . str_pad($lastDay, 2, 0, STR_PAD_LEFT));
            }
        }

        return $nextDate;
    }

    /**
     * 課金停止予定日を算出する
     *
     * @param RemiseACType $RemiseACType
     *            定期購買設定情報
     * @param \DateTime $nextDate
     *            次回課金日
     *
     * @return \DateTime $stopDate 課金停止予定日
     */
    public function getStopDate($RemiseACType, $nextDate)
    {
        // 課金日
        $dayOfMonth = $RemiseACType->getDayOfMonth();
        if ($dayOfMonth == 99) {
            $dayOfMonth = $nextDate->format('d');
        }
        // 月末課金判定
        $isLastDayCycle = false;
        if ($nextDate->format('m') == 2 && ($nextDate->format('d') == 28 || $nextDate->format('d') == 29)) {
            $isLastDayCycle = true;
        }

        $dt = clone $nextDate;
        for ($cnt = 0; $cnt < $RemiseACType->getCount() - 1; $cnt ++) {
            // ｘ日ごと課金
            if ($RemiseACType->getIntervalMark() == "D") {
                $dt = $dt->modify('+' . $RemiseACType->getIntervalValue() . 'day');
            } // ｘヶ月ごと課金
            else {
                $dt = $dt->modify('first day of this months'); // 基準日の月初めから
                $dt = $dt->modify('+' . $RemiseACType->getIntervalValue() . 'month'); // 経過日数後を算出
                $lastDay = $dt->modify('last day of this months')->format('d'); // 経過日数後の末日を取得
                                                                                // 月末課金以外で基準日の日付が経過日数後の末日に収まる場合は、基準日の日付で生成
                if ($isLastDayCycle == false && $dayOfMonth <= $lastDay) {
                    $dt = new \DateTime($dt->format('Y-m') . '-' . str_pad($dayOfMonth, 2, 0, STR_PAD_LEFT));
                } // 基準日の日付が経過日数後の末日を超える場合は、末日で生成
                else {
                    $dt = new \DateTime($dt->format('Y-m') . '-' . str_pad($lastDay, 2, 0, STR_PAD_LEFT));
                }
                // 月末課金判定
                if ($dt->format('m') == 2 && ($dt->format('d') == 28 || $dt->format('d') == 29)) {
                    $isLastDayCycle = true;
                }
            }
        }
        $stopDate = $dt;
        return $stopDate;
    }

    /**
     * 残り課金回数を算出する
     *
     * @param  RemiseACMember   $remiseACMembere   定期購買情報
     * @param  \DateTime        $acNextDate        基準となる次回課金日
     *
     * @return  integer  count  残り課金回数
     */
    public function getRemainingCount(RemiseACMember $remiseACMembere, $acNextDate)
    {
        if ($remiseACMembere->isLimitless()) return 0;

        $dt = clone $remiseACMembere->getNextDate();
        $count = $remiseACMembere->getCount();

        // 課金日
        $dayOfMonth = $remiseACMembere->getDayOfMonth();
        if ($dayOfMonth == 99) {
            $dayOfMonth = $dt->format('d');
        }
        // 月末課金判定
        $isLastDayCycle = false;
        if ($dt->format('m') == 2 && ($dt->format('d') == 28 || $dt->format('d') == 29)) {
            $isLastDayCycle = true;
        }

        // 基準となる次回課金日と算出する次回課金日が一致した（または超えた）場合、
        // 対象の課金回数を返却
        while ($acNextDate->format('Ymd') > $dt->format('Ymd'))
        {
            // ｘ日ごと課金
            if ($remiseACMembere->getIntervalMark() == "D") {
                $dt = $dt->modify('+' . $remiseACMembere->getIntervalValue() . 'day');    // 経過日数後を算出
            }
            // ｘヶ月ごと課金
            else {
                $dt = $dt->modify('first day of this months');  // 基準日の月初めから
                $dt = $dt->modify('+' . $remiseACMembere->getIntervalValue() . 'month');  // 経過日数後を算出
                $lastDay = $dt->modify('last day of this months')->format('d'); // 経過日数後の末日を取得
                // 月末課金以外で基準日の日付が経過日数後の末日に収まる場合は、基準日の日付で生成
                if ($isLastDayCycle == false && $dayOfMonth <= $lastDay) {
                    $dt = new \DateTime($dt->format('Y-m') . '-' . str_pad($dayOfMonth, 2, 0, STR_PAD_LEFT));
                }
                // 基準日の日付が経過日数後の末日を超える場合は、末日で生成
                else {
                    $dt = new \DateTime($dt->format('Y-m') . '-' . str_pad($lastDay, 2, 0, STR_PAD_LEFT));
                }
                // 月末課金判定
                if ($dt->format('m') == 2 && ($dt->format('d') == 28 || $dt->format('d') == 29)) {
                    $isLastDayCycle = true;
                }
            }
            $count--;
        }

        return $count;
    }

    /**
     * 課金回数を算出する
     *
     * @param  RemiseACMember $remiseACMembere   定期購買情報
     *
     * @return  integer  count  課金回数
     */
    public function getCountFromStopDate(RemiseACMember $remiseACMembere)
    {
        if ($remiseACMembere->isLimitless()) return 0;

        $stopDate = $remiseACMembere->getStopDate();
        $dt = clone $remiseACMembere->getNextDate();

        if (strcmp($stopDate->format('Ymd'), $dt->format('Ymd')) <= 0)
        {
            return 0;
        }

        $count = 1;

        // 課金日
        $dayOfMonth = $remiseACMembere->getDayOfMonth();
        if ($dayOfMonth == 99) {
            $dayOfMonth = $dt->format('d');
        }
        // 月末課金判定
        $isLastDayCycle = false;
        if ($dt->format('m') == 2 && ($dt->format('d') == 28 || $dt->format('d') == 29)) {
            $isLastDayCycle = true;
        }

        // 課金終了予定日と次回課金日が一致した（または超えた）場合、
        // 対象の課金回数を返却
        while (strcmp($stopDate->format('Ymd'), $dt->format('Ymd')) > 0)
        {
            // ｘ日ごと課金
            if ($remiseACMembere->getIntervalMark() == "D") {
                $dt = $dt->modify('+' . $remiseACMembere->getIntervalValue() . 'day');    // 経過日数後を算出
            }
            // ｘヶ月ごと課金
            else {
                $dt = $dt->modify('first day of this months');  // 基準日の月初めから
                $dt = $dt->modify('+' . $remiseACMembere->getIntervalValue() . 'month');  // 経過日数後を算出
                $lastDay = $dt->modify('last day of this months')->format('d'); // 経過日数後の末日を取得
                // 月末課金以外で基準日の日付が経過日数後の末日に収まる場合は、基準日の日付で生成
                if ($isLastDayCycle == false && $dayOfMonth <= $lastDay) {
                    $dt = new \DateTime($dt->format('Y-m') . '-' . str_pad($dayOfMonth, 2, 0, STR_PAD_LEFT));
                }
                // 基準日の日付が経過日数後の末日を超える場合は、末日で生成
                else {
                    $dt = new \DateTime($dt->format('Y-m') . '-' . str_pad($lastDay, 2, 0, STR_PAD_LEFT));
                }
                // 月末課金判定
                if ($dt->format('m') == 2 && ($dt->format('d') == 28 || $dt->format('d') == 29)) {
                    $isLastDayCycle = true;
                }
            }
            $count++;
        }

        return $count;
    }

    /**
     * スキップ可否を取得する
     *
     * @param  RemiseACMember $remiseACMembere   定期購買情報
     *
     * @return  integer  0:スキップ不可、1:スキップ可、2:スキップ済み
     */
    public function getSkippingFlg(RemiseACMember $remiseACMember)
    {
        // スキップ不可
        if ($remiseACMember->getSkip() != 1) return 0;
        // スキップ未実行
        if ($remiseACMember->isSkipped() == 0) return 1;

        $nowDateStr = (new \DateTime())->format('Ymd');
        $skippedNextDateStr = $remiseACMember->getSkippedNextDate()->format('Ymd');

        // スキップ次回課金日経過
        if (strcmp($skippedNextDateStr, $nowDateStr) <= 0) return 1;
        // スキップ次回課金日未経過
        return 2;
    }

    /**
     * スキップ可不可の表示値の取得
     *
     * @param  RemiseACMember $remiseACMembere   定期購買情報
     *
     * @return  string  スキップ可不可の表示値
     */
    public function getDispAcSkipFlg(RemiseACMember $remiseACMember)
    {
        if (is_null($remiseACMember)) return "";

        if ($remiseACMember->getSkip() == trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.skip.true.key')) {
            // スキップを許可
            return trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.skip.true.value');
        } else {
            // スキップを不許可
            return trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.skip.false.value');
        }
    }

    /**
     * 解約可否を取得する
     *
     * @param  RemiseACMember $remiseACMembere   定期購買情報
     *
     * @return  integer  0:解約不可、1:解約可、2:最低利用期間中で解約不可
     */
    public function getRefusalFlg(RemiseACMember $remiseACMembere)
    {
        // 解約済み
        if (!$remiseACMembere->isStatus()) return 0;
        // 解約不可
        if ($remiseACMembere->getStop() == 1) return 0;
        // いつでも解約可
        if ($remiseACMembere->getStop() == 0) return 1;

        // 0日or0か月の場合は、解約可
        if ($remiseACMembere->getUsageValue() == 0) return 1;

        // 最低利用期間の判定
        $now = new \DateTime();
        // 最低利用期間を算出
        $dt = clone $remiseACMembere->getStartDate();
        $day = $dt->format('d');
        // ｘ日間
        if ($remiseACMembere->getUsageMark() == "D") {
            $dt = $dt->modify('+' . $remiseACMembere->getUsageValue() . 'day');
            // 最低利用期間経過時
            if ($dt < $now) return 1;
        }
        // ｘヶ月間
        else {
            $dt = $dt->modify('first day of this months');  // 開始日の月初めから
            $dt = $dt->modify('+' . $remiseACMembere->getUsageValue() . 'month');  // 経過日数後を算出
            if ($dt->format('Y') > 9999) return 0;

            $lastDay = $dt->modify('last day of this months')->format('d'); // 経過日数後の末日を取得
            // 開始日の日付が経過日数後の末日に収まる場合は、開始日の日付で生成
            if ($day <= $lastDay) {
                $dt = new \DateTime($dt->format('Y-m') . '-' . str_pad($day, 2, 0, STR_PAD_LEFT));
            }
            // 開始日の日付が経過日数後の末日を超える場合は、末日で生成
            else {
                $dt = new \DateTime($dt->format('Y-m') . '-' . str_pad($lastDay, 2, 0, STR_PAD_LEFT));
            }
            // 最低利用期間経過時
            if ($dt < $now) return 1;
        }
        return 2;
    }

    /**
     * 解約規約の表示値の取得
     *
     * @param  RemiseACMember $remiseACMember   定期購買情報
     *
     * @return  string  解約規約の表示値
     */
    public static function getDispAcStopFlg(RemiseACMember $remiseACMember)
    {
        if (is_null($remiseACMember)) return "";
        switch ($remiseACMember->getStop()) {
            case trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.stop.true.key'):
                // いつでも解約可
                return trans('remise_payment4.ac.front.shopping.acinfo.stop.true.value');
            case trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.stop.false.key'):
                // 解約不可
                return trans('remise_payment4.ac.front.shopping.acinfo.stop.false.value');
            case trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.stop.usage.key'):
                // 最低利用期間中は解約不可
                return trans('remise_payment4.ac.front.shopping.acinfo.stop.usage.value');
        }
        return "";
    }

    /**
     * 定期購買メンバー情報受注ID指定一意取得
     *
     * @param  string   $memberId     受注ID
     *
     * @return RemiseACMember $remiseACMember|null
     */
    public function getRemiseACMemberByOrderId($orderId)
    {
        $orderResultCard = $this->orderResultCardRepository->findOneBy(['id'=>$orderId]);
        if($orderResultCard){
            $remiseACMember = $this->remiseACMemberRepository->findOneBy(['id'=>$orderResultCard->getMemberId()]);
            return $remiseACMember;
        }else{
            return null;
        }
    }


    /**
     * 商品規格情報取得（受注情報指定）
     *
     * @param Order $Order
     * @return $ProductClass
     */
    public function getProductClassByOrder(Order $Order)
    {
        if (! $Order) {
            return;
        }

        $OrderItems = $Order->getItems();
        if (! $OrderItems) {
            return;
        }

        foreach ($OrderItems as $OrderItem) {
            // 商品明細を取る
            if ($OrderItem->isProduct()) {
                // 商品を取得
                $Product = $OrderItem->getProduct();
                if (! $Product) {
                    continue;
                }

                // 商品規格を取得
                $ProductClass = $OrderItem->getProductClass();
                if (! $ProductClass) {
                    continue;
                }else{
                    return $ProductClass;
                }
            }
        }

        return;
    }

    /**
     * 商品規格情報取得（ルミーズ定期購買種別指定）
     *
     */
    public function getProductClassListByRemisePayment4AcActypeId($remisePayment4AcActypeId)
    {
        if (! $remisePayment4AcActypeId) {
            return;
        }

        $ProductClasses = $this->productClassRepository->findBy(['remise_payment4_ac_actype_id' => $remisePayment4AcActypeId]);

        return $ProductClasses;
    }

    /**
     * ルミーズ定期購買種別マスタ取得（商品規格情報指定）
     *
     * @param ProductClass $ProductClass
     * @return $remiseACType
     */
    public function getRemiseAcTypeByProductClass(ProductClass $ProductClass)
    {
        if(!$ProductClass){
            return;
        }

        // ルミーズ販売種別情報を取得
        $remiseACType = $this->remiseACTypeRepository->findOneBy([
            'id' => $ProductClass->getRemisePayment4AcAcTypeId()
        ]);

        if ($remiseACType) {
            return $remiseACType;
        }

        return;
    }

    /**
     * 定期購買取込処理
     *
     * @param  string   $memberId     メンバーID
     * @param  \DateTime $chargeDate  取込対象日
     * @param  string  $startRow  取得開始行番号
     * @param  string  $count  取得件数
     * @param  RemiseACApiResult $returnRemiseACApiResult  実行結果格納用Entity
     *
     * @return Array $remiseACImportInfoList
     */
    public function acImportOrder($memberId, $chargeDate, $startRow, $count, RemiseACApiResult $returnRemiseACApiResult)
    {
        try{
            // タイムアウト無効
            set_time_limit(0);

            // 返却値初期化
            $remiseACImportInfoList = array();

            // 定期購買参照更新URL有効フラグ
            $useKeizokuResultExtend = $this->acApiService->useKeizokuEditExtend();

            // 定期購買結果取込用URLを呼び出す
            $remiseACApiKeizokuResultExtendResult = $this->acApiService->requestKeizokuResultExtend($memberId, $chargeDate, $startRow, $count);

            // 定期購買結果取込のエラー判定
            if(!$remiseACApiKeizokuResultExtendResult->isResult())
            {
                $returnRemiseACApiResult->setErrorLevel($remiseACApiKeizokuResultExtendResult->getErrorLevel());
                $returnRemiseACApiResult->setErrorMessage($remiseACApiKeizokuResultExtendResult->getErrorMessage());
                $returnRemiseACApiResult->setResult($remiseACApiKeizokuResultExtendResult->isResult());
                $returnRemiseACApiResult->setResultData($remiseACApiKeizokuResultExtendResult->getResultData());
                return $remiseACImportInfoList;
            }

            // 定期購買結果の受注情報への反映
            $resultDataList = $remiseACApiKeizokuResultExtendResult->getResultData();
            if(isset($resultDataList['typRetKeizokuResult']['X_R_CODE'])){
                $resultDataList['typRetKeizokuResult'] = array($resultDataList['typRetKeizokuResult']);
            }
            foreach($resultDataList['typRetKeizokuResult'] as $resultData){
                if ($resultData['X_AC_MEMBERID'] == "") continue;

                $remiseACImportInfo = $this->acImportCreateRemiseAcImportInfo($resultData);

                // 取込対象のメンバーIDをログ表示（どのメンバーの取込で失敗したかわかるように）
                $this->logService->logInfo('AcImportOrder -- import member_id',[$remiseACImportInfo->getMemberId()]);

                // エラーコード判定
                if(strcmp($remiseACImportInfo->getRCode(), "0:0000") != 0 ){
                    $remiseACImportInfo->setNote(trans('remise_payment4.ac.admin_import_result.text.note.member_notimport',['%rcode%' => $remiseACImportInfo->getRCode()]));
                    $remiseACImportInfoList[] = $remiseACImportInfo;
                    continue;
                }

                // 定期購買メンバー情報を取得
                $remiseACMember = $this->remiseACMemberRepository->findOneBy(['id' => $remiseACImportInfo->getMemberId()]);

                // 存在しない場合
                if(!$remiseACMember){
                    $remiseACImportInfo->setNote(trans('remise_payment4.ac.admin_import_result.text.note.member_notfound'));
                    $remiseACImportInfoList[] = $remiseACImportInfo;
                    continue;
                }

                // 定期購買メンバー情報に紐づく受注情報をセット
                $this->setRemiseAcMemberRelatedOrder($remiseACMember);

                // ルミーズ受注結果情報取得(既に取り込み済みのデータを判定する)
                $orderResultCard = $this->orderResultCardRepository->findOneBy([
                    'member_id' => $remiseACImportInfo->getMemberId(),
                    'tranid' => $remiseACImportInfo->getTranid()
                ], [
                    'create_date' => 'DESC'
                ]);

                // 存在した場合
                if ($orderResultCard)
                {
                    $remiseACImportInfo->setStatus("1");   // 取込済
                    $remiseACImportInfo->setNote(trans('remise_payment4.ac.admin_import_result.text.note.captured'));
                    $remiseACImportInfo->setOrderId($orderResultCard->getId());
                    $remiseACImportInfoList[] = $remiseACImportInfo;
                    continue;
                }

                // ルミーズ受注結果情報取得(初回取込のデータが存在するかを確認する)
                $orderResultCards = $remiseACMember->getOrderResultCards();

                // 存在しない場合
                if (!$orderResultCards)
                {
                    $remiseACImportInfo->setNote(trans('remise_payment4.ac.admin_import_result.text.note.member_notfound'));
                    $remiseACImportInfoList[] = $remiseACImportInfo;
                    continue;
                }

                // EC-CUBEの受注情報を作成
                $Order = $this->acImportCreateOrder($chargeDate, $remiseACImportInfo, $remiseACMember);

                // ルミーズの受注情報を作成
                $this->acImportCreateRemiseOrder($chargeDate, $remiseACImportInfo, $Order, $remiseACMember);

                // 定期購買メンバー情報の更新
                $errMsg = $this->acImportUpdateRemiseAcMemberUseAPI($remiseACMember, $resultData);
                if(!empty($errMsg)){
                    $remiseACImportInfo->setNote(trans("remise_payment4.ac.admin_import_result.text.note.member_notupdate",['%errMsg%' => $errMsg]));
                    $remiseACImportInfo->setStatus("1");   // 取込済
                    $remiseACImportInfo->setOrderId($Order->getId());
                    $remiseACImportInfo->setOrderNo($Order->getOrderNo());
                    $remiseACImportInfoList[] = $remiseACImportInfo;
                    continue;
                }

                // 差額がある場合、メッセージを設定
                if($Order->getTotal() != $Order->getPaymentTotal()){
                    $remiseACImportInfo->setNote(trans('remise_payment4.ac.admin_import_result.text.note.member_difference'));
                }

                $remiseACImportInfo->setStatus("1");   // 取込済
                $remiseACImportInfo->setOrderId($Order->getId());
                $remiseACImportInfo->setOrderNo($Order->getOrderNo());
                $remiseACImportInfoList[] = $remiseACImportInfo;
            }

            $returnRemiseACApiResult->setResult(true);
            return $remiseACImportInfoList;

        } catch (\Exception $e) {
            // ログ出力
            $this->logService->logError('Ac Import Order', Array(
                trans('admin.common.system_error'),
                'ErrCode:' . $e->getCode(),
                'ErrMessage:' . $e->getMessage(),
                'ErrTrace:' . $e->getTraceAsString()
                ));
            $returnRemiseACApiResult->setResult(false);
            $returnRemiseACApiResult->setErrorLevel(2);
            $returnRemiseACApiResult->setErrorMessage(trans('remise_payment4.ac.admin_import_result.text.failure'));
            $this->entityManager->rollback();
            return $remiseACImportInfoList;
        }
    }

    /**
     * 取込結果詳細情報生成
     *
     * @param  array   $resultData  取り込み結果１レコード分
     *
     * @return  RemiseACImportInfo   $remiseACImportInfo   取込結果情報
     */
    private function acImportCreateRemiseAcImportInfo($resultData)
    {
        $remiseACImportInfo = new RemiseACImportInfo();
        $remiseACImportInfo->setRCode       ($resultData['X_R_CODE']        );
        $remiseACImportInfo->setMemberId    ($resultData['X_AC_MEMBERID']   );
        $remiseACImportInfo->setSKaiinNo    ($resultData['X_AC_S_KAIIN_NO'] );
        $remiseACImportInfo->setAcName      ($resultData['X_AC_NAME']       );
        $remiseACImportInfo->setAcKana      ($resultData['X_AC_KANA']       );
        $remiseACImportInfo->setAcTel       ($resultData['X_AC_TEL']        );
        $remiseACImportInfo->setAcMail      ($resultData['X_AC_MAIL']       );
        $remiseACImportInfo->setAcAmount    ($resultData['X_AC_AMOUNT']     );
        $remiseACImportInfo->setAcResult    ($resultData['X_AC_RESULT']     );
        $remiseACImportInfo->setTranid      ($resultData['X_TRANID']        );
        $remiseACImportInfo->setErrcode     ($resultData['X_ERRCODE']       );
        $remiseACImportInfo->setErrinfo     ($resultData['X_ERRINFO']       );
        $remiseACImportInfo->setAcNextDate  ($resultData['X_AC_NEXT_DATE']  );
        $remiseACImportInfo->setCard        ($resultData['X_CARD']          );
        $remiseACImportInfo->setExpire      (substr($resultData['X_EXPIRE'], 2, 2) . substr($resultData['X_EXPIRE'], 0, 2));
        $remiseACImportInfo->setNote        (""                         );
        $remiseACImportInfo->setStatus      (0                          );
        $remiseACImportInfo->setOrderId     (0                          );
        $remiseACImportInfo->setOrderNo     (0                          );
        return $remiseACImportInfo;
    }

    /**
     * 受注情報作成
     *
     * @param  RemiseACMember $remiseACMember              複写元受注情報
     *
     * @return Order  $Order  受注情報
     */
    protected function acImportCreateOrder($chargeDate, RemiseACImportInfo $remiseACImportInfo, RemiseACMember $remiseACMember)
    {
        $newOrder = new Order();

        /*** 受注情報作成に必要となる関連情報の取得 ***/
        // ルミーズ受注情報（カード決済）リスト
        $orderResultCards = $remiseACMember->getOrderResultCards();
        // ルミーズ受注情報（カード決済）（最新）
        $latestOrderResultCard = $orderResultCards[0];
        // ルミーズ受注情報（最新）
        $latestOrderResult = $latestOrderResultCard->getOrderResult();
        // 受注情報（最新）
        $latestOrder = $latestOrderResult->getOrder();
        // 受注情報詳細（最新）リスト
        $latestOrderItems = $this->orderItemRepository->findBy(['Order' => $latestOrder->getId() ],['OrderItemType' => 'ASC']);
        // 受注情報詳細（最新）リストから商品明細の受注情報詳細を取得
        $latestOrderItemProduct = null;
        foreach ($latestOrderItems as $tmpLatestOrderItem){
            if($tmpLatestOrderItem->isProduct()){
                $latestOrderItemProduct = $tmpLatestOrderItem;
            }
        }
        // 受注情報詳細から商品情報を取得
        $product = $latestOrderItemProduct->getProduct();
        // 受注情報詳細から商品規格情報を取得
        $productClass = $latestOrderItemProduct->getProductClass();
        // ルミーズの拡張項目を商品規格情報セットするため、再度DB検索する
        $productClass = $this->productClassRepository->findOneBy(['id' => $productClass->getId()]);
        // 会員情報
        $customer = $latestOrder->getCustomer();
        // 配送情報
        $latestShipping = $latestOrderItemProduct->getShipping();

        /*** 受注情報の作成 ***/
        // ランダムID
        $newOrder->setPreOrderId($this->acImportCreatePreOrderId());

        // お支払方法
        $newOrder->setPayment($latestOrder->getPayment());
        $newOrder->setPaymentMethod($latestOrder->getPaymentMethod());

        // 受注顧客情報
        if($customer){
            $newOrder->setCustomer($customer);
            $newOrder->setName01($customer->getName01());
            $newOrder->setName02($customer->getName02());
            $newOrder->setKana01($customer->getKana01());
            $newOrder->setKana02($customer->getKana02());
            $newOrder->setCompanyName($customer->getCompanyName());
            $newOrder->setEmail($customer->getEmail());
            $newOrder->setPhoneNumber($customer->getPhoneNumber());
            $newOrder->setPostalCode($customer->getPostalCode());
            $newOrder->setAddr01($customer->getAddr01());
            $newOrder->setAddr02($customer->getAddr02());
            $newOrder->setBirth($customer->getBirth());
            $newOrder->setCountry($customer->getCountry());
            $newOrder->setPref($customer->getPref());
            $newOrder->setSex($customer->getSex());
            $newOrder->setJob($customer->getJob());
            $newOrder->setDeviceType($latestOrder->getDeviceType());
        }else{
            $newOrder->setName01($latestOrder->getName01());
            $newOrder->setName02($latestOrder->getName02());
            $newOrder->setKana01($latestOrder->getKana01());
            $newOrder->setKana02($latestOrder->getKana02());
            $newOrder->setCompanyName($latestOrder->getCompanyName());
            $newOrder->setEmail($latestOrder->getEmail());
            $newOrder->setPhoneNumber($latestOrder->getPhoneNumber());
            $newOrder->setPostalCode($latestOrder->getPostalCode());
            $newOrder->setAddr01($latestOrder->getAddr01());
            $newOrder->setAddr02($latestOrder->getAddr02());
            $newOrder->setBirth($latestOrder->getBirth());
            $newOrder->setCountry($latestOrder->getCountry());
            $newOrder->setPref($latestOrder->getPref());
            $newOrder->setSex($latestOrder->getSex());
            $newOrder->setJob($latestOrder->getJob());
            $newOrder->setDeviceType($latestOrder->getDeviceType());
        }

        // 注文日
        $newOrder->setOrderDate(\DateTime::createFromFormat('Ymd', $chargeDate));

        // 状態設定
        if(strcmp($remiseACImportInfo->getAcResult(),trans('1'))==0){

            // 入金日
            $newOrder->setPaymentDate(\DateTime::createFromFormat('Ymd', $chargeDate));

            // 課金成功
            $newOrder->setOrderStatus($this->orderStatusRepository->find(OrderStatus::PAID));
        }else{
            // 課金失敗
            $newOrder->setOrderStatus($this->orderStatusRepository->find(OrderStatus::NEW));
        }

        // 保存
        $this->entityManager->persist($newOrder);
        $this->entityManager->flush();

        // 一度コミットしてから、実行する
        // 受注番号採番
        $purchaseContext = new PurchaseContext($newOrder, $newOrder->getCustomer());
        $this->orderNoProcessor->process($newOrder, $purchaseContext);

        /*** 配送情報の作成 ***/
        $newShipping = $this->acImportCreateShipping($newOrder, $latestShipping);
        $this->entityManager->persist($newShipping);
        $this->entityManager->flush();
        $newOrder->addShipping($newShipping);
        $this->entityManager->persist($newOrder);
        $this->entityManager->flush();


        /*** 受注情報詳細の作成 ***/

        $firstFlg = false;
        if($latestOrderResultCard->getRemiseACResult()->getResult() == trans('remise_payment4.ac.plg_remise_payment4_remise_ac_result.result.first.key'))
        { // 前回の受注情報が初回購入の場合

            $firstFlg = true;
        }
        else
       { // 前回の受注情報が初回購入以外の場合

            $firstFlg = false;
        }

        $subtotal = 0;
        $deliveryFeeTotal = 0;
        $charge = 0;
        $discount = 0;
        $totalPrice = 0;
        $totalTax = 0;
        $productQuantity = 0;

        foreach ($latestOrderItems as $latestOrderItem) {
            // ポイント明細の場合、作成対象外
            if ($latestOrderItem->isPoint()) {
                continue;
            }

            // 受注情報詳細の作成
            $newOrderItem = $this->acImportCreateOrderItem($firstFlg, $productQuantity, $subtotal, $newOrder, $latestOrderItem, $newShipping, $product, $productClass);
            $this->entityManager->persist($newOrderItem);
            $this->entityManager->flush();

            // 受注情報に受注情報詳細をセット
            $newOrder->addOrderItem($newOrderItem);
            $this->entityManager->persist($newOrder);
            $this->entityManager->flush();

            if ($newOrderItem->isProduct()){
                // 商品の合計金額
                if ($newOrderItem->getTaxDisplayType()->getId() == TaxDisplayType::INCLUDED) {
                    $subtotal = $subtotal + ($newOrderItem->getPrice() * $newOrderItem->getQuantity());
                }else{
                    $subtotal = $subtotal + (($newOrderItem->getPrice() + $newOrderItem->getTax()) * $newOrderItem->getQuantity());
                }
                $totalPrice = $totalPrice + $subtotal;
                $productQuantity = $newOrderItem->getQuantity();
            }else if ($newOrderItem->isDeliveryFee()){
                // 送料の合計金額
                if ($newOrderItem->getTaxDisplayType()->getId() == TaxDisplayType::INCLUDED) {
                    $deliveryFeeTotal = $deliveryFeeTotal + ($newOrderItem->getPrice() * $newOrderItem->getQuantity());
                }else{
                    $deliveryFeeTotal = $deliveryFeeTotal + (($newOrderItem->getPrice() + $newOrderItem->getTax()) * $newOrderItem->getQuantity());
                }
                $totalPrice = $totalPrice + $deliveryFeeTotal;
            }else if ($newOrderItem->isCharge()){
                // 手数料の合計金額
                if ($newOrderItem->getTaxDisplayType()->getId() == TaxDisplayType::INCLUDED) {
                    $charge = $charge + ($newOrderItem->getPrice() * $newOrderItem->getQuantity());
                }else{
                    $charge = $charge + (($newOrderItem->getPrice() + $newOrderItem->getTax()) * $newOrderItem->getQuantity());
                }
                $totalPrice = $totalPrice + $charge;
            }else if ($newOrderItem->isDiscount()){
                // 値引きの合計金額
                if ($newOrderItem->getTaxDisplayType()->getId() == TaxDisplayType::INCLUDED) {
                    $discount = $discount + ($newOrderItem->getPrice() * $newOrderItem->getQuantity());
                }else{
                    $discount = $discount + (($newOrderItem->getPrice() + $newOrderItem->getTax()) * $newOrderItem->getQuantity());
                }
                $totalPrice = $totalPrice + $discount;
            }else{
                // その他
                if ($newOrderItem->getTaxDisplayType()->getId() == TaxDisplayType::INCLUDED) {
                    $totalPrice = $totalPrice + ($newOrderItem->getPrice() * $newOrderItem->getQuantity());
                }else{
                    $totalPrice = $totalPrice + (($newOrderItem->getPrice() + $newOrderItem->getTax()) * $newOrderItem->getQuantity());
                }
            }

            // 全体の合計税額
            $totalTax = $totalTax + ($newOrderItem->getTax() * $newOrderItem->getQuantity());
        }

        // 受注情報詳細（商品）の合計金額
        $newOrder->setSubtotal($subtotal);
        // 受注情報詳細（送料）の合計金額
        $newOrder->setDeliveryFeeTotal($deliveryFeeTotal);
        // 受注情報詳細（手数料）の合計金額
        $newOrder->setCharge($charge);
        // 受注情報詳細（値引き）の合計金額(dtb_orderは正数で入れないといけないので、-1を掛ける必要がある)
        $newOrder->setDiscount($discount * -1);
        // 受注情報詳細全体の合計金額
        $newOrder->setTotal($totalPrice);
        // 受注情報詳細全体の合計税額
        $newOrder->setTax($totalTax);

        // お支払い合計(ルミーズから取り込んだ金額を設定)
        $newOrder->setPaymentTotal($remiseACImportInfo->getAcAmount());

        $this->entityManager->persist($newOrder);
        $this->entityManager->flush();

        // ポイント設定
        if($productClass->getRemisePayment4AcPointFlg()){
            $this->utilService->setPoint($newOrder);
            $this->entityManager->persist($newOrder);
            $this->entityManager->flush();
        }

        return $newOrder;
    }

    /**
     * pre_order_idを生成（EC-CUBE本体流用）
     *
     * @return $preOrderId
     */
    private function acImportCreatePreOrderId()
    {
        // ランダムなpre_order_idを作成
        do {
            $preOrderId = sha1(StringUtil::random(32));

            $Order = $this->orderRepository->findOneBy(
                [
                    'pre_order_id' => $preOrderId,
                ]
                );
        } while ($Order);

        return $preOrderId;
    }

    /**
     * Shipping(配送方法)生成
     *
     * @param Order $Order
     * @param Shipping $originShipping
     *
     * @return $Shipping
     */
    private function acImportCreateShipping(Order $Order, Shipping $originShipping)
    {
        $Shipping = new Shipping();

        $Shipping
        ->setName01($originShipping->getName01())
        ->setName02($originShipping->getName02())
        ->setKana01($originShipping->getKana01())
        ->setKana02($originShipping->getKana02())
        ->setCompanyName($originShipping->getCompanyName())
        ->setPhoneNumber($originShipping->getPhoneNumber())
        ->setPostalCode($originShipping->getPostalCode())
        ->setPref($originShipping->getPref())
        ->setAddr01($originShipping->getAddr01())
        ->setAddr02($originShipping->getAddr02())
        ->setShippingDeliveryName($originShipping->getShippingDeliveryName())
        ->setDelivery($originShipping->getDelivery())
        ->setOrder($Order);

        return $Shipping;
    }

    /**
     * Shipping(配送方法)生成
     *
     * @param $productPrice
     * @param $productQuantity
     * @param $productTax
     * @param $deliveryFeeTotal
     * @param Order $Order
     * @param OrderItem $originOrderItem　
     * @param Shipping $Shipping
     *
     * @return $OrderItem
     */
    private function acImportCreateOrderItem($firstFlg, $productQuantity, $subtotal, Order $Order, OrderItem $originOrderItem, Shipping $Shipping, Product $Product, ProductClass $ProductCalass)
    {
        $OrderItem = new OrderItem();

        $taxRule = $this->taxRuleRepository->getByRule($Product,$ProductCalass);

        if($originOrderItem->isProduct())
        { // 商品

            // 単価
            $productPrice = 0;
            // 個数
            $productQuantity = 0;
            // 税額
            $productTax = 0;

            if($firstFlg)
            { // 前回の受注情報が初回購入の場合
                // 単価を取得（商品規格の単価）
                $productPrice = $ProductCalass->getRemisePayment4AcAmount();

                // 個数を取得（前回の受注情報詳細（商品）の個数）
                $productQuantity = $originOrderItem->getQuantity();

                // 税額（共通または個別の税率から税額を算出）
                $productTax = $this->utilService->getTax($productPrice, $Product, $ProductCalass);
            }
            else
            { // 前回の受注情報が初回購入以外の場合
                // 単価を取得（前回の受注情報の単価）
                $productPrice = $originOrderItem->getPrice();

                // 個数を取得（前回の受注情報詳細（商品）の個数）
                $productQuantity = $originOrderItem->getQuantity();

                // 税額を取得（前回の受注情報詳細（商品）の税額）
                $productTax = $originOrderItem->getTax();
            }

            $OrderItem
            ->setProductName($originOrderItem->getProductName())
            ->setProductCode($originOrderItem->getProductCode())
            ->setClassName1($originOrderItem->getClassName1())
            ->setClassName2($originOrderItem->GetClassName2())
            ->setClassCategoryName1($originOrderItem->getClassCategoryName1())
            ->setClassCategoryName2($originOrderItem->getClassCategoryName2())
            ->setPrice($productPrice)
            ->setQuantity($productQuantity)
            ->setTax($productTax)
            ->setTaxRate($taxRule->getTaxRate())
            ->setTaxRuleId($originOrderItem->getTaxRuleId())
            ->setOrder($Order)
            ->setProduct($originOrderItem->getProduct())
            ->setProductClass($originOrderItem->getProductClass())
            ->setShipping($Shipping)
            ->setRoundingType($originOrderItem->getRoundingType())
            ->setTaxType($originOrderItem->getTaxType())
            ->setTaxDisplayType($originOrderItem->getTaxDisplayType())
            ->setOrderItemType($originOrderItem->getOrderItemType())
            ->setPointRate($originOrderItem->getPointRate());
        }
        else if($originOrderItem->isDeliveryFee())
        { // 送料

            $deliveryFeeTotal = 0;
            $deliveryFeeTax = 0;
            $deliveryFeeQuantity = 0;

            if($firstFlg)
            { // 前回の受注情報が初回購入の場合

                // 受注情報詳細（送料）の合計金額（共通の送料、個別の送料から送料を算出）
                $deliveryFeeTotal = $this->utilService->getDeliveryFee($productQuantity, $subtotal, $Order->getId(), $ProductCalass);

                // 受注情報詳細（送料）の税額
                $deliveryFeeTax = $deliveryFeeTotal - round( ( $deliveryFeeTotal / ( ( $taxRule->getTaxRate() / 100 ) + 1 ) ) );

                // EC-CUBEが送料無料条件で無料となった場合に、送料ではなく、個数を0にしているために1を固定でセットする必要がある。
                $deliveryFeeQuantity = 1;
            }
            else
            { // 前回の受注情報が初回購入以外の場合

                // 受注情報詳細（送料）の合計金額（前回の受注情報詳細（送料）の送料）
                $deliveryFeeTotal = $originOrderItem->getPrice();

                // 受注情報詳細（送料）の税額
                $deliveryFeeTax = $originOrderItem->getTax();

                // 前回の個数を引き継ぐ
                $deliveryFeeQuantity = $originOrderItem->getQuantity();
            }

            $OrderItem
            ->setProductName($originOrderItem->getProductName())
            ->setProductCode($originOrderItem->getProductCode())
            ->setClassName1($originOrderItem->getClassName1())
            ->setClassName2($originOrderItem->GetClassName2())
            ->setClassCategoryName1($originOrderItem->getClassCategoryName1())
            ->setClassCategoryName2($originOrderItem->getClassCategoryName2())
            ->setPrice($deliveryFeeTotal)
            ->setQuantity($deliveryFeeQuantity)
            ->setTax($deliveryFeeTax)
            ->setTaxRate($taxRule->getTaxRate())
            ->setTaxRuleId($originOrderItem->getTaxRuleId())
            ->setOrder($Order)
            ->setProduct($originOrderItem->getProduct())
            ->setProductClass($originOrderItem->getProductClass())
            ->setShipping($Shipping)
            ->setRoundingType($originOrderItem->getRoundingType())
            ->setTaxType($originOrderItem->getTaxType())
            ->setTaxDisplayType($originOrderItem->getTaxDisplayType())
            ->setOrderItemType($originOrderItem->getOrderItemType())
            ->setPointRate($originOrderItem->getPointRate());
        }
        else
       { // 手数料・割引
            $OrderItem
            ->setProductName($originOrderItem->getProductName())
            ->setProductCode($originOrderItem->getProductCode())
            ->setClassName1($originOrderItem->getClassName1())
            ->setClassName2($originOrderItem->GetClassName2())
            ->setClassCategoryName1($originOrderItem->getClassCategoryName1())
            ->setClassCategoryName2($originOrderItem->getClassCategoryName2())
            ->setPrice($originOrderItem->getPrice())
            ->setQuantity($originOrderItem->getQuantity())
            ->setTax($originOrderItem->getTax())
            ->setTaxRate($taxRule->getTaxRate())
            ->setTaxRuleId($originOrderItem->getTaxRuleId())
            ->setOrder($Order)
            ->setProduct($originOrderItem->getProduct())
            ->setProductClass($originOrderItem->getProductClass())
            ->setShipping($Shipping)
            ->setRoundingType($originOrderItem->getRoundingType())
            ->setTaxType($originOrderItem->getTaxType())
            ->setTaxDisplayType($originOrderItem->getTaxDisplayType())
            ->setOrderItemType($originOrderItem->getOrderItemType())
            ->setPointRate($originOrderItem->getPointRate());
        }

        return $OrderItem;
    }

    /**
     * ルミーズ受注情報、ルミーズ受注情報（カード決済）、 ルミーズ定期購買結果情報生成
     *
     * @param $chargeDate
     * @param RemiseACImportInfo $remiseACImportInfo
     * @param Order $Order
     *
     */
    private function acImportCreateRemiseOrder($chargeDate, RemiseACImportInfo $remiseACImportInfo, Order $Order)
    {
        // ルミーズ受注情報
        $OrderResult = new OrderResult();
        $OrderResult->setId($Order->getId());
        $OrderResult->setKind(trans('remise_payment4.common.label.payment.kind.card'));
        $OrderResult->setPaymentId($Order->getPayment()->getId());
        $OrderResult->setPaymentTotal($Order->getPaymentTotal());
        $OrderResult->setRequestDate(new \DateTime());
        $OrderResult->setCompleteDate(new \DateTime());
        $OrderResult->setCreateDate(new \DateTime());
        $OrderResult->setUpdateDate(new \DateTime());
        $this->entityManager->persist($OrderResult);
        $this->entityManager->flush();

        // ルミーズ受注情報（カード決済）
        $OrderResultCard = new OrderResultCard();
        $OrderResultCard->setId($Order->getId());
        if(strcmp($remiseACImportInfo->getAcResult(),trans('1'))==0){
            // 課金成功
            $OrderResultCard->setState(trans('remise_payment4.common.label.card.state.complete'));
        }else{
            // 課金失敗
            $OrderResultCard->setState(trans('remise_payment4.common.label.card.state.result.ac.failed'));
        }
        $OrderResultCard->setJob("CAPTURE");
        $OrderResultCard->setTranid($remiseACImportInfo->getTranid());
        $OrderResultCard->setRCode($remiseACImportInfo->getRCode());
        $OrderResultCard->setCardParts($remiseACImportInfo->getCard());
        $OrderResultCard->setExpire($remiseACImportInfo->getExpire());
        $OrderResultCard->setMemberId($remiseACImportInfo->getMemberId());
        $OrderResultCard->setAcTotal($remiseACImportInfo->getAcAmount());
        $OrderResultCard->setAcNextDate(\DateTime::createFromFormat('Ymd', $remiseACImportInfo->getAcNextDate()));
        $OrderResultCard->setCreateDate(new \DateTime());
        $OrderResultCard->setUpdateDate(new \DateTime());
        $this->entityManager->persist($OrderResultCard);
        $this->entityManager->flush();

        // ルミーズ定期購買結果情報
        $remiseACResult = $this->createRemiseACResult(
            $Order->getId(),
            $remiseACImportInfo->getAcResult(),
            \DateTime::createFromFormat('Ymd', $chargeDate));

        $this->entityManager->persist($remiseACResult);
        $this->entityManager->flush();

        return;
    }

    /**
     * ルミーズ定期購買メンバー情報更新
     *
     * @param RemiseACMember $remiseACMember
     * @param $keizokuEditExtendResultData
     *
     */
    private function acImportUpdateRemiseAcMemberUseAPI(RemiseACMember $remiseACMember, $keizokuEditExtendResultData)
    {
        $errMsg = "";

        $keizokuEditExtendResultData["X_CARD"] = (isset($keizokuEditExtendResultData["X_LATEST_CARD"])) ? $keizokuEditExtendResultData["X_LATEST_CARD"] : "";
        $keizokuEditExtendResultData["X_EXPIRE"] = (isset($keizokuEditExtendResultData["X_LATEST_EXPIRE"])) ? $keizokuEditExtendResultData["X_LATEST_EXPIRE"] : "";
        $keizokuEditExtendResultData["X_NAME"] = (isset($keizokuEditExtendResultData["X_LATEST_NAME"])) ? $keizokuEditExtendResultData["X_LATEST_NAME"] : "";
        $keizokuEditExtendResultData["X_AC_AMOUNT"] = (isset($keizokuEditExtendResultData["X_LATEST_AC_AMOUNT"])) ? $keizokuEditExtendResultData["X_LATEST_AC_AMOUNT"] : "";
        $keizokuEditExtendResultData["X_AC_TAX"] = (isset($keizokuEditExtendResultData["X_LATEST_AC_TAX"])) ? $keizokuEditExtendResultData["X_LATEST_AC_TAX"] : "";
        $keizokuEditExtendResultData["X_AC_TOTAL"] = (isset($keizokuEditExtendResultData["X_LATEST_AC_TOTAL"])) ? $keizokuEditExtendResultData["X_LATEST_AC_TOTAL"] : "";
        $keizokuEditExtendResultData["X_AC_INTERVAL"] = (isset($keizokuEditExtendResultData["X_LATEST_AC_INTERVAL"])) ? $keizokuEditExtendResultData["X_LATEST_AC_INTERVAL"] : "";
        $keizokuEditExtendResultData["X_AC_NEXT_DATE"] = (isset($keizokuEditExtendResultData["X_LATEST_AC_NEXT_DATE"])) ? $keizokuEditExtendResultData["X_LATEST_AC_NEXT_DATE"] : "";
        $keizokuEditExtendResultData["X_AUTOCHARGE"] = (isset($keizokuEditExtendResultData["X_LATEST_AUTOCHARGE"])) ? $keizokuEditExtendResultData["X_LATEST_AUTOCHARGE"] : "";
        $keizokuEditExtendResultData["X_AC_STOP_DATE"] = (isset($keizokuEditExtendResultData["X_LATEST_AC_STOP_DATE"])) ? $keizokuEditExtendResultData["X_LATEST_AC_STOP_DATE"] : "";

        // 課金間隔の編集
        $acInterval = $keizokuEditExtendResultData['X_AC_INTERVAL'];
        $acIntervalValue = "";
        $acIntervalMark = "";
        $pos = strpos($acInterval, "M");
        if ($pos !== false) {
            $acIntervalValue = substr($acInterval, 0, $pos);
            $acIntervalMark = "M";
        } else {
            $pos = strpos($acInterval, "D");
            if ($pos !== false) {
                $acIntervalValue = substr($acInterval, 0, $pos);
                $acIntervalMark = "D";
            }
        }

        // 差分チェック
        if ($remiseACMember->getTotal() != $keizokuEditExtendResultData["X_AC_TOTAL"]) {
            $errMsg .= "定期購買金額";
        }
        if ($remiseACMember->getIntervalValue() != $acIntervalValue
            || $remiseACMember->getIntervalMark() != $acIntervalMark) {
                if (!empty($errMsg)) $errMsg .= "、";
                $errMsg .= "課金間隔";
            }
        if (!$remiseACMember->isStatus() && $keizokuEditExtendResultData["X_AUTOCHARGE"] == 1) {
            if (!empty($errMsg)) $errMsg .= "、";
            $errMsg .= "定期購買の状態";
        }
        if (!empty($errMsg)) {
            return $errMsg;
        }

        // 定期購買状態(無効/有効)
        $remiseACMember->setStatus($keizokuEditExtendResultData["X_AUTOCHARGE"]);
        // スキップ実施フラグ
        $remiseACMember->setSkipped(0);
        // カード番号
        $remiseACMember->setCardparts($keizokuEditExtendResultData["X_CARD"]);
        // 有効期限
        $remiseACMember->setExpire(substr($keizokuEditExtendResultData["X_EXPIRE"],2,2).substr($keizokuEditExtendResultData["X_EXPIRE"],0,2));
        // 課金額
        $remiseACMember->setTotal($keizokuEditExtendResultData["X_AC_TOTAL"]);
        // 課金間隔
        $remiseACMember->setIntervalValue($acIntervalValue);
        $remiseACMember->setIntervalMark($acIntervalMark);
        // 課金日
        if ($remiseACMember->getIntervalMark() == "M") {
            $remiseACMember->setDayOfMonth($remiseACMember->getNextDate()->format('d'));
        } else {
            $remiseACMember->setDayOfMonth(0);
        }
        // 次回課金日
        $remiseACMember->setNextDate(\DateTime::createFromFormat('Ymd', $keizokuEditExtendResultData["X_AC_NEXT_DATE"]));
        // 課金終了予定日
        if (!empty($keizokuEditExtendResultData['X_AC_STOP_DATE'])) {
            $acStopDate = \DateTime::createFromFormat('Ymd', $keizokuEditExtendResultData["X_AC_STOP_DATE"]);
            $remiseACMember->setStopDate($acStopDate);
            $remiseACMember->setLimitless(0);
            $remiseACMember->setCount($this->getCountFromStopDate($remiseACMember));
            if ($remiseACMember->getCount() == 0) {
                $remiseACMember->setStatus(0);
            }
        } else {
            $remiseACMember->setLimitless(1);
            $remiseACMember->setCount(0);
            $remiseACMember->setStopDate(null);
        }

        $this->entityManager->persist($remiseACMember);
        $this->entityManager->flush();

        return $errMsg;
    }

    /**
     * ルミーズ定期購買メンバー情報更新
     *
     * @param RemiseACMember $remiseACMember
     * @param $keizokuEditExtendResultData
     *
     */
    private function acImportUpdateRemiseAcMemberUnusedAPI(RemiseACMember $remiseACMember, RemiseACImportInfo $remiseACImportInfo)
    {
        // 取込結果情報の次回課金日
        $acNextDate = new \DateTime($remiseACImportInfo->getAcNextDate());

        if ($remiseACMember->getIntervalMark() == "M") {
            $remiseACMember->setDayOfMonth($acNextDate->format('d'));
        }
        if (!$remiseACMember->isLimitless()) {
            $remiseACMember->setCount($this->getRemainingCount($remiseACMember, $acNextDate));
            if ($remiseACMember->getCount() == 0) {
                $remiseACMember->setStatus(0);
            }
        }
        $remiseACMember->setCardparts($remiseACImportInfo->getCard());
        $remiseACMember->setExpire($remiseACImportInfo->getExpire());
        $remiseACMember->setNextDate($acNextDate);
        $remiseACMember->setSkipped(0);

        $this->entityManager->persist($remiseACMember);
        $this->entityManager->flush();

        return;
    }

    /**
     * ルミーズ自動継続課金結果通知情報の取得
     *
     * @param  string  $execDate  Ymd形式の文字列
     */
    public function acImportGetRemiseACImport($execDate) {
        $remiseACImportList = $this->remiseACImportRepository->getExecData($execDate);

        return $remiseACImportList;
    }

    /**
     * ルミーズ自動継続課金結果通知情報の登録
     *
     * @param  array  $postData  応答情報
     */
    public function acImportInsertRemiseACImport($postData)
    {
        $execDate = $postData["EXEC_DATE"];

        // 重複レコードチェック
        $remiseACImportList = $this->acImportGetRemiseACImport(substr($execDate,0,8));
        if ($remiseACImportList) return;

        // ルミーズ自動継続課金結果通知情報生成
        $remiseACImport = new RemiseACImport();
        $remiseACImport
            ->setSuccessCnt(0)
            ->setSuccessAmount(0)
            ->setFailCnt(0)
            ->setFailAmount(0)
            ->setTotalCnt(0)
            ->setTotalAmount(0)
            ->setExecDate(new \DateTime($execDate))
            ->setImportFlg(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_import.import_flg_ini'))
            ->setCreateDate(new \DateTime())
            ->setUpdateDate(new \DateTime());
        if (isset($postData["SUCCESS_CNT"])) {
            $remiseACImport->setSuccessCnt($postData["SUCCESS_CNT"]);
        }
        if (isset($postData["SUCCESS_AMOUNT"])) {
            $remiseACImport->setSuccessAmount($postData["SUCCESS_AMOUNT"]);
        }
        if (isset($postData["FAIL_CNT"])) {
            $remiseACImport->setFailCnt($postData["FAIL_CNT"]);
        }
        if (isset($postData["FAIL_AMOUNT"])) {
            $remiseACImport->setFailAmount($postData["FAIL_AMOUNT"]);
        }
        if (isset($postData["TOTAL_CNT"])) {
            $remiseACImport->setTotalCnt($postData["TOTAL_CNT"]);
        }
        if (isset($postData["TOTAL_AMOUNT"])) {
            $remiseACImport->setTotalAmount($postData["TOTAL_AMOUNT"]);
        }

        // ルミーズ自動継続課金結果通知情報の登録
        $this->entityManager->persist($remiseACImport);
        $this->entityManager->flush($remiseACImport);
        $this->entityManager->commit();

        return;
    }

    /**
     * ルミーズ定期購買取込結果更新
     *
     * @param $importFlg
     * @param $chargeDate
     * @param $remiseACImportInfoList
     *
     * @return $remiseACImportList
     *
     */
    public function acImportUpdateRemiseACImport($importFlg, $chargeDate, $remiseACImportInfoList = null) {
        $remiseACImportList = $this->acImportGetRemiseACImport($chargeDate);

        if ($remiseACImportList) {
            $importCnt = 0;
            if ($remiseACImportInfoList) {
                foreach ($remiseACImportInfoList as $remiseACImportInfo) {
                    if ($remiseACImportInfo->getStatus() == trans('remise_payment4.ac.admin_import_result.text.import.key') &&
                        $remiseACImportInfo->getNote() != trans('remise_payment4.ac.admin_import_result.text.note.captured')) {
                        $importCnt++;
                    }
                }
            }

            foreach ($remiseACImportList as $remiseACImport) {
                $importCnt = $importCnt + $remiseACImport->getImportCnt();

                if (empty($importFlg)) {
                    if ($importCnt == $remiseACImport->getTotalCnt()) {
                        $importFlg = trans('remise_payment4.ac.plg_remise_payment4_remise_ac_import.import_flg_cmp');
                    } else {
                        $importFlg = trans('remise_payment4.ac.plg_remise_payment4_remise_ac_import.import_flg_prs');
                    }
                }

                $remiseACImport->setImportFlg($importFlg);
                $remiseACImport->setImportCnt($importCnt);
                $remiseACImport->setImportDate(new \DateTime());
                $remiseACImport->setUpdateDate(new \DateTime());
                $this->entityManager->persist($remiseACImport);
            }

            $this->entityManager->flush();
        }

        return $remiseACImportList;
    }

    /**
     * メール送信 定期購買注文受付メール（REMISE定期購買）
     *
     * @param Order $order
     * @param RemiseACMember $remiseACMember
     * @param $kind //メール種別
     *
     */
    public function sendMailAcOrder(Order $order, RemiseACMember $remiseACMember)
    {
        try{
            $kind = trans('remise_payment4.common.label.mail_template.kind.ac_order');

            // ルミーズメールテンプレートの取得
            $RemiseMailTemplate = $this->utilService->getRemiseMailTemplateByKind($kind);

            if (!$RemiseMailTemplate)
            {
                $this->logService->logError('sendMailMypage -- Not Found RemiseMailTemplate');
                return 'NOT_FOUND_REMISE_MAIL_TEMPLATE:' . $kind;
            }

            // EC-CUBEメールテンプレートの取得
            $MailTemplate = $this->utilService->getMailTemplate($RemiseMailTemplate->getId());

            if (!$MailTemplate)
            {
                $this->logService->logError('sendMailMypage -- Not Found MailTemplate (' . $RemiseMailTemplate->getId() . ')');
                return 'NOT_FOUND_MAIL_TEMPLATE:' . $RemiseMailTemplate->getId();
            }

            $interval = "";
            if (strcmp($remiseACMember->getIntervalMark(), trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.interval_marks.m.key')) == 0)
            {
                $interval = $remiseACMember->getIntervalValue().trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.interval_marks.m.value');
            }
            else if(strcmp($remiseACMember->getIntervalMark(), trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.interval_marks.d.key')) == 0)
            {
                $interval = $remiseACMember->getIntervalValue().trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.interval_marks.d.value');
            }

            // 課金回数
            $countText = "";
            if($remiseACMember->getCount() && $remiseACMember->getCount() > 0){
                $countText = $remiseACMember->getCount().trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.count.value');
            }else{
                $countText = "";
            }

            // メール本文生成
            $body = $this->twig->render($MailTemplate->getFileName(), [
                'Order' => $order,
                'RemiseACMember' => $remiseACMember,
                'interval' => $interval,
                'remiseAcCount' => $countText
            ]);

            $message = (new Email())
            ->subject('['.$this->baseInfo->getShopName().'] '.$MailTemplate->getMailSubject())
            ->from(new Address($this->baseInfo->getEmail01() , $this->baseInfo->getShopName()))
            ->to($order->getEmail())
            ->bcc($this->baseInfo->getEmail01())
            ->replyTo($this->baseInfo->getEmail03())
            ->returnPath($this->baseInfo->getEmail04());

            // HTMLテンプレートが存在する場合
            $htmlFileName = $this->mailService->getHtmlTemplate($MailTemplate->getFileName());
            if (!is_null($htmlFileName)) {
                $htmlBody = $this->twig->render($htmlFileName, [
                    'Order' => $order,
                    'RemiseACMember' => $remiseACMember,
                    'interval' => $interval,
                    'remiseAcCount' => $countText
                ]);

                $message
                ->text($body)
                ->html($htmlBody);
            } else {
                $message->text($body);
            }

            try {
                $this->mailer->send($message);
            } catch (TransportExceptionInterface $e) {
                $this->logService->logCritical('sendMailAcOrder', Array(
                'ErrCode:' . $e->getCode(),
                'ErrMessage:' . $e->getMessage(),
                'ErrTrace:' . $e->getTraceAsString()
                ));
            }

            // 送信履歴の作成
            $MailHistory = new MailHistory();
            $MailHistory
            ->setMailSubject($message->getSubject())
            ->setMailBody($message->getTextBody())
            ->setSendDate(new \DateTime())
            ->setOrder($order);

            // HTML用メールの設定
            $htmlBody = $message->getHtmlBody();
            if (!empty($htmlBody)) {
                $MailHistory->setMailHtmlBody($htmlBody);
            }

            // 送信履歴の登録
            $this->entityManager->persist($MailHistory);
            $this->entityManager->flush();
            
            return;
        } catch (\Exception $e) {
            // ログ出力
            $this->entityManager->rollback();
            $this->logService->logError('sendMailAcOrder', Array(
                trans('admin.common.system_error'),
                'ErrCode:' . $e->getCode(),
                'ErrMessage:' . $e->getMessage(),
                'ErrTrace:' . $e->getTraceAsString()
                ));
            return;
        }
    }

    /**
     * メール送信 マイページ（更新/スキップ/解約）
     *
     * @param Order $order
     * @param Customer $Customer
     * @param RemiseACMember $remiseACMember
     * @param $kind //メール種別
     *
     */
    public function sendMailMypage(Order $order, $Customer, RemiseACMember $remiseACMember, $kind)
    {
        try{
            // ルミーズメールテンプレートの取得
            $RemiseMailTemplate = $this->utilService->getRemiseMailTemplateByKind($kind);

            if (!$RemiseMailTemplate)
            {
                $this->logService->logError('sendMailMypage -- Not Found RemiseMailTemplate');
                return 'NOT_FOUND_REMISE_MAIL_TEMPLATE:' . $kind;
            }

            // EC-CUBEメールテンプレートの取得
            $MailTemplate = $this->utilService->getMailTemplate($RemiseMailTemplate->getId());

            if (!$MailTemplate)
            {
                $this->logService->logError('sendMailMypage -- Not Found MailTemplate (' . $RemiseMailTemplate->getId() . ')');
                return 'NOT_FOUND_MAIL_TEMPLATE:' . $RemiseMailTemplate->getId();
            }

            // 商品明細を取得
            $orderItems = $order->getItems();
            $orderItem = new OrderItem();
            foreach ($orderItems as $tempOrderItem) {
                if ($tempOrderItem->isProduct()) {
                    $orderItem = $tempOrderItem;
                    break;
                }
            }

            // メール本文生成
            $body = $this->twig->render($MailTemplate->getFileName(), [
                'Order' => $order,
                'OrderItem' => $orderItem,
                'RemiseACMember' => $remiseACMember,
            ]);

            $message = (new Email())
            ->subject('['.$this->baseInfo->getShopName().'] '.$MailTemplate->getMailSubject())
            ->from(new Address($this->baseInfo->getEmail01() , $this->baseInfo->getShopName()))
            ->to($Customer->getEmail())
            ->bcc($this->baseInfo->getEmail01())
            ->replyTo($this->baseInfo->getEmail03())
            ->returnPath($this->baseInfo->getEmail04());

            // HTMLテンプレートが存在する場合
            $htmlFileName = $this->mailService->getHtmlTemplate($MailTemplate->getFileName());
            if (!is_null($htmlFileName)) {
                $htmlBody = $this->twig->render($htmlFileName, [
                    'Order' => $order,
                    'OrderItem' => $orderItem,
                    'RemiseACMember' => $remiseACMember,
                ]);

                $message
                ->text($body)
                ->html($htmlBody);
            } else {
                $message->text($body);
            }

            try {
                $this->mailer->send($message);
            } catch (TransportExceptionInterface $e) {
                $this->logService->logCritical('sendMailMypage', Array(
                'ErrCode:' . $e->getCode(),
                'ErrMessage:' . $e->getMessage(),
                'ErrTrace:' . $e->getTraceAsString()
                ));
            }

            // 送信履歴の作成
            $MailHistory = new MailHistory();
            $MailHistory
            ->setMailSubject($message->getSubject())
            ->setMailBody($message->getBody())
            ->setSendDate(new \DateTime())
            ->setOrder($order);

            // HTML用メールの設定
            $htmlBody = $message->getHtmlBody();
            if (!empty($htmlBody)) {
                $MailHistory->setMailHtmlBody($htmlBody);
            }

            // 送信履歴の登録
            $this->mailHistoryRepository->save($MailHistory);

            return;
        } catch (\Exception $e) {
            // ログ出力
            $this->entityManager->rollback();
            $this->logService->logError('sendMailMypage', Array(
                trans('admin.common.system_error'),
                'ErrCode:' . $e->getCode(),
                'ErrMessage:' . $e->getMessage(),
                'ErrTrace:' . $e->getTraceAsString()
                ));
            return;
        }
    }

    /**
     * メール送信 マイページ（更新/スキップ/解約）
     *
     * @param string $kind メール種別
     * @param RemiseACImport $remiseACImport
     * @param string $errMsg エラーメッセージ
     *
     */
    public function sendMailResultImport($kind, RemiseACImport $remiseACImport, $remiseACImportInfoList, $filePath = null, $errMsg = null)
    {
        try {
            // ルミーズメールテンプレートの取得
            $RemiseMailTemplate = $this->utilService->getRemiseMailTemplateByKind($kind);

            if (!$RemiseMailTemplate) {
                $this->logService->logError('sendMailMypage -- Not Found RemiseMailTemplate');
                return 'NOT_FOUND_REMISE_MAIL_TEMPLATE:' . $kind;
            }

            // EC-CUBEメールテンプレートの取得
            $MailTemplate = $this->utilService->getMailTemplate($RemiseMailTemplate->getId());

            if (!$MailTemplate) {
                $this->logService->logError('sendMailMypage -- Not Found MailTemplate (' . $RemiseMailTemplate->getId() . ')');
                return 'NOT_FOUND_MAIL_TEMPLATE:' . $RemiseMailTemplate->getId();
            }

            $importCnt = 0;
            $importNotCnt = 0;
            foreach ($remiseACImportInfoList as $remiseACImportInfo) {
                if($remiseACImportInfo->getStatus() == trans('remise_payment4.ac.admin_import_result.text.import.key')) {
                    $importCnt++;
                }

                if($remiseACImportInfo->getStatus() == trans('remise_payment4.ac.admin_import_result.text.not_import.key')) {
                    $importNotCnt++;
                }
            }

            // ファイル添付フラグ
            $attach = false;
            if(isset($filePath)) {
                $attach = true;
            }

            // メール本文生成
            $body = $this->twig->render($MailTemplate->getFileName(), [
                'remiseACImport' => $remiseACImport,
                'importCnt' => $importCnt,
                'importNotCnt' => $importNotCnt,
                'importTotalCnt' => $importCnt + $importNotCnt,
                'attach' => $attach,
                'errMsg' => $errMsg
            ]);

            $message = (new Email())
                ->subject('['.$this->baseInfo->getShopName().'] '.$MailTemplate->getMailSubject())
                ->from(new Address($this->baseInfo->getEmail01() , $this->baseInfo->getShopName()))
                ->to($this->baseInfo->getEmail01())
                ->replyTo($this->baseInfo->getEmail03())
                ->returnPath($this->baseInfo->getEmail04()
            );

            if(isset($filePath)) {
                $message->attachFromPath($filePath);
            }

            // HTMLテンプレートが存在する場合
            $htmlFileName = $this->mailService->getHtmlTemplate($MailTemplate->getFileName());
            if (!is_null($htmlFileName)) {
                $htmlBody = $this->twig->render($htmlFileName, [
                    'remiseACImport' => $remiseACImport,
                    'importCnt' => $importCnt,
                    'importNotCnt' => $importNotCnt,
                    'importTotalCnt' => $importCnt + $importNotCnt,
                    'attach' => $attach,
                    'errMsg' => $errMsg
                ]);

                $message
                ->text($body)
                ->html($htmlBody);
            } else {
                $message->text($body);
            }

            try {
                $this->mailer->send($message);
            } catch (TransportExceptionInterface $e) {
                $this->logService->logCritical('sendMailResultImport', Array(
                'ErrCode:' . $e->getCode(),
                'ErrMessage:' . $e->getMessage(),
                'ErrTrace:' . $e->getTraceAsString()
                ));
            }

            return;
        } catch (\Exception $e) {
            // ログ出力
            $this->entityManager->rollback();
            $this->logService->logError('sendMailResultImport', Array(
                trans('admin.common.system_error'),
                'ErrCode:' . $e->getCode(),
                'ErrMessage:' . $e->getMessage(),
                'ErrTrace:' . $e->getTraceAsString()
                ));
            return;
        }
    }

    /**
     * ルミーズ定期購買取込情報の確認
     *
     * @param string $chargeDate 取込日
     *
     * @return string 確認結果
     */
    public function checkRemiseACImport($chargeDate) {
        // ルミーズ定期購買取込情報（自動継続課金取込結果通知）を取得
        $remiseACImportList = $this->acImportGetRemiseACImport($chargeDate);

        // ルミーズ定期購買取込情報が存在しない場合、エラー
        if (!$remiseACImportList) {
            return 'ERROR:101';
        }

        // 取込状況が「未取込」以外の場合、処理抜け
        if ($remiseACImportList[0]->getImportFlg() != trans('remise_payment4.ac.plg_remise_payment4_remise_ac_import.import_flg_ini')) {
            return trans('remise_payment4.ac.common.const.remise_autocharge_complete_ok');
        }

        return;
    }

    /**
     * ルミーズ定期購買バッチ取込上限件数の取得
     *
     * @return integer 上限件数
     */
    public function getAcMaxNumberAcquisitions() {
        // プラグイン設定情報の取得
        $Config = $this->utilService->getConfig();

        if ($Config) {
            // 設定情報の取得
            $ConfigInfo = $Config->getUnserializeInfo();

            if ($ConfigInfo) {
                // 上限件数返却
                return $ConfigInfo->getAcMaxNumberAcquisitions();
            }
        }

        return 0;
    }

    /**
     * ルミーズ定期購買バッチ取込管理情報の登録
     *
     * @param array $postData 応答情報
     *
     * @return bool 処理結果
     */
    public function acImportStackInsertAcImportStack($postData) {
        try {
            // タイムアウト無効
            set_time_limit(0);

            // 上限件数取得
            $acMaxNumberAcquisitions = $this->getAcMaxNumberAcquisitions();

            for ($i = 1; $i <= $postData["TOTAL_CNT"]; $i = $i + $acMaxNumberAcquisitions) {
                // ルミーズ定期購買バッチ取込管理情報の生成
                $remiseACImportStack = new RemiseACImportStack();
                $remiseACImportStack
                ->setImportDate(new \DateTime($postData["EXEC_DATE"]))
                ->setStartNum($i)
                ->setEndNum($i + $acMaxNumberAcquisitions - 1)
                ->setExecFlg(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_import_stack.exec_flg.false'))
                ->setCompleteFlg(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_import_stack.complete_flg.false'))
                ->setCreateDate(new \DateTime())
                ->setUpdateDate(new \DateTime());

                // ルミーズ定期購買バッチ取込管理情報の登録
                $this->entityManager->persist($remiseACImportStack);
            }

            $this->entityManager->flush();
            return true;
        } catch (\Exception $e) {
            // ログ出力
            $this->logService->logError('Ac Import Stack Accept -- Error', Array(
                trans('admin.common.system_error'),
                'ErrCode:' . $e->getCode(),
                'ErrMessage:' . $e->getMessage(),
                'ErrTrace:' . $e->getTraceAsString()
            ));
            $this->entityManager->rollback();
            return false;
        }
    }

    /**
     * ルミーズ定期購買バッチ取込管理情報の取得
     *
     * @param string $chargeDate 取込日
     *
     * @return bool 処理結果
    */
    public function acImportStackGetRemiseAcImportStack($chargeDate) {
        $remiseACImportStackList = $this->remiseACImportStackRepository->getExecData($chargeDate);

        return $remiseACImportStackList;
    }

    /**
     * ルミーズ定期購買バッチ取込管理情報の変更
     *
     * @param RemiseACImportStack $remiseACImportStack ルミーズ定期購買バッチ取込管理情報
     *
     * @return bool 処理結果
     */
    public function acImportStackUpdateExecFlg($remiseACImportStack) {
        try {
            // 実行フラグを「済」に変更
            $remiseACImportStack->setExecFlg(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_import_stack.exec_flg.true'));
            $remiseACImportStack->setUpdateDate(new \DateTime());

            // ルミーズ定期購買バッチ取込管理情報の登録
            $this->entityManager->persist($remiseACImportStack);
            $this->entityManager->flush();
            return true;
        } catch (\Exception $e) {
            // ログ出力
            $this->logService->logError('Ac Result Batch Import -- Error', Array(
                trans('admin.common.system_error'),
                'ErrCode:' . $e->getCode(),
                'ErrMessage:' . $e->getMessage(),
                'ErrTrace:' . $e->getTraceAsString()
            ));
            $this->entityManager->rollback();
            return false;
        }
    }

    /**
     * ルミーズ定期購買バッチ取込管理情報の変更
     *
     * @param RemiseACImportStack $remiseACImportStack ルミーズ定期購買バッチ取込管理情報
     *
     * @return bool 処理結果
     */
    public function acImportStackUpdateCompleteFlg($remiseACImportStack) {
        try {
            // 完了フラグを「済」に変更
            $remiseACImportStack->setCompleteFlg(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_import_stack.complete_flg.true'));
            $remiseACImportStack->setUpdateDate(new \DateTime());

            // ルミーズ定期購買バッチ取込管理情報の登録
            $this->entityManager->persist($remiseACImportStack);
            $this->entityManager->flush();
            return true;
        } catch (\Exception $e) {
            // ログ出力
            $this->logService->logError('Ac Result Batch Import -- Error', Array(
                trans('admin.common.system_error'),
                'ErrCode:' . $e->getCode(),
                'ErrMessage:' . $e->getMessage(),
                'ErrTrace:' . $e->getTraceAsString()
            ));
            $this->entityManager->rollback();
            return false;
        }
    }

    /**
     * ルミーズ定期購買バッチ取込情報の登録
     *
     * @param \DateTime $chargeDate 取込日
     * @param array $remiseACImportInfoList ルミーズ定期購買取込詳細情報
     *
     * @return bool 処理結果
     */
    public function acImportStackInsertAcImportStackResult($chargeDate, $remiseACImportInfoList) {
        try {
            // タイムアウト無効
            set_time_limit(0);

            foreach ($remiseACImportInfoList as $remiseACImportInfo) {
                // ルミーズ定期購買バッチ取込情報の生成
                $remiseACImportStackResult = new RemiseACImportStackResult();
                $remiseACImportStackResult
                ->setImportDate(\DateTime::createFromFormat('Ymd', $chargeDate))
                ->setRCode($remiseACImportInfo->getRCode())
                ->setMemberId($remiseACImportInfo->getMemberId())
                ->setSKaiinNo($remiseACImportInfo->getSKaiinNo())
                ->setAcName($remiseACImportInfo->getAcName())
                ->setAcKana($remiseACImportInfo->getAcKana())
                ->setAcTel($remiseACImportInfo->getAcTel())
                ->setAcMail($remiseACImportInfo->getAcMail())
                ->setAcAmount($remiseACImportInfo->getAcAmount())
                ->setAcResult($remiseACImportInfo->getAcResult())
                ->setTranid($remiseACImportInfo->getTranid())
                ->setErrcode($remiseACImportInfo->getErrcode())
                ->setErrinfo($remiseACImportInfo->getErrinfo())
                ->setAcNextDate($remiseACImportInfo->getAcNextDate())
                ->setCard($remiseACImportInfo->getCard())
                ->setExpire($remiseACImportInfo->getExpire())
                ->setNote($remiseACImportInfo->getNote())
                ->setStatus($remiseACImportInfo->getStatus())
                ->setOrderId($remiseACImportInfo->getOrderId())
                ->setOrderNo($remiseACImportInfo->getOrderNo());

                // ルミーズ定期購買バッチ取込情報の登録
                $this->entityManager->persist($remiseACImportStackResult);
            }

            $this->entityManager->flush();
        } catch (\Exception $e) {
            // ログ出力
            $this->logService->logError('Ac Result Batch Import -- Error', Array(
                trans('admin.common.system_error'),
                'ErrCode:' . $e->getCode(),
                'ErrMessage:' . $e->getMessage(),
                'ErrTrace:' . $e->getTraceAsString()
            ));
            $this->entityManager->rollback();
        }
    }

    /**
     * ルミーズ定期購買取込詳細情報の取得
     *
     * @param string $chargeDate 取込日
     *
     * @return array $remiseACImportInfoList ルミーズ定期購買取込詳細情報
     */
    public function acImportStackGetRemiseAcImportInfo($chargeDate) {
        // タイムアウト無効
        set_time_limit(0);

        // 返却値初期化
        $remiseACImportInfoList = array();

        // ルミーズ定期購買バッチ取込情報の取得
        $remiseACImportStackResultList = $this->remiseACImportStackResultRepository->getAcResultData($chargeDate);

        foreach ($remiseACImportStackResultList as $remiseACImportStackResult) {
            $remiseACImportInfo = new RemiseACImportInfo();
            $remiseACImportInfo
            ->setRCode($remiseACImportStackResult->getRCode())
            ->setMemberId($remiseACImportStackResult->getMemberId())
            ->setSKaiinNo($remiseACImportStackResult->getSKaiinNo())
            ->setAcName($remiseACImportStackResult->getAcName())
            ->setAcKana($remiseACImportStackResult->getAcKana())
            ->setAcTel($remiseACImportStackResult->getAcTel())
            ->setAcMail($remiseACImportStackResult->getAcMail())
            ->setAcAmount($remiseACImportStackResult->getAcAmount())
            ->setAcResult($remiseACImportStackResult->getAcResult())
            ->setTranid($remiseACImportStackResult->getTranid())
            ->setErrcode($remiseACImportStackResult->getErrcode())
            ->setErrinfo($remiseACImportStackResult->getErrinfo())
            ->setAcNextDate($remiseACImportStackResult->getAcNextDate())
            ->setCard($remiseACImportStackResult->getCard())
            ->setExpire($remiseACImportStackResult->getExpire())
            ->setNote($remiseACImportStackResult->getNote())
            ->setStatus($remiseACImportStackResult->getStatus())
            ->setOrderId($remiseACImportStackResult->getOrderId())
            ->setOrderNo($remiseACImportStackResult->getOrderNo());
            $remiseACImportInfoList[] = $remiseACImportInfo;
        }

        return $remiseACImportInfoList;
    }

    /**
     * 取込結果一時ファイルを作成
     *
     * @param array $remiseACImportInfoList ルミーズ定期購買取込情報
     *
     * @return string $filePath ファイルパス
     */
    public function createTemporaryFile($remiseACImportInfoList) {
        $tempDir = $this->projectRoot.'/var/cache/remise';
        $fileName = 'remise_result_'.(new \DateTime())->format('YmdHis').'.csv';
        $filePath = $tempDir.'/'.$fileName;

        try {
            try {
                if (!file_exists($tempDir)) {
                    @mkdir($tempDir);
                } else {
                    $this->delFileDir($this->getFileDirList($tempDir));
                }
            } catch (\Exception $e) {
                $filePath = null;
            }

            if ($filePath) {
                $remiseCsvType = $this->getRemiseCsvTypeAcResult();
                $this->exportCsv($remiseCsvType->getId(), $remiseACImportInfoList, $filePath);
            }

            return $filePath;
        } catch (\Exception $e) {
            // ログ出力
            $this->logService->logError('Ac Result Batch Import -- Error', Array(
                trans('admin.common.system_error'),
                'ErrCode:' . $e->getCode(),
                'ErrMessage:' . $e->getMessage(),
                'ErrTrace:' . $e->getTraceAsString()
            ));
            $this->entityManager->rollback();
        }
    }

    /**
     * 有効期限を過ぎたファイル一覧を削除
     *
     * @param array $list ファイル一覧
     * @param string $expire_date_str 有効期限
     */
    private function delFileDir($list = array(), $expire_date_str = '-1 day') {
        //削除期限
        date_default_timezone_set('Asia/Tokyo');
        $expire_timestamp = 0;

        if (($expire_timestamp = strtotime($expire_date_str)) === false) {}

        foreach ($list as $file_path) {
            $mod = filemtime( $file_path );

            if($mod < $expire_timestamp) {
                if (is_file($file_path)) {
                    unlink($file_path);
                }
            }
        }
    }

    /**
     * 指定したディレクトリのファイルの一覧を取得
     *
     * @param string $dir ディレクトリ
     *
     * @return array $list ファイル一覧
     */
    private function getFileDirList($dir = '') {
        $list = array();

        foreach(glob($dir.'/*') as $file) {
            if(is_file($file)) {
                $list[] = $file;
            }
        }

        return $list;
    }

    /**
     * ルミーズ定期購買取込詳細情報のCSVファイルを出力
     *
     * @param mixed $csvTypeId 識別子
     * @param array $remiseACImportInfoList ルミーズ定期購買取込詳細情報
     * @param array $filePath ファイルパス
     */
    private function exportCsv($csvTypeId, $remiseACImportInfoList, $filePath) {
        try {
            // タイムアウトを無効にする.
            set_time_limit(0);

            $Csvs = $this->getCsvType($csvTypeId);

            // ヘッダ行の出力.
            $ExportCsvRow = new ExportCsvRow();
            foreach ($Csvs as $Csv) {
                $ExportCsvRow->setData($Csv->getDispName());
                $ExportCsvRow->pushData();
            }

            $this->fopen($filePath);
            $this->fputcsv($ExportCsvRow->getRow());

            // データ行の出力.
            foreach ($remiseACImportInfoList as $remiseACImportInfo) {
                $ExportCsvRow = new ExportCsvRow();

                foreach ($Csvs as $Csv) {
                    switch ($Csv->getFieldName()) {
                        case "order_id": // 注文番号
                            if ($remiseACImportInfo->getOrderNo()) {
                                $ExportCsvRow->setData($remiseACImportInfo->getOrderNo());
                            } else {
                                $ExportCsvRow->setData(null);
                            }
                            break;
                        case "member_id": // メンバーID
                            $ExportCsvRow->setData($remiseACImportInfo->getMemberId());
                            break;
                        case "s_kaiin_no": // 会員番号
                            $ExportCsvRow->setData($remiseACImportInfo->getSKaiinNo());
                            break;
                        case "ac_amount": // 定期購買金額
                            $ExportCsvRow->setData($remiseACImportInfo->getAcAmount());
                            break;
                        case "ac_result": // 課金結果
                            if (strcmp($remiseACImportInfo->getAcResult(), trans('remise_payment4.ac.plg_remise_payment4_remise_ac_result.result.success.key')) == 0) {
                                $ExportCsvRow->setData(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_result.result.success.name'));
                            } else if (strcmp($remiseACImportInfo->getAcResult(), trans('remise_payment4.ac.plg_remise_payment4_remise_ac_result.result.failure.key')) == 0) {
                                $ExportCsvRow->setData(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_result.result.failure.name'));
                            }
                            break;
                        case "status": // 取込状況
                            if (strcmp($remiseACImportInfo->getStatus(), trans('remise_payment4.ac.plg_remise_payment4_remise_ac_import.import_flg_ini')) == 0) {
                                $ExportCsvRow->setData(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_import.import_flg_ini.value'));
                            } else if (strcmp($remiseACImportInfo->getStatus(), trans('remise_payment4.ac.plg_remise_payment4_remise_ac_import.import_flg_cmp')) == 0) {
                                $ExportCsvRow->setData(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_import.import_flg_cmp.value'));
                            }
                            break;
                        case "note": // 備考
                            $ExportCsvRow->setData($remiseACImportInfo->getNote());
                            break;
                        default:
                            $ExportCsvRow->setData(null);
                            break;
                    }

                    $ExportCsvRow->pushData();
                }

                $this->fputcsv($ExportCsvRow->getRow());
            }

            $this->fclose();
        } catch (\Exception $e) {
            // ログ出力
            $this->logService->logError('Ac Result Auto Import', Array(
                trans('admin.common.system_error'),
                'ErrCode:' . $e->getCode(),
                'ErrMessage:' . $e->getMessage(),
                'ErrTrace:' . $e->getTraceAsString()
            ));

            try {
                $this->fclose();
            } catch (\Exception $e) {
            }
        }
        return ;
    }

    /**
     * CSV項目を取得
     *
     * @param mixed $CsvTypeId 識別子
     *
     * @return array CSV項目
     */
    private function getCsvType($CsvTypeId) {
        $CsvType = $this->csvTypeRepository->find($CsvTypeId);

        $criteria = [
            'CsvType' => $CsvType,
            'enabled' => true
       ];

        $orderBy = [
            'sort_no' => 'ASC'
        ];

        $Csvs = $this->csvRepository->findBy($criteria, $orderBy);

        return $Csvs;
    }

    /**
     * ファイルオープン
     *
     * @param string $filePath ファイルパス
     */
    private function fopen($filePath) {
        if (is_null($this->fp) || $this->closed) {
            $this->fp = fopen($filePath, 'w');
        }
    }

    /**
     * CSVファイル出力
     *
     * @param array $row
     */
    private function fputcsv($row) {
        if (is_null($this->convertEncodingCallBack)) {
            $this->convertEncodingCallBack = $this->getConvertEncodingCallback();
        }

        fputcsv($this->fp, array_map($this->convertEncodingCallBack, $row), $this->eccubeConfig['eccube_csv_export_separator']);
    }

    /**
     * ファイルクローズ
     *
     * @param string $filePath ファイルパス
     */
    private function fclose() {
        if (!$this->closed) {
            fclose($this->fp);
            $this->closed = true;
        }
    }

    /**
     * 文字エンコーディングの変換を行う
     *
     * @return string エンコードされた文字列
     */
    private function getConvertEncodingCallback() {
        $config = $this->eccubeConfig;

        return function ($value) use ($config) {
            return mb_convert_encoding(
                (string) $value, $config['eccube_csv_export_encoding'], 'UTF-8'
                );
        };
    }
}
