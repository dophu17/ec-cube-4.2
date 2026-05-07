<?php

namespace Plugin\RemisePayment42;

use Eccube\EntityPayment;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Entity\Payment;
use Eccube\Event\EccubeEvents;
use Eccube\Event\TemplateEvent;
use Eccube\Event\EventArgs;
use Eccube\Repository\CustomerRepository;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


use Plugin\RemisePayment42\Entity\Config;
use Plugin\RemisePayment42\Entity\ConfigInfo;
use Plugin\RemisePayment42\Entity\OrderResult;
use Plugin\RemisePayment42\Entity\OrderResultCard;
use Plugin\RemisePayment42\Entity\OrderResultCvs;
use Plugin\RemisePayment42\Entity\Payquick;
use Plugin\RemisePayment42\Entity\RemisePayment;
use Plugin\RemisePayment42\Service\UtilService;
use Plugin\RemisePayment42\Service\AcService;
use Plugin\RemisePayment42\Service\AcApiService;
use Plugin\RemisePayment42\Repository\RemisePaymentRepository;
use Plugin\RemisePayment42\Repository\RemiseSaleTypeRepository;
use Plugin\RemisePayment42\Repository\RemiseACTypeRepository;
use Plugin\RemisePayment42\Repository\RemiseACMemberRepository;

/**
 * イベント処理用
 */
