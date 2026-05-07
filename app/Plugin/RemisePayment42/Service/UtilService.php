<?php

namespace Plugin\RemisePayment42\Service;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\MailTemplate;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Entity\Payment;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\MailTemplateRepository;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\PaymentRepository;
use Eccube\Repository\CustomerRepository;
use Eccube\Repository\ShippingRepository;
use Eccube\Repository\DeliveryFeeRepository;
use Eccube\Entity\Customer;
use Eccube\Entity\MailHistory;
use Eccube\Repository\PluginRepository;
use Eccube\Entity\Plugin;
use Eccube\Service\OrderStateMachine;
use Eccube\Service\TaxRuleService;

use Plugin\RemisePayment42\Entity\RemiseMailTemplate;
use Plugin\RemisePayment42\Entity\RemisePayment;
use Plugin\RemisePayment42\Entity\OrderResult;
use Plugin\RemisePayment42\Entity\OrderResultCvs;
use Plugin\RemisePayment42\Repository\ConfigRepository;
use Plugin\RemisePayment42\Repository\RemiseMailTemplateRepository;
use Plugin\RemisePayment42\Repository\RemisePaymentRepository;
use Plugin\RemisePayment42\Repository\OrderResultCvsRepository;
use Plugin\RemisePayment42\Repository\OrderResultCardRepository;
use Plugin\RemisePayment42\Repository\OrderResultRepository;
use Plugin\RemisePayment42\Service\PaymentService;
use Plugin\RemisePayment42\Entity\Config;
use Plugin\RemisePayment42\Entity\ConfigInfo;
use Plugin\RemisePayment42\Entity\ConfigInfoExtset;
use Plugin\RemisePayment42\Repository\PayquickRepository;
use Plugin\RemisePayment42\Entity\Payquick;

/**
 * ユーティリティ
 */
class UtilService
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /** @var BaseInfo */
    protected $baseInfo;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var OrderStatusRepository
     */
    protected $orderStatusRepository;

    /**
     * @var ShippingRepository
     */
    protected $shippingRepository;

    /**
     * @var DeliveryFeeRepository
     */
    protected $deliveryFeeRepository;

    /**
     * @var TaxRuleService
     */
    protected $taxRuleService;

    /**
     * @var PaymentRepository
     */
    protected $paymentRepository;

    /**
     * @var MailTemplateRepository
     */
    protected $mailTemplateRepository;

    /**
     * @var CustomerRepository
     */
    protected $CustomerRepository;

    /**
     * @var PluginRepository
     */
    protected $pluginRepository;

    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var RemisePaymentRepository
     */
    protected $remisePaymentRepository;

    /**
     * @var RemiseMailTemplateRepository
     */
    protected $remiseMailTemplateRepository;

    /**
     * @var OrderResultRepository
     */
    protected $orderResultRepository;

    /**
     * @var OrderResultCardRepository
     */
    protected $orderResultCardRepository;

    /**
     * @var OrderResultCvsRepository
     */
    protected $orderResultCvsRepository;

    /**
     * @var PayquickRepository
     */
    protected $PayquickRepository;

    /**
     * @var OrderStateMachine
     */
    protected $orderStateMachine;

    /**
     * コンストラクタ
     *
     * @param TokenStorageInterface $tokenStorage
     * @param BaseInfoRepository $baseInfoRepository
     * @param EccubeConfig $eccubeConfig
     * @param OrderRepository $orderRepository
     * @param OrderStatusRepository $orderStatusRepository
     * @param ShippingRepository $shippingRepository
     * @param DeliveryFeeRepository $deliveryFeeRepository;
     * @param TaxRuleService $taxRuleService
     * @param PaymentRepository $paymentRepository
     * @param MailTemplateRepository $mailTemplateRepository
     * @param CustomerRepository $customerRepository
     * @param PluginRepository $pluginRepository
     * @param ConfigRepository $configRepository
     * @param RemisePaymentRepository $remisePaymentRepository
     * @param RemiseMailTemplateRepository $remiseMailTemplateRepository
     * @param OrderResultRepository $orderResultRepository
     * @param OrderResultCardRepository $orderResultCardRepository
     * @param OrderResultCvsRepository $orderResultCvsRepository
     * @param PayquickRepository $payquickRepository
     * @param OrderStateMachine $orderStateMachine
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        BaseInfoRepository $baseInfoRepository,
        EccubeConfig $eccubeConfig,
        OrderRepository $orderRepository,
        OrderStatusRepository $orderStatusRepository,
        ShippingRepository  $shippingRepository,
        DeliveryFeeRepository $deliveryFeeRepository,
        TaxRuleService $taxRuleService,
        PaymentRepository $paymentRepository,
        MailTemplateRepository $mailTemplateRepository,
        CustomerRepository $customerRepository,
        PluginRepository $pluginRepository,
        ConfigRepository $configRepository,
        RemisePaymentRepository $remisePaymentRepository,
        RemiseMailTemplateRepository $remiseMailTemplateRepository,
        OrderResultRepository $orderResultRepository,
        OrderResultCardRepository $orderResultCardRepository,
        OrderResultCvsRepository $orderResultCvsRepository,
        PayquickRepository $payquickRepository,
        OrderStateMachine $orderStateMachine
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->baseInfo = $baseInfoRepository->get();
        $this->eccubeConfig = $eccubeConfig;
        $this->orderRepository = $orderRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->shippingRepository = $shippingRepository;
        $this->deliveryFeeRepository = $deliveryFeeRepository;
        $this->taxRuleService = $taxRuleService;
        $this->paymentRepository = $paymentRepository;
        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->customerRepository = $customerRepository;
        $this->pluginRepository = $pluginRepository;
        $this->configRepository = $configRepository;
        $this->remisePaymentRepository = $remisePaymentRepository;
        $this->remiseMailTemplateRepository = $remiseMailTemplateRepository;
        $this->orderResultRepository = $orderResultRepository;
        $this->orderResultCardRepository = $orderResultCardRepository;
        $this->orderResultCvsRepository = $orderResultCvsRepository;
        $this->payquickRepository = $payquickRepository;
        $this->orderStateMachine = $orderStateMachine;
    }

    /**
     * 受注情報を取得
     *
     * @param $id
     *
     * @return $Order
     */
    public function getOrder($id)
    {
        $Order = $this->orderRepository->find($id);
        return $Order;
    }

    /**
     * 受注情報を取得(OrderIDとCustomerに紐づく)
     *
     * @param $id
     *
     * @return $Order
     */
    public function getOrderByOrderIdCustomer($id,$customer)
    {
        $Order = $this->orderRepository->findOneBy(['id'=>$id,'Customer'=>$customer]);
        return $Order;
    }

    /**
     * OrderNoに紐づく受注情報を取得
     *
     * @param $orderNo
     *
     * @return $Order
     */
    public function getOrderByOrderNo($orderNo)
    {
        // 受注情報の取得
        $Order = $this->orderRepository->findOneBy([
            'order_no' => $orderNo
        ]);

        return $Order;
    }

    /**
     * 決済処理中の受注情報を取得
     *
     * @param $orderNo
     *
     * @return $Order
     */
    public function getOrderByPending($orderNo)
    {
        // 決済処理中のEC-CUBE受注ステータスを取得
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PENDING);

        // 受注情報の取得
        $Order = $this->orderRepository->findOneBy([
            'order_no' => $orderNo,
            'OrderStatus' => $OrderStatus
        ]);

        return $Order;
    }

    /**
     * 新規受付の受注情報を取得
     *
     * @param $orderNo
     *
     * @return $Order
     */
    public function getOrderByNew($orderNo)
    {
        // 新規受付のEC-CUBE受注ステータスを取得
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::NEW);

        // 受注情報の取得
        $Order = $this->orderRepository->findOneBy([
            'order_no' => $orderNo,
            'OrderStatus' => $OrderStatus
        ]);

        return $Order;
    }

    /**
     * 受注情報のステータスを「購入処理中」に変更
     *
     * @param  $Order
     *
     * @return  $Order
     */
    public function setOrderStatusByProcessing($Order)
    {
        // 購入処理中のEC-CUBE受注ステータスを取得
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PROCESSING);

        // 受注情報のステータスを変更
        $Order->setOrderStatus($OrderStatus);

        return $Order;
    }

    /**
     * 受注情報のステータスを「決済処理中」に変更
     *
     * @param  $Order
     *
     * @return  $Order
     */
    public function setOrderStatusByPending($Order)
    {
        // 決済処理中のEC-CUBE受注ステータスを取得
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PENDING);

        // 受注情報のステータスを変更
        $Order->setOrderStatus($OrderStatus);

        return $Order;
    }

    /**
     * 受注情報のステータスを「新規受付」に変更
     *
     * @param  $Order
     *
     * @return  $Order
     */
    public function setOrderStatusByNew($Order)
    {
        // 新規受付のEC-CUBE受注ステータスを取得
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::NEW);

        // 受注情報のステータスを変更
        $Order->setOrderStatus($OrderStatus);

        return $Order;
    }

    /**
     * 受注情報のステータスを「入金済み」に変更
     *
     * @param  $Order
     *
     * @return  $Order
     */
    public function setOrderStatusByPaid($Order)
    {
        // 入金済みのEC-CUBE受注ステータスを取得
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PAID);

        // 受注情報のステータスを変更
        $this->orderStateMachine->apply($Order, $OrderStatus);

        return $Order;
    }

    /**
     * 受注情報のステータスを「注文取消し」に変更
     *
     * @param  $Order
     *
     * @return  $Order
     */
    public function setOrderStatusByCancel($Order)
    {
        // 注文取消のEC-CUBE受注ステータスを取得
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::CANCEL);

        // 受注情報のステータスを変更
        $this->orderStateMachine->apply($Order, $OrderStatus);

        return $Order;
    }

    /**
     * 受注情報のステータスを「返品」に変更
     *
     * @param  $Order
     *
     * @return  $Order
     */
    public function setOrderStatusByReturned($Order)
    {
        // 返品のEC-CUBE受注ステータスを取得
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::RETURNED);

        // 受注情報のステータスを変更
        $this->orderStateMachine->apply($Order, $OrderStatus);

        return $Order;
    }

    /**
     * EC-CUBE支払方法の取得
     *
     * @param  $id
     *
     * @return  $Payment
     */
    public function getPayment($id)
    {
        $Payment = $this->paymentRepository->find($id);
        return $Payment;
    }

    /**
     * EC-CUBE支払方法の新規作成
     *
     * @param  string  $method  支払方法名
     *
     * @return  $Payment
     */
    public function createPayment($method)
    {
        $Payment = $this->paymentRepository->findOneBy([], ['sort_no' => 'DESC']);

        $sortNo = 1;
        if ($Payment) {
            $sortNo = $Payment->getSortNo() + 1;
        }

        $Payment = new Payment();
        $Payment->setMethod($method);
        $Payment->setCharge(0);
        $Payment->setSortNo($sortNo);
        $Payment->setFixed(true);
        $Payment->setRuleMin(0);
        $Payment->setMethodClass(PaymentService::class);
        $Payment->setVisible(true);
        $Payment->setCreateDate(new \DateTime());
        $Payment->setUpdateDate(new \DateTime());

        return $Payment;
    }

    /**
     * EC-CUBEメールテンプレートの取得
     *
     * @param  $id
     *
     * @return  $MailTemplate
     */
    public function getMailTemplate($id)
    {
        $MailTemplate = $this->mailTemplateRepository->find($id);
        return $MailTemplate;
    }