class RemiseEvent implements EventSubscriberInterface
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var UtilService
     */
    protected $utilService;

    /**
     * @var AcService
     */
    protected $acService;

    /**
     * @var AcApiService
     */
    protected $acApiService;

    /**
     * @var RemisePaymentRepository
     */
    protected $remisePaymentRepository;

    /**
     *
     * @var RemiseSaleTypeRepository
     */
    protected $remiseSaleTypeRepository;

    /**
     *
     * @var RemiseACTypeRepository
     */
    protected $remiseACTypeRepository;

    /**
     *
     * @var RemiseACMemberRepository
     */
    protected $remiseACMemberRepository;

    /**
     * コンストラクタ
     *
     * @param RequestStack $requestStack
     * @param CustomerRepository $customerRepository
     * @param UtilService $utilService
     * @param AcService $acService
     * @param AcApiService $acApiService
     * @param RemisePaymentRepository $remisePaymentRepository
     * @param RemiseSaleTypeRepository $remiseSaleTypeRepository
     * @param RemiseACTypeRepository $remiseACTypeRepository
     * @param RemiseACMemberRepository $remiseACMemberRepository
     */
    public function __construct(
        RequestStack $requestStack,
        CustomerRepository $customerRepository,
        UtilService $utilService,
        AcService $acService,
        AcApiService $acApiService,
        RemisePaymentRepository $remisePaymentRepository,
        RemiseSaleTypeRepository $remiseSaleTypeRepository,
        RemiseACTypeRepository $remiseACTypeRepository,
        RemiseACMemberRepository $remiseACMemberRepository
    ) {
        $this->session = $requestStack->getSession();
        $this->customerRepository = $customerRepository;
        $this->utilService = $utilService;
        $this->acService = $acService;
        $this->acApiService = $acApiService;
        $this->remisePaymentRepository = $remisePaymentRepository;
        $this->remiseSaleTypeRepository = $remiseSaleTypeRepository;
        $this->remiseACTypeRepository = $remiseACTypeRepository;
        $this->remiseACMemberRepository = $remiseACMemberRepository;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            // 管理画面　支払方法登録・編集画面
            '@admin/Setting/Shop/payment_edit.twig' => 'onAdminPaymentEditTwig',
            // 管理画面　受注一覧
            '@admin/Order/index.twig' => 'onAdminOrderIndexTwig',
            // 管理画面　受注一覧(検索実行)
            EccubeEvents::ADMIN_ORDER_INDEX_SEARCH => 'onAdminOrderIndexSearch',
            // 管理画面　受注登録
            '@admin/Order/edit.twig' => 'onAdminOrderEditTwig',
            // 管理画面　会員一覧
            '@admin/Customer/index.twig' => 'onAdminCustomerIndexTwig',
            // 管理画面　会員登録・編集
            '@admin/Customer/edit.twig' => 'onAdminCustomerEditTwig',
            // 管理画面　商品登録
            '@admin/Product/product.twig' => 'onAdminProductProductTwig',
            // 管理画面　商品登録完了イベント
            EccubeEvents::ADMIN_PRODUCT_EDIT_COMPLETE => 'onAdminProductEditComplete',
            // 管理画面　商品規格
            '@admin/Product/product_class.twig' => 'onAdminProductProductClassTwig',
            // 管理画面　配送方法設定
            '@admin/Setting/Shop/delivery_edit.twig' => 'onAdminSettingSystemDeliveryEditTwig',
            // 管理画面　マスターデータ管理
            '@admin/Setting/System/masterdata.twig' => 'onAdminSettingSystemMasterdataTwig',

            // ショッピングカート
            'Cart/index.twig' => 'onCartIndexTwig',
            // ご注文手続き
            'Shopping/index.twig' => 'onShoppingIndexTwig',
            // ご注文内容のご確認
            'Shopping/confirm.twig' => 'onShoppingConfirmTwig',
            // ご注文完了
            'Shopping/complete.twig' => 'onShoppingCompleteTwig',
            // マイページ/ご注文履歴一覧
            'Mypage/index.twig' => 'onMypageIndexTwig',
            // マイページ/ご注文履歴詳細
            'Mypage/history.twig' => 'onMypageHistoryTwig',
            // マイページ/会員情報編集
            'Mypage/change.twig' => 'onMypageChangeTwig',
            // マイページ/退会
            'Mypage/withdraw.twig' => 'onMypageWithdrawTwig',
        ];
    }

    /**
     * 管理画面　商品登録画面
     *
     * @param $event
     */
    public function onAdminProductProductTwig(TemplateEvent $event)
    {
        // プラグイン設定情報の取得
        $Config = $this->utilService->getConfig();
        if (!$Config) {
            return;
        }

        // 設定情報の取得
        $ConfigInfo = $Config->getUnserializeInfo();
        if (!$ConfigInfo) {
            return;
        }

        // 定期購買が無効の場合
        if(!$ConfigInfo->useOptionAC())
        {
            return;
        }

        // ルミーズ販売種別（定期購買）既存レコード確認
        $RemiseSaleTypes = $this->remiseSaleTypeRepository->findBy([
            'sale_type' => trans('remise_payment4.ac.sale_type')
        ], [
            'id' => 'DESC'
        ]);

        if (! $RemiseSaleTypes) {
            return;
        }

        // ルミーズ定期購買種別
        $remiseACTypes = $this->remiseACTypeRepository->findBy([
            'visible' => '1'
        ], [
            'sort_no' => 'DESC'
        ]);

        if (! $remiseACTypes) {
            return;
        }

        // 商品情報を取得
        $Product = $event->getParameter('Product');
        if ($Product && $Product->getId() != null && $Product->getId() != "") {
            // 規格有りの場合、出力しない
            $has_class = $Product->hasProductClass();
            if ($has_class) {
                return;
            }
        }

        $event->setParameter('RemiseSaleTypes', $RemiseSaleTypes);
        $asset = 'RemisePayment42/Resource/template/admin/sub_product_edit.twig';
        $event->addAsset($asset);
        return;

    }

    /**
     *  管理画面　商品登録完了イベント
     *
     * @param $event
     */
    public function onAdminProductEditComplete(EventArgs $eventArgs)
    {
        // プラグイン設定情報の取得
        $Config = $this->utilService->getConfig();
        if (!$Config) {
            return;
        }

        // 設定情報の取得
        $ConfigInfo = $Config->getUnserializeInfo();
        if (!$ConfigInfo) {
            return;
        }

        // 定期購買が無効の場合
        if(!$ConfigInfo->useOptionAC())
        {
            return;
        }

        // form情報取得
        $form = $eventArgs->getArgument('form');
        // 商品情報取得
        $Product = $eventArgs->getArgument('Product');

        // 規格有りの商品の場合、処理を抜ける
        $has_class = $Product->hasProductClass();
        if ($has_class) {
            return;
        }

        // ルミーズ定期購買金額取得
        $acAmount = $form->get('remise_payment4_ac_amount')->getViewData();
        $acAmount = str_replace(',','',$acAmount);

        // ルミーズ定期購買種別取得
        $acAcType = $form->get('remise_payment4_ac_actype_id')->getViewData();

        // ルミーズ定期購買ポイント付与
        $acPointFlg = $form->get('remise_payment4_ac_point_flg')->getViewData();

        // 商品規格情報を取得(規格無し商品でも、デフォルトで1件必ず存在する)
        $ProductClasses = $Product->getProductClasses();

        // ルミーズ定期購買商品登録
        $this->acService->saveACProduct($ProductClasses[0], $acAmount, $acAcType, $acPointFlg);

    }

    /**
     * 管理画面　商品規格画面
     *
     * @param $event
     */
    public function onAdminProductProductClassTwig(TemplateEvent $event)
    {
        // プラグイン設定情報の取得
        $Config = $this->utilService->getConfig();
        if (!$Config) {
            return;
        }

        // 設定情報の取得
        $ConfigInfo = $Config->getUnserializeInfo();
        if (!$ConfigInfo) {
            return;
        }

        // 定期購買が無効の場合
        if(!$ConfigInfo->useOptionAC())
        {
            return;
        }

        $asset = 'RemisePayment42/Resource/assets/js/admin/sub_product_class_index.js';
        $event->addAsset($asset);

    }

    /**
     * 管理画面　支払方法登録・編集画面
     *
     * @param $event
     */
    public function onAdminPaymentEditTwig(TemplateEvent $event)
    {
        // 表示対象のEC-CUBE支払方法を取得
        $Payment = $event->getParameter('Payment');

        if (!$Payment)
        {
            return;
        }

        // ルミーズ支払方法取得の取得
        $RemisePayment = $this->utilService->getRemisePayment($Payment->getId());

        if (!$RemisePayment)
        {
            return;
        }

        // カード決済の場合
        if ($RemisePayment->getKind() == trans('remise_payment4.common.label.payment.kind.card'))
        {
            // 手数料の項目を消す
            $asset = 'RemisePayment42/Resource/assets/js/admin/sub_payment_edit_card.js';
            $event->addAsset($asset);
        }
        // マルチ決済の場合
        else if ($RemisePayment->getKind() == trans('remise_payment4.common.label.payment.kind.cvs'))
        {
            // 支払方法の複製ボタンを追加
            $asset = 'RemisePayment42/Resource/assets/js/admin/sub_payment_edit_cvs.js';
            $event->addAsset($asset);
        }
    }

    /**
     * 管理画面　受注一覧
     *
     * @param $event
     */
    public function onAdminOrderIndexTwig(TemplateEvent $event)
    {

        // プラグイン設定情報の取得
        $Config = $this->utilService->getConfig();
        if (!$Config)
        {
            return;
        }

        // 設定情報の取得
        $ConfigInfo = $Config->getUnserializeInfo();
        if (!$ConfigInfo)
        {
            return;
        }

        // 定期購買検索用の検索フォームを設定
        if($ConfigInfo->useOptionAC())
        {
            $snippet = 'RemisePayment42/Resource/template/admin/sub_order_index_ac.twig';
            $event->addSnippet($snippet);
        }

        // 表示対象の受注一覧を取得
        $pagination = $event->getParameter('pagination');

        if (empty($pagination) || count($pagination) == 0)
        {
            return;
        }

        // 受注一覧から受注番号を取得
        $orderIds = array();
        foreach ($pagination as $Order)
        {
            $orderIds[] = $Order->getId();
            $orderNos[$Order->getId()] = $Order->getOrderNo();
        }

        if (empty($orderIds))
        {
            return;
        }

        $event->setParameter('orderNos', $orderNos);

        // 受注未確定の決済履歴詳細を取得
        $OrderResultCardsNotCompleted = $this->utilService->getOrderResultCardForNotCompleted($orderIds);

        // 課金失敗の決済履歴詳細を取得
        $OrderResultCardsAcFailed = $this->utilService->getOrderResultCardForAcFailed($orderIds);

        if($OrderResultCardsNotCompleted || $OrderResultCardsAcFailed)
        {
            if($OrderResultCardsNotCompleted)
            {
                // 受注未確定の決済履歴詳細
                $event->setParameter('NotCompletedOrderResultCards', $OrderResultCardsNotCompleted);
            }else{
                $event->setParameter('NotCompletedOrderResultCards', Array());
            }

            if($OrderResultCardsAcFailed)
            {
                // 課金失敗の決済履歴詳細
                $event->setParameter('AcFailedOrderResultCards', $OrderResultCardsAcFailed);
            }else{
                $event->setParameter('AcFailedOrderResultCards', Array());
            }

            // 注文状況へ課金失敗の表記追加
            $asset = 'RemisePayment42/Resource/assets/js/admin/sub_order_index.js';
            $event->addAsset($asset);
        }

        if (!$ConfigInfo->useOptionExtset())
        {
            return;
        }

        if($ConfigInfo->useOptionExtset())
        {
            // 決済履歴詳細を取得
            $states = Array(
                trans('remise_payment4.common.label.card.state.result'),
                trans('remise_payment4.common.label.card.state.complete'),
                trans('remise_payment4.common.label.card.state.result.deleted'),
                trans('remise_payment4.common.label.card.state.result.ac.failed'),
                trans('remise_payment4.common.label.card.state.result.ac.success'));

            $ExtsetOrderResultCards = $this->utilService->getOrderResultCardsOrderIdsStates($orderIds,$states);
            $event->setParameter('ExtsetOrderResultCards', $ExtsetOrderResultCards);

            // 本体側のステータス、受注番号を取得
            $ExtsetOrderResultCardsOrderState = array();
            foreach ($ExtsetOrderResultCards as $ExtsetOrderResultCard)
            {
                $Order = $this->utilService->getOrder($ExtsetOrderResultCard->getId());
                $ExtsetOrderResultCardsOrderState[$ExtsetOrderResultCard->getId()] = $Order->getOrderStatus()['id'];
            }
            $event->setParameter('ExtsetOrderResultCardsOrderState', $ExtsetOrderResultCardsOrderState);

            $snippet = 'RemisePayment42/Resource/template/admin/sub_order_index.twig';
            $event->addSnippet($snippet);

            $asset = 'RemisePayment42/Resource/assets/js/admin/sub_order_index_extset.js';
            $event->addAsset($asset);

        }

    }

    /**
     * 管理画面　受注一覧(検索実行)
     *
     * @param $event
     */
    public function onAdminOrderIndexSearch(EventArgs $event)
    {
        // SQLを取得
        $qb = $event->getArgument('qb');

        // リクエスト情報を取得
        $request = $event->getRequest();

        if ('POST' === $request->getMethod()) {
            // 検索実行時の検索条件を取得
            $requestData = $request->request->all();
            $admin_search_order = $this->utilService->getValue($requestData, 'admin_search_order');
            $result_status_array = $this->utilService->getValue($admin_search_order, 'remise_payment4_ac_result_status');
            $member_id = $this->utilService->getValue($admin_search_order, 'remise_payment4_ac_member_id');
        }else{
            // 検索実行時以外（他画面からの遷移など）の検索条件を取得
            $viewData = $this->session->get('eccube.admin.order.search', []);
            $result_status_array = $this->utilService->getValue($viewData, 'remise_payment4_ac_result_status');
            $member_id = $this->utilService->getValue($viewData, 'remise_payment4_ac_member_id');
        }

        // テーブル結合
        if($result_status_array || $member_id){
            $qb
            ->innerJoin('\Plugin\RemisePayment42\Entity\OrderResultCard', 'rms_orc', 'WITH', 'rms_orc.id = o.id')
            ->innerJoin('\Plugin\RemisePayment42\Entity\RemiseACMember', 'rms_acm', 'WITH', 'rms_acm.id = rms_orc.member_id')
            ->innerJoin('\Plugin\RemisePayment42\Entity\RemiseACResult', 'rms_acr', 'WITH', 'rms_acr.id = o.id');
        }

        // 自動継続課金結果の検索条件追加
        if($result_status_array){
            $qb
            ->andWhere('rms_acr.result IN (:result_status)')
            ->setParameter('result_status', $result_status_array);
        }

        // メンバーIDの検索条件追加
        if($member_id){
            $qb
            ->andWhere('rms_orc.member_id = :member_id')
            ->setParameter('member_id', $member_id);
        }

    }

    /**
     * 管理画面　受注登録
     *
     * @param $event
     */
    public function onAdminOrderEditTwig(TemplateEvent $event)
    {
        // 表示対象の受注情報を取得
        $Order = $event->getParameter('Order');

        if (!$Order)
        {
            return;
        }
        if (!$Order->getOrderStatus())
        {
            return;
        }

        // ステータス　決済処理中、購入処理中　は対象外
        if ($Order->getOrderStatus()->getId() == OrderStatus::PENDING
         || $Order->getOrderStatus()->getId() == OrderStatus::PROCESSING)
        {
            return;
        }

        // EC-CUBE支払方法の取得
        $Payment = $Order->getPayment();

        if (!$Payment)
        {
            return;
        }

        $paymentId = $Payment->getId();

        // ルミーズ支払方法の取得
        $RemisePayment = $this->utilService->getRemisePayment($paymentId);

        if (!$RemisePayment)
        {
            return;
        }

        // 決済履歴の取得
        $OrderResult = $this->utilService->getOrderResult($Order->getId());

        if (!$OrderResult)
        {
            return;
        }

        $OrderResultCard = null;
        $OrderResultCvs = null;
        $cvsInfo = null;

        // カード決済
        if ($RemisePayment->getKind() == trans('remise_payment4.common.label.payment.kind.card'))
        {
            // 決済履歴詳細の取得
            $OrderResultCard = $this->utilService->getOrderResultCard($Order->getId());

            if (!$OrderResultCard)
            {
                return;
            }
            // 受注未確定のwarningメッセージ
            if ($OrderResultCard->getState() == trans('remise_payment4.common.label.card.state.result'))
            {
                $this->session->getFlashBag()->add('eccube.'."admin".'.warning', trans('remise_payment4.admin_order_edit.text.card.state.result.warning'));
            }
            // 課金失敗のwarningメッセージ
            if ($OrderResultCard->getState() == trans('remise_payment4.common.label.card.state.result.ac.failed'))
            {
                $this->session->getFlashBag()->add('eccube.'."admin".'.warning', trans('remise_payment4.admin_order_edit.text.card.state.result.ac.failed.warning'));
            }
        }
        // マルチ決済
        if ($RemisePayment->getKind() == trans('remise_payment4.common.label.payment.kind.cvs'))
        {
            // 決済履歴詳細の取得
            $OrderResultCvs = $this->utilService->getOrderResultCvs($Order->getId());

            if (!$OrderResultCvs)
            {
                return;
            }

            // 追加情報
            $cvsInfo = $this->utilService->getCvsInfo($Order, $OrderResultCvs);
        }

        // プラグイン設定情報の取得
        $Config = $this->utilService->getConfig();

        // 設定情報の取得
        if ($Config) {
            $ConfigInfo = $Config->getUnserializeInfo();

            // 拡張セットが有効の場合
            if($ConfigInfo->useOptionExtset() && $RemisePayment->getKind() == trans('remise_payment4.common.label.payment.kind.card'))
            {
                if(($OrderResultCard->getState() == trans('remise_payment4.common.label.card.state.complete')
                    || $OrderResultCard->getState() == trans('remise_payment4.common.label.card.state.result.deleted')
                    || $OrderResultCard->getState() == trans('remise_payment4.common.label.card.state.result.ac.success'))
                    && $Order->getOrderStatus()['id'] != OrderStatus::CANCEL
                    && $Order->getOrderStatus()['id'] != OrderStatus::RETURNED
                    && $Order->getOrderStatus()['id'] != OrderStatus::PENDING
                    && $Order->getOrderStatus()['id'] != OrderStatus::PROCESSING
                )
                {
                    $event->setParameter('OptionExtset', true);

                    $remisePaymentCard = $this->remisePaymentRepository->findByKind(trans('remise_payment4.common.label.payment.kind.card'));
                    $event->setParameter('RemisePaymentCard', $remisePaymentCard[0]);
                }
            }
        }

        // 決済履歴
        $event->setParameter('OrderResult', $OrderResult);
        // 決済履歴詳細
        $event->setParameter('OrderResultCard', $OrderResultCard);
        $event->setParameter('OrderResultCvs', $OrderResultCvs);
        // 追加情報
        $event->setParameter('CvsInfo', $cvsInfo);

        // ルミーズ決済情報の表示欄追加
        $snippet = 'RemisePayment42/Resource/template/admin/sub_order_edit.twig';
        $event->addSnippet($snippet);

        // 定期購買が有効な場合
        if($ConfigInfo->useOptionAC()){
            $total = $Order->getTotal();
            $paymentTotal = $Order->getPaymentTotal();

            // 定期購買情報取得
            $RemiseACMember = $this->acService->getRemiseACMemberByOrderId($Order->getId());

            // 定期購買商品、かつ、合計とお支払い合計に差分がある場合
            if($RemiseACMember && $total != $paymentTotal){
                $difference = ($total - $paymentTotal) * -1;
                $event->setParameter('difference', $difference);
                $snippet = 'RemisePayment42/Resource/template/admin/sub_order_edit_ac.twig';
                $event->addSnippet($snippet);
            }
        }
    }

    /**
     * 管理画面　会員一覧
     *
     * @param $event
     */
    public function onAdminCustomerIndexTwig(TemplateEvent $event)
    {
        $customers = $this->customerRepository->findAll();

        $customerIds = array();
        foreach($customers as $customer){
            $customerIds[] = $customer->getId();
        }

        $Customers = $this->remiseACMemberRepository->getCustomersByCustomerIds($customerIds);

        $event->setParameter('Customers', $Customers);
        $asset = 'RemisePayment42/Resource/assets/js/admin/sub_customer_index.js';
        $event->addAsset($asset);
    }

    /**
     * 管理画面　会員登録・編集
     *
     * @param $event
     */
    public function onAdminCustomerEditTwig(TemplateEvent $event)
    {
        // 表示対象の会員情報を取得
        $Customer = $event->getParameter('Customer');

        if (!$Customer || $Customer->getId() == null)
        {
            return;
        }

        // ペイクイック情報の取得
        $Payquick = $this->utilService->getPayquickByCustomer($Customer);

        if (!$Payquick)
        {
            return;
        }

        // ペイクイック情報
        $event->setParameter('Payquick', $Payquick);

        // カードご利用情報の表示欄追加
        $snippet = 'RemisePayment42/Resource/template/admin/sub_customer_edit.twig';
        $event->addSnippet($snippet);
        $asset = 'RemisePayment42/Resource/assets/css/payquick.css';
        $event->addAsset($asset);

        // 会員に紐づく継続中の定期購買情報を検索
        $remiseACMembers = $this->remiseACMemberRepository->getRemiseACMembersByCustomerId($Customer->getId());

        if($remiseACMembers){
            // 継続中の定期購買がある場合、退会をさせない
            $asset = 'RemisePayment42/Resource/assets/js/admin/sub_customer_edit.js';
            $event->addAsset($asset);
        }
    }

    /**
     * 配送先設定
     *
     * @param $event
     */
    public function onAdminSettingSystemDeliveryEditTwig(TemplateEvent $event)
    {
        // ルミーズ販売種別（定期購買）既存レコード確認
        $RemiseSaleTypes = $this->remiseSaleTypeRepository->findBy([
            'sale_type' => trans('remise_payment4.ac.sale_type')
        ], [
            'id' => 'DESC'
        ]);

        if (! $RemiseSaleTypes) {
            return;
        }

        $event->setParameter('RemiseSaleTypes', $RemiseSaleTypes);
        $asset = 'RemisePayment42/Resource/assets/js/admin/sub_delivery_edit.js';
        $event->addAsset($asset);
    }

    /**
     * マスターデータ管理
     *
     * @param $event
     */
    public function onAdminSettingSystemMasterdataTwig(TemplateEvent $event)
    {
        // リクエスト情報を取得
        $form = $event->getParameter('form');
        $masterdata = $form->offsetGet('masterdata');
        if(strcmp($masterdata->vars['value'],'Eccube-Entity-Master-SaleType') != 0) {
            return;
        }

        // プラグイン設定情報の取得
        $Config = $this->utilService->getConfig();
        if (!$Config) {
            return;
        }

        // 設定情報の取得
        $ConfigInfo = $Config->getUnserializeInfo();
        if (!$ConfigInfo) {
            return;
        }

        // 定期購買が無効の場合
        if(!$ConfigInfo->useOptionAC())
        {
            return;
        }

        $snippet = 'RemisePayment42/Resource/template/admin/sub_masterdata_edit.twig';
        $event->addSnippet($snippet);

    }

    /**
     * ショッピングカート
     *
     * @param $event
     */
    public function onCartIndexTwig(TemplateEvent $event)
    {
        // プラグイン設定情報の取得
        $Config = $this->utilService->getConfig();
        if (!$Config) {
            return;
        }

        // 設定情報の取得
        $ConfigInfo = $Config->getUnserializeInfo();
        if (!$ConfigInfo) {
            return;
        }

        // 定期購買が無効の場合
        if(!$ConfigInfo->useOptionAC())
        {
            return;
        }

        // カートの商品情報を取得
        $Carts = $event->getParameter('Carts');
        if(!$Carts){
            return;
        }

        $cartKeys = $this->acService->checkACCartDuplicate($Carts);

        if($cartKeys != null)
        {
            // 購入手続きボタン無効化JavaScriptを差し込む
            $event->setParameter('remiseAcCartKeys', $cartKeys);
            $asset = 'RemisePayment42/Resource/assets/js/sub_cart_index.js';
            $event->addAsset($asset);
            $this->session->getFlashBag()->add('eccube.'.'front.cart'.'.error', trans('remise_payment4.ac.front.cart.duplicate.text'));
        }else{
            return;
        }
    }

    /**
     * ご注文手続き
     *
     * @param $event
     */
    public function onShoppingIndexTwig(TemplateEvent $event)
    {
        // 表示対象の受注情報を取得
        $Order = $event->getParameter('Order');

        if (!$Order)
        {
            return;
        }

        // セッションから処理中の受注番号を取得
        $orderId = $this->session->get('remise_payment4.order_id');
        if (is_null($orderId))
        {
            $orderId = '';
        }

        // セッションに保持している受注番号と表示対象の注文番号が不一致の場合
        if ($orderId != "" && $orderId != $Order->getId())
        {
            // セッションクリア
            $this->session->remove('remise_payment4.payquick_state.' . $orderId);
            $this->session->remove('remise_payment4.use_payquick.' . $orderId);
            $this->session->remove('remise_payment4.method.' . $orderId);
            $this->session->remove('remise_payment4.ptimes.' . $orderId);
            $this->session->remove('remise_payment4.regist_payquick.' . $orderId);
            $this->session->remove('remise_payment4.order_no');
        }

        // セッション設定
        $orderId = $Order->getId();
        $this->session->set('remise_payment4.order_id', $orderId);

        // EC-CUBE支払方法の取得
        $Payment = $Order->getPayment();

        if (!$Payment)
        {
            return;
        }

        $paymentId = $Payment->getId();

        // ルミーズ支払方法の取得
        $RemisePayment = $this->utilService->getRemisePayment($paymentId);

        if (!$RemisePayment)
        {
            return;
        }

        // カード決済の場合
        if ($RemisePayment->getKind() == trans('remise_payment4.common.label.payment.kind.card'))
        {
            // 行程の追加
            $asset = 'RemisePayment42/Resource/assets/js/sub_shopping_flow.js';
            $event->addAsset($asset);

            // プラグイン設定情報の取得
            $ConfigInfo = $this->utilService->getConfigInfo();

            if (!$ConfigInfo)
            {
                return;
            }

            // 定期購買が有効の場合
            if($ConfigInfo->useOptionAC())
            {
                if(!$this->acService->checkACOrderDuplicate($Order))
                {
                    $asset = 'RemisePayment42/Resource/assets/js/sub_shopping_index_redirect_to_cart.js';
                    $event->addAsset($asset);
                    return;
                }

                // 商品規格情報取得
                $ProductClass = $this->acService->getProductClassByOrder($Order);
                // ルミーズ定期購買種別マスタ取得
                $RemiseAcType = $this->acService->getRemiseAcTypeByProductClass($ProductClass);

                if($RemiseAcType){
                    // 課金間隔取得
                    $interval = "";
                    if (strcmp($RemiseAcType->getIntervalMark(), trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.interval_marks.m.key')) == 0)
                    {
                        $interval = $RemiseAcType->getIntervalValue().trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.interval_marks.m.value');
                    }
                    else if(strcmp($RemiseAcType->getIntervalMark(), trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.interval_marks.d.key')) == 0)
                    {
                        $interval = $RemiseAcType->getIntervalValue().trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.interval_marks.d.value');
                    }

                    // 初回課金日取得
                    $firstDate = $this->acService->getFirstDate($RemiseAcType, new \DateTime());

                    // 次回課金額
                    $RemiseACMember = $this->acService->createRemiseACMember($Order);
                    $remiseAcAmount = $RemiseACMember->getTotal();

                    // 課金回数
                    if($RemiseAcType->getCount() && $RemiseAcType->getCount() > 0){
                        $countText = $RemiseAcType->getCount().trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.count.value');
                    }else{
                        $countText = "";
                    }

                    $event->setParameter('RemiseProductClass', $ProductClass);
                    $event->setParameter('RemiseAcType', $RemiseAcType);
                    $event->setParameter('remiseAcAmount', $remiseAcAmount);
                    $event->setParameter('remiseAcFirstDate',$firstDate);
                    $event->setParameter('remiseAcInterval', $interval);
                    $event->setParameter('remiseAcCount', $countText);

                    $snippet = 'RemisePayment42/Resource/template/sub_shopping_index_autocharge.twig';
                    $event->addSnippet($snippet);
                }
            }

            // 会員の注文処理でペイクイックを「利用する」の場合
            if ($Order->getCustomer() && $ConfigInfo->getPayquickFlag() === '1')
            {
                // ペイクイック情報の取得
                $Payquick = $this->utilService->getPayquickByCustomer($Order->getCustomer());

                // ペイクイック情報
                $event->setParameter('Payquick', $Payquick);

                // セッションから情報の取得
                $regist_payquick = '0';
                $use_payquick = '0';
                $method = '';
                $ptimes = '';

                if ($Payquick)
                {
                    // 支払方法の取得
                    $configUseMethod = $ConfigInfo->getUseMethod();
                    if (empty($configUseMethod)) $configUseMethod = array();
                    sort($configUseMethod);

                    $useMethod = [];
                    foreach ($configUseMethod as $key => $value)
                    {
                        $useMethod[trans('remise_payment4.common.label.card_method.' . $value)] = $value;
                    }

                    // 分割回数の取得
                    $configPtimes = $ConfigInfo->getPtimes();
                    if (empty($configPtimes)) $configPtimes = array();
                    sort($configPtimes);

                    $usePtimes = [];
                    foreach ($configPtimes as $key => $value)
                    {
                        $usePtimes[$value . trans('remise_payment4.common.label.card_ptimes_count')] = $value;
                    }

                    $event->setParameter('useMethod', $useMethod);
                    $event->setParameter('usePtimes', $usePtimes);

                    // ペイクイック利用
                    $work = $this->session->get('remise_payment4.use_payquick.' . $orderId);
                    if (isset($work))
                    {
                        $use_payquick = $work;
                    }
                    else {
                        $use_payquick = '1';
                    }
                    // 支払方法
                    $work = $this->session->get('remise_payment4.method.' . $orderId);
                    if (isset($work))
                    {
                        $method = $work;
                    }
                    else {
                        foreach ($configUseMethod as $key => $value)
                        {
                            $method = $value;
                            break;
                        }
                    }
                    // 分割回数
                    $work = $this->session->get('remise_payment4.ptimes.' . $orderId);
                    if (isset($work))
                    {
                        $ptimes = $work;
                    }
                    else {
                        foreach ($configPtimes as $key => $value)
                        {
                            $ptimes = $value;
                            break;
                        }
                    }
                    // クレジットカード登録
                    if ($use_payquick == '1') {
                        $regist_payquick = '1';
                    }
                    else {
                        $work = $this->session->get('remise_payment4.regist_payquick.' . $orderId);
                        if (isset($work))
                        {
                            $regist_payquick = $work;
                        }
                    }
                }
                else {
                    // クレジットカード登録
                    $work = $this->session->get('remise_payment4.regist_payquick.' . $orderId);
                    if (isset($work))
                    {
                        $regist_payquick = $work;
                    }
                }

                // カードご利用情報
                $PayquickInfo = array();
                $PayquickInfo['regist_payquick'] = $regist_payquick;
                $PayquickInfo['use_payquick'] = $use_payquick;
                $PayquickInfo['method'] = $method;
                $PayquickInfo['ptimes'] = $ptimes;

                $event->setParameter('PayquickInfo', $PayquickInfo);

                // カードご利用情報の表示欄追加
                $snippet = 'RemisePayment42/Resource/template/sub_shopping_index_payquick.twig';
                $event->addSnippet($snippet);
                $asset = 'RemisePayment42/Resource/assets/css/payquick.css';
                $event->addAsset($asset);

            }
        }
        // マルチ決済の場合
        else if ($RemisePayment->getKind() == trans('remise_payment4.common.label.payment.kind.cvs'))
        {
            // 行程の追加
            $asset = 'RemisePayment42/Resource/assets/js/sub_shopping_flow.js';
            $event->addAsset($asset);
        }
    }

    /**
     * ご注文内容のご確認
     *
     * @param $event
     */
    public function onShoppingConfirmTwig(TemplateEvent $event)
    {
        // 表示対象の受注情報の取得
        $Order = $event->getParameter('Order');

        if (!$Order)
        {
            return;
        }

        // セッションから処理中の受注番号を取得
        $orderId = $this->session->get('remise_payment4.order_id');
        if (is_null($orderId))
        {
            $orderId = '';
        }

        // EC-CUBE支払方法の取得
        $Payment = $Order->getPayment();

        if (!$Payment)
        {
            return;
        }

        $paymentId = $Payment->getId();

        // ルミーズ支払方法の取得
        $RemisePayment = $this->utilService->getRemisePayment($paymentId);

        if (!$RemisePayment)
        {
            return;
        }

        // カード決済の場合
        if ($RemisePayment->getKind() == trans('remise_payment4.common.label.payment.kind.card'))
        {
            // 行程の追加
            $asset = 'RemisePayment42/Resource/assets/js/sub_shopping_flow.js';
            $event->addAsset($asset);
            // ボタン名の変更
            $asset = 'RemisePayment42/Resource/assets/js/sub_shopping_confirm.twig';
            $event->addAsset($asset);

            // プラグイン設定情報の取得
            $ConfigInfo = $this->utilService->getConfigInfo();

            if (!$ConfigInfo)
            {
                return;
            }

            // 定期購買が有効の場合
            if($ConfigInfo->useOptionAC())
            {
                // 商品規格情報取得
                $ProductClass = $this->acService->getProductClassByOrder($Order);
                // ルミーズ定期購買種別マスタ取得
                $RemiseAcType = $this->acService->getRemiseAcTypeByProductClass($ProductClass);

                if($RemiseAcType){
                    // 課金間隔取得
                    $interval = "";
                    if (strcmp($RemiseAcType->getIntervalMark(), trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.interval_marks.m.key')) == 0)
                    {
                        $interval = $RemiseAcType->getIntervalValue().trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.interval_marks.m.value');
                    }
                    else if(strcmp($RemiseAcType->getIntervalMark(), trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.interval_marks.d.key')) == 0)
                    {
                        $interval = $RemiseAcType->getIntervalValue().trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.interval_marks.d.value');
                    }

                    // 初回課金日取得
                    $firstDate = $this->acService->getFirstDate($RemiseAcType, new \DateTime());

                    // 次回課金額
                    $RemiseACMember = $this->acService->createRemiseACMember($Order);
                    $remiseAcAmount = $RemiseACMember->getTotal();

                    // 課金回数
                    if($RemiseAcType->getCount() && $RemiseAcType->getCount() > 0){
                        $countText = $RemiseAcType->getCount().trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.count.value');
                    }else{
                        $countText = "";
                    }

                    $event->setParameter('RemiseProductClass', $ProductClass);
                    $event->setParameter('RemiseAcType', $RemiseAcType);
                    $event->setParameter('remiseAcAmount', $remiseAcAmount);
                    $event->setParameter('remiseAcFirstDate',$firstDate);
                    $event->setParameter('remiseAcInterval', $interval);
                    $event->setParameter('remiseAcCount', $countText);

                    $snippet = 'RemisePayment42/Resource/template/sub_shopping_index_autocharge.twig';
                    $event->addSnippet($snippet);
                }
            }

            // 会員の注文処理でペイクイックを「利用する」の場合
            if ($Order->getCustomer() && $ConfigInfo->getPayquickFlag() === '1')
            {
                // ペイクイック情報の取得
                $Payquick = $this->utilService->getPayquickByCustomer($Order->getCustomer());

                // ペイクイック情報
                $event->setParameter('Payquick', $Payquick);

                // フォーム情報
                $form = $event->getParameter('form');

                $payquick_state = '0';
                $regist_payquick = '0';
                $use_payquick = '0';
                $method = '';
                $method_name = '';
                $ptimes = '';
                $ptimes_name = '';

                // 新規時
                if (!$Payquick)
                {
                    $payquick_state = '1';
                    // クレジットカード登録
                    $work = $form->offsetGet('remise_payment4_regist_payquick')->vars['data'];
                    if (isset($work))
                    {
                        foreach ($work as $value)
                        {
                            if ($value == '1')
                            {
                                $regist_payquick = '1';
                            }
                        }
                    }
                }
                // 既存時
                else
                {
                    $payquick_state = '2';
                    // ペイクイック利用
                    $work = $form->offsetGet('remise_payment4_use_payquick')->vars['data'];
                    if (isset($work))
                    {
                        $use_payquick = $work;
                    }
                    // 支払方法
                    $work = $form->offsetGet('remise_payment4_method')->vars['data'];
                    if (isset($work))
                    {
                        $method = $work;
                    }
                    $method_name = trans('remise_payment4.common.label.card_method.' . $method);
                    // 分割回数
                    $work = $form->offsetGet('remise_payment4_ptimes')->vars['data'];
                    if (isset($work))
                    {
                        $ptimes = $work;
                    }
                    $ptimes_name = $ptimes . trans('remise_payment4.common.label.card_ptimes_count');

                    // クレジットカード登録
                    if ($use_payquick == '1') {
                        $regist_payquick = '1';
                    }
                    else {
                        $work = $form->offsetGet('remise_payment4_regist_payquick')->vars['data'];
                        if (isset($work))
                        {
                            foreach ($work as $value)
                            {
                                if ($value == '1')
                                {
                                    $regist_payquick = '1';
                                }
                            }
                        }
                    }
                }

                // セッション設定
                $this->session->set('remise_payment4.regist_payquick.' . $orderId, $regist_payquick);
                $this->session->set('remise_payment4.use_payquick.' . $orderId, $use_payquick);
                $this->session->set('remise_payment4.method.' . $orderId, $method);
                $this->session->set('remise_payment4.ptimes.' . $orderId, $ptimes);

                // カードご利用情報
                $PayquickInfo = array();
                $PayquickInfo['payquick_state'] = $payquick_state;
                $PayquickInfo['regist_payquick'] = $regist_payquick;
                $PayquickInfo['use_payquick'] = $use_payquick;
                $PayquickInfo['method'] = $method;
                $PayquickInfo['method_name'] = $method_name;
                $PayquickInfo['ptimes'] = $ptimes;
                $PayquickInfo['ptimes_name'] = $ptimes_name;

                $event->setParameter('PayquickInfo', $PayquickInfo);

                // カードご利用情報の表示欄追加
                $snippet = 'RemisePayment42/Resource/template/sub_shopping_confirm_payquick.twig';
                $event->addSnippet($snippet);
                $asset = 'RemisePayment42/Resource/assets/css/payquick.css';
                $event->addAsset($asset);
            }
        }
        // マルチ決済の場合
        else if ($RemisePayment->getKind() == trans('remise_payment4.common.label.payment.kind.cvs'))
        {
            // 行程の追加
            $asset = 'RemisePayment42/Resource/assets/js/sub_shopping_flow.js';
            $event->addAsset($asset);
            // ボタン名の変更
            $asset = 'RemisePayment42/Resource/assets/js/sub_shopping_confirm.twig';
            $event->addAsset($asset);
        }
    }

    /**
     * ご注文完了
     *
     * @param $event
     */
    public function onShoppingCompleteTwig(TemplateEvent $event)
    {
        // 表示対象の受注情報を取得
        $Order = $event->getParameter('Order');

        if (!$Order)
        {
            return;
        }

        // EC-CUBE支払方法の取得
        $Payment = $Order->getPayment();

        if (!$Payment)
        {
            return;
        }

        $paymentId = $Payment->getId();

        // ルミーズ支払方法の取得
        $RemisePayment = $this->utilService->getRemisePayment($paymentId);

        if (!$RemisePayment)
        {
            return;
        }

        // カード決済の場合
        if ($RemisePayment->getKind() == trans('remise_payment4.common.label.payment.kind.card'))
        {
            // 行程の追加
            $asset = 'RemisePayment42/Resource/assets/js/sub_shopping_flow.js';
            $event->addAsset($asset);
        }
        // マルチ決済の場合
        else if ($RemisePayment->getKind() == trans('remise_payment4.common.label.payment.kind.cvs'))
        {
            // 行程の追加
            $asset = 'RemisePayment42/Resource/assets/js/sub_shopping_flow.js';
            $event->addAsset($asset);
        }
    }

    /**
     * マイページ/ご注文履歴一覧
     *
     * @param $event
     */
    public function onMypageIndexTwig(TemplateEvent $event)
    {
        // 表示対象の注文履歴一覧を取得
        $pagination = $event->getParameter('pagination');

        if (empty($pagination) || count($pagination) == 0)
        {
            return;
        }

        // 注文履歴一覧から受注番号を取得
        $orderIds = array();
        foreach ($pagination as $Order)
        {
            $orderIds[] = $Order->getId();
        }

        if (empty($orderIds))
        {
            return;
        }

        // 受注未確定の決済履歴詳細を取得
        $OrderResultCards = $this->utilService->getOrderResultCardForNotCompleted($orderIds);

        if (!$OrderResultCards)
        {
            return;
        }

        // 受注未確定の決済履歴詳細
        $event->setParameter('NotCompletedOrderResultCards', $OrderResultCards);

        // ご注文状況を入金確認中（受注未確定）へ表記変更
        $asset = 'RemisePayment42/Resource/assets/js/sub_mypage_index.js';
        $event->addAsset($asset);
    }

    /**
     * マイページ/ご注文履歴詳細
     *
     * @param $event
     */
    public function onMypageHistoryTwig(TemplateEvent $event)
    {
        // プラグイン設定情報の取得
        $Config = $this->utilService->getConfig();
        if (!$Config) {
            return;
        }

        // 設定情報の取得
        $ConfigInfo = $Config->getUnserializeInfo();
        if (!$ConfigInfo) {
            return;
        }

        // 表示対象の受注情報を取得
        $Order = $event->getParameter('Order');

        if (!$Order)
        {
            return;
        }

        // 定期購買が有効の場合
        if($ConfigInfo->useOptionAC())
        {
            // 定期購買メンバー情報取得
            $RemiseACMember = $this->acService->getRemiseACMemberByOrderId($Order->getId());
            if($RemiseACMember){
                $event->setParameter('RemiseACMember', $RemiseACMember);
                $event->setParameter('skippingFlg', $this->acService->getSkippingFlg($RemiseACMember));
                $event->setParameter('refusalFlg', $this->acService->getRefusalFlg($RemiseACMember));
                $event->setParameter('dispAcStopFlg', $this->acService->getDispAcStopFlg($RemiseACMember));
                $event->setParameter('useKeizokuEditExtend', $this->acApiService->useKeizokuEditExtend());
                $event->setParameter('useKeizokuResultExtend', $this->acApiService->useKeizokuResultExtend());
                $snippet = 'RemisePayment42/Resource/template/sub_mypage_history_autocharge.twig';
                $event->addSnippet($snippet);
            }
        }

        $orderIds = array($Order->getId());

        // ご注文状況へ入金確認中（受注未確定）の表記追加
        $OrderResultCards = $this->utilService->getOrderResultCardForNotCompleted($orderIds);

        if (!$OrderResultCards || count($OrderResultCards) == 0)
        {
            return;
        }

        // ご注文状況を入金確認中（受注未確定）へ表記変更
        $asset = 'RemisePayment42/Resource/assets/js/sub_mypage_history.js';
        $event->addAsset($asset);
    }

    /**
     * マイページ/会員情報編集
     *
     * @param $event
     */
    public function onMypageChangeTwig(TemplateEvent $event)
    {
        // ペイクイック削除後の状況をセッションから取得
        $delete_payquick = $this->session->get('remise_payment4.delete_payquick');
        if (is_null($delete_payquick)) $delete_payquick = '';

        // ペイクイック削除を実施した場合
        if ($delete_payquick == '1')
        {
            // ペイクイック削除完了メッセージの表示
            $snippet = 'RemisePayment42/Resource/template/sub_mypage_change_payquick_deleted.twig';
            $event->addSnippet($snippet);
            $asset = 'RemisePayment42/Resource/assets/css/payquick.css';
            $event->addAsset($asset);
        }

        // ペイクイック削除情報のクリア
        $this->session->remove('remise_payment4.delete_payquick');

        // 表示対象のフォームを取得
        $form = $event->getParameter('form');

        if (empty($form) || !isset($form->vars['value']))
        {
            return;
        }

        // 会員情報の取得
        $Customer = $form->vars['value'];

        if (!$Customer)
        {
            return;
        }

        // ペイクイック情報の取得
        $Payquick = $this->utilService->getPayquickByCustomer($Customer);

        if (!$Payquick)
        {
            return;
        }

        // ペイクイック情報
        $event->setParameter('Payquick', $Payquick);

        // カードご利用情報の表示欄追加
        $snippet = 'RemisePayment42/Resource/template/sub_mypage_change_payquick.twig';
        $event->addSnippet($snippet);
        $asset = 'RemisePayment42/Resource/assets/css/payquick.css';
        $event->addAsset($asset);
    }

    /**
     * マイページ/退会
     *
     * @param $event
     */
    public function onMypageWithdrawTwig(TemplateEvent $event)
    {
        // 表示対象の会員情報を取得
        $Customer = $this->utilService->getLoginCustomer();

        // 会員に紐づく継続中の定期購買情報を検索
        $remiseACMembers = $this->remiseACMemberRepository->getRemiseACMembersByCustomerId($Customer->getId());

        if(!$remiseACMembers){
            return;
        }

        foreach($remiseACMembers as $remiseACMember){
            $this->acService->setRemiseAcMemberRelatedOrder($remiseACMember);
        }

        $event->setParameter('RemiseACMembers', $remiseACMembers);
        $snippet = 'RemisePayment42/Resource/template/sub_mypage_withdraw.twig';
        $event->addSnippet($snippet);
    }
}