//     /**
//      * EC-CUBEメールテンプレートの新規作成
//      *
//      * @return  $MailTemplate
//      */
//     public function createMailTemplate($kind)
//     {
//         $MailTemplate = new MailTemplate();

//         switch ($kind){
//             case trans('remise_payment4.common.label.mail_template.kind.acpt'):
//                 // 入金お知らせメール（REMISEマルチ決済）
//                 $MailTemplate->setName(trans('remise_payment4.common.label.mail_template.name.acpt'));
//                 $MailTemplate->setFileName('RemisePayment42/Resource/template/mail/order_acpt.twig');
//                 $MailTemplate->setMailSubject(trans('remise_payment4.common.label.mail_template.subject.acpt'));
//                 $MailTemplate->setCreateDate(new \DateTime());
//                 $MailTemplate->setUpdateDate(new \DateTime());
//                 break;
//             case trans('remise_payment4.common.label.mail_template.kind.ac_order'):
//                 // 定期購買注文受付メールREMISE定期購買）
//                 $MailTemplate->setName(trans('remise_payment4.common.label.mail_template.name.ac_order'));
//                 $MailTemplate->setFileName('RemisePayment42/Resource/template/mail/ac_order.twig');
//                 $MailTemplate->setMailSubject(trans('remise_payment4.common.label.mail_template.subject.ac_order'));
//                 $MailTemplate->setCreateDate(new \DateTime());
//                 $MailTemplate->setUpdateDate(new \DateTime());
//                 break;
//             case trans('remise_payment4.common.label.mail_template.kind.mypage.card_update'):
//                 // 定期購買カード情報更新通知メール（REMISE定期購買）
//                 $MailTemplate->setName(trans('remise_payment4.common.label.mail_template.name.mypage.card_update'));
//                 $MailTemplate->setFileName('RemisePayment42/Resource/template/mail/ac_card_update_complete.twig');
//                 $MailTemplate->setMailSubject(trans('remise_payment4.common.label.mail_template.subject.mypage.card_update'));
//                 $MailTemplate->setCreateDate(new \DateTime());
//                 $MailTemplate->setUpdateDate(new \DateTime());
//                 break;
//             case  trans('remise_payment4.common.label.mail_template.kind.mypage.cancel'):
//                 // 定期購買解約通知メール（REMISE定期購買）
//                 $MailTemplate->setName(trans('remise_payment4.common.label.mail_template.name.mypage.cancel'));
//                 $MailTemplate->setFileName('RemisePayment42/Resource/template/mail/ac_cancel_complete.twig');
//                 $MailTemplate->setMailSubject(trans('remise_payment4.common.label.mail_template.subject.mypage.cancel'));
//                 $MailTemplate->setCreateDate(new \DateTime());
//                 $MailTemplate->setUpdateDate(new \DateTime());
//                 break;
//             case trans('remise_payment4.common.label.mail_template.kind.mypage.skip'):
//                 // 定期購買スキップ通知メール（REMISE定期購買）
//                 $MailTemplate->setName(trans('remise_payment4.common.label.mail_template.name.mypage.skip'));
//                 $MailTemplate->setFileName('RemisePayment42/Resource/template/mail/ac_skip_complete.twig');
//                 $MailTemplate->setMailSubject(trans('remise_payment4.common.label.mail_template.subject.mypage.skip'));
//                 $MailTemplate->setCreateDate(new \DateTime());
//                 $MailTemplate->setUpdateDate(new \DateTime());
//                 break;
//             case trans('remise_payment4.common.label.mail_template.kind.result_import.success'):
//                 // 定期購買結果取込通知メール（REMISE定期購買）
//                 $MailTemplate->setName(trans('remise_payment4.common.label.mail_template.name.result_import.success'));
//                 $MailTemplate->setFileName('RemisePayment42/Resource/template/mail/ac_result_import_complete.twig');
//                 $MailTemplate->setMailSubject(trans('remise_payment4.common.label.mail_template.subject.result_import.success'));
//                 $MailTemplate->setCreateDate(new \DateTime());
//                 $MailTemplate->setUpdateDate(new \DateTime());
//                 break;
//             case trans('remise_payment4.common.label.mail_template.kind.result_import.error'):
//                 // 定期購買結果取込エラー通知メール（REMISE定期購買）
//                 $MailTemplate->setName(trans('remise_payment4.common.label.mail_template.name.result_import.error'));
//                 $MailTemplate->setFileName('RemisePayment42/Resource/template/mail/ac_result_import_error.twig');
//                 $MailTemplate->setMailSubject(trans('remise_payment4.common.label.mail_template.subject.result_import.error'));
//                 $MailTemplate->setCreateDate(new \DateTime());
//                 $MailTemplate->setUpdateDate(new \DateTime());
//                 break;
//         }

//         return $MailTemplate;
//     }

    /**
     * EC-CUBE会員情報の取得
     *
     * @param  $id
     *
     * @return  $Customer
     */
    public function getCustomer($id)
    {
        $Customer = $this->customerRepository->find($id);
        return $Customer;
    }

    /**
     * ログイン中のEC-CUBE会員情報の取得
     *
     * @param  $id
     *
     * @return  $Customer
     */
    public function getLoginCustomer()
    {
        $Customer = $this->getUser();
        return $Customer;
    }

    protected function getUser()
    {
        if (null === $token = $this->tokenStorage->getToken()) {
            return;
        }

        if (!is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return;
        }

        return $user;
    }

    /**
     * EC-CUBEプラグイン情報の取得
     *
     * @return  $Plugin
     */
    public function getPlugin()
    {
        $Plugin = $this->pluginRepository->findByCode(trans('remise_payment4.common.label.plugin_code'));
        return $Plugin;
    }

    /**
     * プラグイン設定情報の取得
     *
     * @return  $Config
     */
    public function getConfig()
    {
        $Config = $this->configRepository->findOneByCode(trans('remise_payment4.common.label.plugin_code'));
        return $Config;
    }

    /**
     * プラグイン設定情報の取得
     *
     * @return  $ConfigInfo
     */
    public function getConfigInfo()
    {
        $Config = $this->getConfig();

        if (!$Config)
        {
            return null;
        }

        $ConfigInfo = $Config->getUnserializeInfo();
        return $ConfigInfo;
    }

    /**
     * プラグイン設定情報の新規作成
     *
     * @return  $Config
     */
    public function createConfig()
    {
        $Config = new Config();
        $Config->setCode(trans('remise_payment4.common.label.plugin_code'));
        $Config->setName(trans('remise_payment4.common.label.plugin_name'));
        $Config->setCreateDate(new \DateTime());
        $Config->setUpdateDate(new \DateTime());
        return $Config;
    }

    /**
     * プラグイン拡張セット設定情報の取得
     *
     * @return  $Config
     */
    public function getConfigExtset()
    {
        $Config = $this->configRepository->findOneByCode(trans('remise_payment4.extset.common.label.plugin_code'));
        return $Config;
    }

    /**
     * プラグイン設定情報の取得
     *
     * @return  $ConfigInfo
     */
    public function getConfigInfoExtset()
    {
        $Config = $this->getConfigExtset();

        if (!$Config)
        {
            return null;
        }

        $ConfigInfo = $Config->getUnserializeInfoExtset();
        return $ConfigInfo;
    }

    /**
     * プラグイン拡張セット設定情報の新規作成
     *
     * @return  $Config
     */
    public function createConfigExtset()
    {
        $Config = new Config();
        $Config->setCode(trans('remise_payment4.extset.common.label.plugin_code'));
        $Config->setName(trans('remise_payment4.extset.common.label.plugin_name'));
        $Config->setCreateDate(new \DateTime());
        $Config->setUpdateDate(new \DateTime());
        return $Config;
    }

    /**
     * ルミーズ支払方法の取得
     *
     * @param  $id
     *
     * @return  $RemisePayment
     */
    public function getRemisePayment($id)
    {
        if($id)
        {
            $RemisePayment = $this->remisePaymentRepository->find($id);
        }else{
            return null;
        }
        return $RemisePayment;
    }

    /**
     * ルミーズ支払方法の削除
     *
     * @param  $RemisePayment
     */
    public function deleteRemisePayment($RemisePayment)
    {
        $this->remisePaymentRepository->delete($RemisePayment);
    }

    /**
     * ルミーズ支払方法の取得
     *
     * @param  $kind
     *
     * @return  $RemisePayment
     */
    public function getRemisePaymentByKind($kind)
    {
        $RemisePayment = $this->remisePaymentRepository->findByKind($kind);
        return $RemisePayment;
    }

    /**
     * ルミーズメールテンプレートの取得
     *
     * @param  $kind
     *
     * @return  $RemiseMailTemplate
     */
    public function getRemiseMailTemplateByKind($kind)
    {
        $RemiseMailTemplate = $this->remiseMailTemplateRepository->findOneByKind($kind);
        return $RemiseMailTemplate;
    }

    /**
     * ルミーズ決済履歴の取得
     *
     * @param  $id
     *
     * @return  $OrderResult
     */
    public function getOrderResult($id)
    {
        $OrderResult = $this->orderResultRepository->find($id);
        return $OrderResult;
    }

    /**
     * ルミーズ決済履歴の新規作成
     *
     * @param  $Order
     *
     * @return  $OrderResult
     */
    public function createOrderResult($Order)
    {
        $OrderResult = $this->orderResultRepository->findOrCreate($Order);
        return $OrderResult;
    }

    /**
     * ルミーズカード決済履歴詳細の取得
     *
     * @param  $id
     *
     * @return  $OrderResultCard
     */
    public function getOrderResultCard($id)
    {
        $OrderResultCard = $this->orderResultCardRepository->find($id);
        return $OrderResultCard;
    }

    /**
     * ルミーズカード決済履歴詳細の取得(受注未確定)
     *
     * @param  $ids
     *
     * @return  $OrderResultCards
     */
    public function getOrderResultCardForNotCompleted($ids)
    {
        $OrderResultCards = $this->orderResultCardRepository->getNotCompletedOrder($ids);
        return $OrderResultCards;
    }

    /**
     * ルミーズカード決済履歴詳細の取得(課金失敗)
     *
     * @param  $ids
     *
     * @return  $OrderResultCards
     */
    public function getOrderResultCardForAcFailed($ids)
    {
        $OrderResultCards = $this->orderResultCardRepository->getAcFailedOrder($ids);
        return $OrderResultCards;
    }

    /**
     * ルミーズカード決済履歴詳細の取得
     *
     * @param  $ids
     * @param  $states
     *
     * @return  $OrderResultCards
     */
    public function getOrderResultCardsOrderIdsStates($ids,$states=Array())
    {
        $OrderResultCards = $this->orderResultCardRepository->getOrderResultCardsOrderIdsStates($ids,$states);
        return $OrderResultCards;
    }

    /**
     * ルミーズカード決済履歴詳細の新規作成
     *
     * @param  $Order
     *
     * @return  $OrderResultCard
     */
    public function createOrderResultCard($Order)
    {
        $OrderResultCard = $this->orderResultCardRepository->findOrCreate($Order);
        return $OrderResultCard;
    }

    /**
     * ルミーズカード決済履歴詳細の削除
     *
     * @param  $OrderResultCard
     */
    public function deleteOrderResultCard($OrderResultCard)
    {
        $this->orderResultCardRepository->delete($OrderResultCard);
    }

    /**
     * ルミーズマルチ決済履歴詳細の取得
     *
     * @param  $id
     *
     * @return  $OrderResultCvs
     */
    public function getOrderResultCvs($id)
    {
        $OrderResultCvs = $this->orderResultCvsRepository->find($id);
        return $OrderResultCvs;
    }

    /**
     * ルミーズマルチ決済履歴詳細の新規作成
     *
     * @param  $Order
     *
     * @return  $OrderResultCvs
     */
    public function createOrderResultCvs($Order)
    {
        $OrderResultCvs = $this->orderResultCvsRepository->findOrCreate($Order);
        return $OrderResultCvs;
    }

    /**
     * ルミーズマルチ決済履歴詳細の削除
     *
     * @param  $OrderResultCvs
     */
    public function deleteOrderResultCvs($OrderResultCvs)
    {
        $this->orderResultCvsRepository->delete($OrderResultCvs);
    }

    /**
     * ペイクイック情報の取得
     *
     * @param  $id
     * @param  $customerId
     *
     * @return  $Payquick
     */
    public function getPayquickById($id, $customerId)
    {
        $Payquick = $this->payquickRepository->findOneBy(array(
            'id' => $id,
            'customer_id' => $customerId,
        ));
        return $Payquick;
    }

    /**
     * ペイクイック情報の取得
     *
     * @param  $Customer
     *
     * @return  $Payquick
     */
    public function getPayquickByCustomer($Customer)
    {
        $Payquick = $this->payquickRepository->findByCustomer($Customer);
        return $Payquick;
    }

    /**
     * ペイクイック情報の新規作成
     *
     * @param  $Customer
     *
     * @return  $Payquick
     */
    public function createPayquick($Customer)
    {
        $Payquick = $this->payquickRepository->findOrCreate($Customer);
        return $Payquick;
    }

    /**
     * ペイクイック情報の削除
     *
     * @param  $Payquick
     */
    public function deletePayquick($Payquick)
    {
        $this->payquickRepository->delete($Payquick);
    }

    /**
     * マルチ決済支払情報を取得
     *
     * @param  $Order  決済履歴
     * @param  $OrderResultCvs  マルチ決済履歴詳細
     *
     * @return $cvsInfo
     */
    public function getCvsInfo($Order, $OrderResultCvs)
    {
        $cvsInfo = '';
        if (!$Order || !$OrderResultCvs)
        {
            return $cvsInfo;
        }

        // EC-CUBE支払方法の取得
        $Payment = $Order->getPayment();

        // 支払先
        $key = 'remise_payment4.common.label.pay_csv.' . $OrderResultCvs->getPayCsv();
        $cvsName = trans($key);
        if ($cvsName == $key) $cvsName = '';

        // 支払期限
        $cvsPaydate = '';
        if ($OrderResultCvs->getPayDate() != null)
        {
            $cvsPaydate = $OrderResultCvs->getPayDateStr('Y年m月d日');
        }

        // 支払のご案内
        $cvsOthers = array();
        $cvsMsg = '';
        switch ($OrderResultCvs->getPayCsv())
        {
            case 'D001': // セブンイレブン
                $cvsOthers[] = array(trans('remise_payment4.common.label.payment_no'), $OrderResultCvs->getPayNo1());
                $cvsOthers[] = array(trans('remise_payment4.common.label.payment_url'), $OrderResultCvs->getPayNo2());
                $cvsMsg .= trans('remise_payment4.common.label.message_seveneleven');
                break;

            case "D002": // ローソン
            case "D005": // ミニストップ
                $cvsOthers[] = array(trans('remise_payment4.common.label.receipt_no'), substr($OrderResultCvs->getPayNo1(), 0, 8));
                $cvsOthers[] = array(trans('remise_payment4.common.label.confirm_no'), substr($OrderResultCvs->getPayNo1(), 8, 9));
                $cvsOthers[] = array(trans('remise_payment4.common.label.payment_url'), $OrderResultCvs->getPayNo2());
                $cvsMsg .= trans('remise_payment4.common.label.message_lawson');
                break;

            case "D015": // セイコーマート
                $cvsOthers[] = array(trans('remise_payment4.common.label.receipt_no'), $OrderResultCvs->getPayNo1());
                $cvsOthers[] = array(trans('remise_payment4.common.label.regist_telno'), $Order->getPhoneNumber());
                $cvsOthers[] = array(trans('remise_payment4.common.label.payment_url'), $OrderResultCvs->getPayNo2());
                $cvsMsg .= trans('remise_payment4.common.label.message_seikomart');
                break;

            case "D405": // ペイジー
                if (strpos($OrderResultCvs->getPayNo1(), '-') !== false) {
                    $parts = explode('-', $OrderResultCvs->getPayNo1());
                    $cvsOthers[] = array(trans('remise_payment4.common.label.receiving_no'), trans('remise_payment4.common.label.receiving_no.D405'));
                    $cvsOthers[] = array(trans('remise_payment4.common.label.customer_no'), $parts[0] ?? '');
                    $cvsOthers[] = array(trans('remise_payment4.common.label.confirm_no'), $parts[1] ?? '');
                    $cvsOthers[] = array(trans('remise_payment4.common.label.payment_url'), $OrderResultCvs->getPayNo2());
                } else {
                    $cvsOthers[] = array(trans('remise_payment4.common.label.confirm_no'), $OrderResultCvs->getPayNo1());
                }
                $cvsMsg .= trans('remise_payment4.common.label.message_payeasy');
                break;

            case "D003": // サンクス
            case "D004": // サークルＫ
                $cvsOthers[] = array(trans('remise_payment4.common.label.receipt_no'), $OrderResultCvs->getPayNo1());
                $cvsOthers[] = array(trans('remise_payment4.common.label.regist_telno'), $Order->getPhoneNumber());
                $cvsOthers[] = array(trans('remise_payment4.common.label.payment_url'), $OrderResultCvs->getPayNo2());
                $cvsMsg .= trans('remise_payment4.common.label.message_circlek');
                break;

            case "D010": // デイリーヤマザキ
            case "D011": // ヤマザキデイリーストア
                $cvsOthers[] = array(trans('remise_payment4.common.label.online_no'), $OrderResultCvs->getPayNo1());
                $cvsOthers[] = array(trans('remise_payment4.common.label.payment_url'), $OrderResultCvs->getPayNo2());
                $cvsMsg .= trans('remise_payment4.common.label.message_yamazaki');
                break;

            case "D030": // ファミリーマート
                $cvsOthers[] = array(trans('remise_payment4.common.label.company_code'), $OrderResultCvs->getPayNo1());
                $cvsOthers[] = array(trans('remise_payment4.common.label.order_no'), $OrderResultCvs->getPayNo2());
                $cvsOthers[] = array(trans('remise_payment4.common.label.payment_url'), trans('remise_payment4.common.label.dsk_url'));
                $cvsMsg .= trans('remise_payment4.common.label.message_familymart');
                break;

            case "P901": // コンビニ払込票
            case "P902": // コンビニ払込票（郵便振替対応）
            case "P903": // コンビニ払込票（ハガキ単色）
            case "P904": // コンビニ払込票（ハガキ青）
            case "P905": // コンビニ払込票（ハガキ緑）
            case "P906": // コンビニ払込票（ハガキ赤）
                $cvsOthers[] = array(trans('remise_payment4.common.label.receipt_no'), $OrderResultCvs->getPayNo1());
                $cvsOthers[] = array(trans('remise_payment4.common.label.payment_url'), trans('remise_payment4.common.label.dsk_url'));
                $cvsMsg .= trans('remise_payment4.common.label.message_paperpay');
                break;

            default: // 他
                $cvsOthers[] = array(trans('remise_payment4.common.label.receipt_no'), $OrderResultCvs->getPayNo1());
                $cvsOthers[] = array(trans('remise_payment4.common.label.payment_url'), $OrderResultCvs->getPayNo2());
                $cvsMsg .= trans('remise_payment4.common.label.message_other');
                break;
        }

        $cvsInfo = [
            'title'         => $Payment->getMethod(),
            'cvs_name'      => $cvsName,
            'cvs_paydate'   => $cvsPaydate,
            'cvs_others'    => $cvsOthers,
            'cvs_msg'       => $cvsMsg,
        ];
        return $cvsInfo;
    }

    /**
     * 配列から値の取得
     *
     * @param  $requestData
     * @param  $key
     *
     * @return $value
     */
    public function getValue($requestData, $key)
    {
        if (empty($requestData) || $key === "")
        {
            return '';
        }
        if (!isset($requestData[$key]) || $requestData[$key] === "")
        {
            return '';
        }
        return $requestData[$key];
    }

    /**
     * 配列から値（日付）の取得
     *
     * @param  $requestData
     * @param  $key
     *
     * @return $value
     */
    public function getDate($requestData, $key)
    {
        if (empty($requestData) || $key === "")
        {
            return null;
        }
        if (!isset($requestData[$key]) || $requestData[$key] === "")
        {
            return null;
        }
        return new \DateTime($requestData[$key]);
    }

    /**
     * 拡張セット用エラーメッセージ取得
     *
     * @param  $job
     * @param  $code
     *
     * @return $dispErrMsg
     */
    public function getExtsetErrorMassage($job,$code)
    {
        // エラーメッセージ取得
        $messageKey = 'remise_payment4.front.text.r_code.' . $code;
        $errMsg = trans($messageKey);
        if ($errMsg == $messageKey)
        {
            $head = substr($code, 0, 3);
            $tail = substr($code, -1);
            if ($head == "8:1" || $head == "8:2" || $head == "8:3")
            {
                if ($tail == "8") {
                    $messageKey = 'remise_payment4.front.text.r_code.8:x008';
                } else {
                    $messageKey = 'remise_payment4.front.text.r_code.' . $head . 'xxx';
                }
                $errMsg = trans($messageKey);
            }
        }
        if ($errMsg == $messageKey)
        {
            $errMsg = trans('remise_payment4.extset.admin_order_edit.text.failed.'.mb_strtolower($job));
        }

        $dispErrMsg = trans('remise_payment4.front.text.payment.error') . ":" . $errMsg . "(" . $code . ")";

        return $dispErrMsg;
    }

    /**
     * 定期購買用エラーメッセージ取得
     *
     * @param  $job
     * @param  $code
     *
     * @return $dispErrMsg
     */
    public function getAcErrorMassage($code)
    {
        // エラーメッセージ取得
        $messageKey = 'remise_payment4.front.text.r_code.' . $code;
        $errMsg = trans($messageKey);
        if ($errMsg == $messageKey)
        {
            $head = substr($code, 0, 3);
            $tail = substr($code, -1);
            if ($head == "8:1" || $head == "8:2" || $head == "8:3")
            {
                if ($tail == "8") {
                    $messageKey = 'remise_payment4.front.text.r_code.8:x008';
                } else {
                    $messageKey = 'remise_payment4.front.text.r_code.' . $head . 'xxx';
                }
                $errMsg = trans($messageKey);
            }
        }
        if ($errMsg == $messageKey)
        {
            $errMsg = trans('remise_payment4.extset.admin_order_edit.text.failed.'.mb_strtolower($job));
        }

        $dispErrMsg = trans('remise_payment4.front.text.payment.error') . ":" . $errMsg . "(" . $code . ")";

        return $dispErrMsg;
    }

    /**
     * パスワード暗号化
     *
     * @param
     *            $str
     *
     * @return $base64EncryptedStr
     */
    public function getEncryptedStr($str)
    {
        // 暗号化用のメソッドを指定
        $method = 'AES-256-CBC';

        // 暗号化・復元用のソルト
        $passphrase = 'REMISE_AUTOCHARGE';

        // iv生成
        $iv = '1234567890123456';

        // 暗号化
        $encryptedStr = openssl_encrypt($str, $method, $passphrase, 0, $iv);

        return $encryptedStr;
    }

    /**
     * パスワード複合化
     *
     * @param
     *            $str
     *
     * @return $encryptedStr
     */
    public function getDecryptedStr($str)
    {
        // 暗号化用のメソッドを指定
        $method = 'AES-256-CBC';

        // 暗号化したときのソルトを指定。これは漏れたらダメなやつです
        $passphrase = 'REMISE_AUTOCHARGE';

        // iv生成
        $iv = '1234567890123456';

        // 複合化
        $decryptedStr = openssl_decrypt($str, $method, $passphrase, 0, $iv);

        return $decryptedStr;
    }

    /**
     * 税込金額取得
     *
     * @param $price
     * @param $product
     * @param $productClass
     * @param $pref
     * @param $country
     *
     * @return $encryptedStr
     */
    public function getTax($price, $product = null, $productClass = null, $pref = null, $country = null)
    {
        // 税込金額を取得
        $tax = $this->taxRuleService->getTax($price, $product, $productClass, $pref, $country);
        return $tax;
    }

    /**
     * 送料取得
     *
     * @param
     *            $str
     *
     * @return $encryptedStr
     */
    public function getDeliveryFee($quantity,$priceIncTaxTotal,$orderId,$productClass){
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
                if ($productClass->getDeliveryFee() != null) {
                    $deliveryFeeIndividual = $productClass->getDeliveryFee();
                    // ECCUBEでは商品ごとに設定しても個別の送料+共通の送料が合計送料として請求する仕様となっている。
                    // $isDeliveryFee = true;
                }
            }
        }
        if (! $isDeliveryFee) {
            // 配送方法の送料を取得
            $Shipping = $this->shippingRepository->findOneBy([
                'Order' => $orderId
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

        return $deliveryFee;

    }

    /**
     * ポイント設定
     *
     * @param
     *
     * @return
     */
    public function setPoint(Order $Order){

        // ポイント付与が有効化を確認
        if (!$this->baseInfo->isOptionPoint()) {
            return;
        }

        // 基本設定のポイントレートを取得
        $basicPointRate = $this->baseInfo->getBasicPointRate();

        // 受注明細を取得
        $OrderItems = $Order->getOrderItems();

        $totalPoint = 0;

        // 明細ごとのポイントを集計
        foreach($OrderItems as $OrderItem){

            $pointRate = $OrderItem->getPointRate();
            if ($pointRate === null) {
                $pointRate = $basicPointRate;
            }

            $point = 0;
            if ($OrderItem->isPoint()) {
                $point = round($OrderItem->getPrice() * ($pointRate / 100)) * $OrderItem->getQuantity();
                // Only calc point on product
            } elseif ($OrderItem->isProduct()) {
                // ポイント = 単価 * ポイント付与率 * 数量
                $point = round($OrderItem->getPrice() * ($pointRate / 100)) * $OrderItem->getQuantity();
            }

            $totalPoint = $totalPoint + $point;

        }

        $Order->setAddPoint($totalPoint);

        return;
    }
}
