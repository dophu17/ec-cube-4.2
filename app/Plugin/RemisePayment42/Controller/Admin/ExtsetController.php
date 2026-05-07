<?php

namespace Plugin\RemisePayment42\Controller\Admin;

use Eccube\Controller\AbstractController;
use Eccube\Controller\Admin\Order\EditController;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Entity\Payment;
use Eccube\Repository\OrderRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DomCrawler\Crawler;

use Plugin\RemisePayment42\Entity\Config;
use Plugin\RemisePayment42\Entity\ConfigInfo;
use Plugin\RemisePayment42\Entity\OrderResult;
use Plugin\RemisePayment42\Entity\OrderResultCard;
use Plugin\RemisePayment42\Entity\RemisePayment;
use Plugin\RemisePayment42\Service\LogService;
use Plugin\RemisePayment42\Service\UtilService;
use Plugin\RemisePayment42\Form\Type\Admin\SubOrderIndexResultType;

/**
 * 拡張セット
 */
class ExtsetController extends AbstractController
{
    /**
     * @var UtilService
     */
    protected $utilService;

    /**
     * @var LogService
     */
    protected $logService;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * curlのレスポンス情報
     */
    private $curlResponse;

    /**
     * curlの実行結果
     */
    private $curlInfo;

    /**
     * curlのエラー情報
     */
    private $curlError;

    /**
     * コンストラクタ
     *
     * @param UtilService $utilService
     * @param LogService $logService
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        UtilService $utilService,
        LogService $logService,
        OrderRepository $orderRepository
        ) {
            $this->utilService = $utilService;
            $this->logService = $logService;
            $this->orderRepository = $orderRepository;
    }

    /**
     * 売上・キャンセル・金額変更処理
     *
     * @param  $request
     *
     * @Route("/%eccube_admin_route%/remise_payment_extset4_sub_order_edit", name="remise_payment_extset4_sub_order_edit")
     */
    public function edit(Request $request)
    {
        try
       {
            // 開始ログ
            $this->logService->logInfo('Sub Order Edit Extset');

            $requestData = $request->request->all();

            // リクエストログ
            // $this->logService->logInfo('Sub Order Edit Extset -- Request', $requestData);

            // 入力チェック
            $inputCheckResult = $this->inputCheck($requestData);

            // 入力チェックエラー
            if($inputCheckResult == 1)
            {
                return $this->redirectToRoute('admin_order_page', ['page_no' => '1']);
            }

            // 入力チェックエラー(オーダーIDが取得できている場合)
            if($inputCheckResult == 2)
            {
                return $this->redirectToRoute('admin_order_edit', ['id' => $requestData['remise_option_extset']['order_id']]);
            }

            // プラグイン基本情報の取得
            $Plugin = $this->utilService->getPlugin();
            if(!$Plugin)
            {
                $this->logService->logWarning('Sub Order Edit Extset',
                    Array(trans('remise_payment4.extset.admin_order_edit.text.warning.plugin')));
                $this->addWarning(trans('remise_payment4.extset.admin_order_edit.text.warning.plugin'),'admin');
                return $this->redirectToRoute('admin_order_edit', ['id' => $requestData['remise_option_extset']['order_id']]);
            }

            // プラグイン設定情報の取得
            $Config = $this->utilService->getConfig();
            if(!$Config)
            {
                $this->logService->logWarning('Sub Order Edit Extset',
                    Array(trans('remise_payment4.extset.admin_order_edit.text.warning.plugin')));
                $this->addWarning(trans('remise_payment4.extset.admin_order_edit.text.warning.plugin'),'admin');
                return $this->redirectToRoute('admin_order_edit', ['id' => $requestData['remise_option_extset']['order_id']]);
            }

            // プラグイン設定詳細情報の取得
            $ConfigInfo = $Config->getUnserializeInfo();
            if(!$ConfigInfo)
            {
                $this->logService->logWarning('Sub Order Edit Extset',
                    Array(trans('remise_payment4.extset.admin_order_edit.text.warning.plugin')));
                $this->addWarning(trans('remise_payment4.extset.admin_order_edit.text.warning.plugin'),'admin');
                return $this->redirectToRoute('admin_order_edit', ['id' => $requestData['remise_option_extset']['order_id']]);
            }

            // オーダーID取得
            $orderId = $requestData['remise_option_extset']['order_id'];

            $job = $requestData['remise_option_extset']['job'];

            // JOB取得
            $paymentTotal = '0';
            if ($job == "CHANGE") {
                $paymentTotal = round($requestData['remise_option_extset']['payment_total']);
            }

            // 受注情報の取得
            $Order = $this->utilService->getOrder($orderId);
            if (!$Order)
            {
                $this->logService->logError('Sub Order Edit Extset',Array("order_id:".$orderId,trans('remise_payment4.extset.admin_order_edit.text.warning.notfound_card')));
                $this->addError(trans('remise_payment4.extset.admin_order_edit.text.warning.notfound_card'),'admin');
                return $this->redirectToRoute('admin_order_edit', ['id' => $requestData['remise_option_extset']['order_id']]);
            }

            // OrderNo取得
            $orderNo = $Order->getOrderNo();

            // 決済履歴の取得
            $OrderResult = $this->utilService->getOrderResult($orderId);
            if (!$OrderResult)
            {
                $this->logService->logError('Sub Order Edit Extset',Array("order_id:".$orderId,trans('remise_payment4.extset.admin_order_edit.text.warning.notfound_card')));
                $this->addError(trans('remise_payment4.extset.admin_order_edit.text.warning.notfound_card'),'admin');
                return $this->redirectToRoute('admin_order_edit', ['id' => $requestData['remise_option_extset']['order_id']]);
            }

            // 決済履歴詳細の取得
            $OrderResultCard = $this->utilService->getOrderResultCard($orderId);
            if (!$OrderResultCard)
            {
                $this->logService->logError('Sub Order Edit Extset',Array("order_id:".$orderId,trans('remise_payment4.extset.admin_order_edit.text.warning.notfound_card')));
                $this->addError(trans('remise_payment4.extset.admin_order_edit.text.warning.notfound_card'),'admin');
                return $this->redirectToRoute('admin_order_edit', ['id' => $requestData['remise_option_extset']['order_id']]);
            }

            // RETURN指定で、返品の場合、当日取消の場合
            if ($job == "RETURN") {
                if ($OrderResultCard->getCreateDate()->format('Y-m-d') == date('Y-m-d')) {
                    $job = "VOID";
                }
            }

            // 売上
            if($job == 'SALES')
            {
                // 仮売上以外はエラーを返却
                if($OrderResultCard->getJob() != 'AUTH')
                {
                    $this->logService->logError('Sub Order Edit Extset',Array("order_id:".$orderId,trans('remise_payment4.extset.admin_order_edit.text.warning.not_auth')));
                    $this->addError(trans('remise_payment4.extset.admin_order_edit.text.warning.not_auth'),'admin');
                    return $this->redirectToRoute('admin_order_edit', ['id' => $requestData['remise_option_extset']['order_id']]);
                }
            }
            // 金額変更
            elseif($job == "CHANGE")
            {
                // 仮売上、売上以外はエラーを返却
                if($OrderResultCard->getJob() != 'AUTH' && $OrderResultCard->getJob() != 'CAPTURE' && $OrderResultCard->getJob() != 'SALES')
                {
                    $this->logService->logError('Sub Order Edit Extset',Array("order_id:".$orderId,trans('remise_payment4.extset.admin_order_edit.text.warning.not_auth_or_capture')));
                    $this->addError(trans('remise_payment4.extset.admin_order_edit.text.warning.not_auth_or_capture'),'admin');
                    return $this->redirectToRoute('admin_order_edit', ['id' => $requestData['remise_option_extset']['order_id']]);
                }
            }
            // 取消、返品
            elseif($job == "VOID" || $job == "RETURN")
            {
                // 仮売上、売上以外はエラーを返却
                if($OrderResultCard->getJob() != 'AUTH' && $OrderResultCard->getJob() != 'CAPTURE' && $OrderResultCard->getJob() != 'SALES')
                {
                    $this->logService->logError('Sub Order Edit Extset',Array("order_id:".$orderId,trans('remise_payment4.extset.admin_order_edit.text.warning.not_auth_or_capture')));
                    $this->addError(trans('remise_payment4.extset.admin_order_edit.text.warning.not_auth_or_capture'),'admin');
                    return $this->redirectToRoute('admin_order_edit', ['id' => $requestData['remise_option_extset']['order_id']]);
                }
            }

            // 拡張セットURL取得
            $url = $ConfigInfo->getExtsetCardUrl();
            // ヘッダ
            $headers = array(
                "User-Agent:" . trans('remise_payment4.extset.common.label.plugin_code') . '_PLG_VER ' . $Plugin->getVersion() . "(" . $job . ")",
            );
            // パラメータ
            $postData = array(
                'SHOPCO'        => $ConfigInfo->getShopco(),
                'HOSTID'        => $ConfigInfo->getExtsetHostid(),
                'S_TORIHIKI_NO' => $orderNo,
                'JOB'           => $job,
                'TRANID'        => $OrderResultCard->getTranid(),
                'RETURL'        => 'https://www.remise.co.jp/',
            );
            // 金額変更時のパラメータ設定
            if ($job == "CHANGE")
            {
                $postData['TOTAL'] = $paymentTotal;
                $postData['TAX']   = '0';
            }

            // リクエストデータログ出力
            $this->logService->logInfo('Sub Order Edit Extset -- Request URL', Array($url));
            $this->logService->logInfo('Sub Order Edit Extset -- Request Data', $postData);

            // リクエスト処理
            $retData = $this->extsetRequest($url, $headers, $postData);

            // 接続エラー
            if ($retData === false)
            {
                // ログ出力
                $this->logService->logError('Sub Order Edit Extset',Array(trans('remise_payment4.extset.admin_order_edit.text.failed.url'), $this->curlError, $this->curlInfo));
                $this->addError(trans('remise_payment4.extset.admin_order_edit.text.failed.url'),'admin');
                return $this->redirectToRoute('admin_order_edit', ['id' => $orderId]);
            }
            // 正常
            else
          {
                // レスポンスデータログ出力
                $this->logService->logInfo('Sub Order Edit Extset -- Response Data',$retData);

                if (!isset($retData['X-R_CODE'])  ) $retData['X-R_CODE']   = "9:0000";
                if (!isset($retData['X-ERRLEVEL'])) $retData['X-ERRLEVEL'] = "";
                if (!isset($retData['X-ERRCODE']) ) $retData['X-ERRCODE']  = "";

                // 正常
                if ($retData['X-R_CODE'] == "0:0000" && $retData['X-ERRLEVEL'] == "0")
                {
                    // 売上
                    if($job == 'SALES')
                    {
                        // ルミーズカード決済受注情報を「売上済み」に変更
                        $OrderResultCard->setJob($job);
                        $OrderResultCard->setPrevTranid($OrderResultCard->getTranid());
                        $OrderResultCard->setPrevPaymentTotal($Order->getPaymentTotal());
                        $OrderResultCard->setTranid($retData['X-TRANID']);
                        $OrderResultCard->setUpdateDate(new \DateTime());
                        $OrderResultCard->setSalesDate(new \DateTime());
                        $this->entityManager->persist($OrderResultCard);
                        $this->entityManager->flush($OrderResultCard);

                        // 受注情報のステータスが新規受付の場合、「入金済み」に変更
                        if($Order->getOrderStatus()->getId() == OrderStatus::NEW)
                        {
                            $this->utilService->setOrderStatusByPaid($Order);
                            $this->entityManager->persist($Order);
                            $this->entityManager->flush();
                        }
                        else
                    {
                            $Order->setPaymentDate(new \DateTime());
                            $this->entityManager->persist($Order);
                            $this->entityManager->flush();
                        }

                        // 会員の場合、購入回数、購入金額などを更新
                        if ($Customer = $Order->getCustomer()) {
                            $this->orderRepository->updateOrderSummary($Customer);
                            $this->entityManager->flush($Customer);
                        }
                    }
                    // 金額変更
                    else if($job == 'CHANGE')
                    {

                        // ルミーズ受注情報を更新
                        $OrderResult->setPaymentTotal($paymentTotal);
                        $OrderResult->setUpdateDate(new \DateTime());
                        $this->entityManager->persist($OrderResult);
                        $this->entityManager->flush($OrderResult);

                        // ルミーズカード決済受注情報を更新
                        $OrderResultCard->setPrevTranid($OrderResultCard->getTranid());
                        $OrderResultCard->setPrevPaymentTotal($Order->getPaymentTotal());
                        $OrderResultCard->setTranid($retData['X-TRANID']);
                        $OrderResultCard->setUpdateDate(new \DateTime());
                        $this->entityManager->persist($OrderResultCard);
                        $this->entityManager->flush($OrderResultCard);

                        // EC-CUBE本体側にあとは任せる。
                        $requestData['mode'] = 'register';
                        $this->logService->logInfo('Sub Order Edit Extset -- SUCCESS',Array("order_id:".$orderId));
                        $this->addSuccess(trans('remise_payment4.extset.admin_order_edit.text.success.'.mb_strtolower($job)),'admin');
                        return $this->forwardToRoute('admin_order_edit', ['id' => $orderId],$requestData);

                    }
                    // 即日取消
                    else if($job == 'VOID')
                    {
                        // ルミーズカード決済受注情報を「即日取消」に変更
                        $OrderResultCard->setJob($job);
                        $OrderResultCard->setPrevTranid($OrderResultCard->getTranid());
                        $OrderResultCard->setPrevPaymentTotal($Order->getPaymentTotal());
                        $OrderResultCard->setTranid($retData['X-TRANID']);
                        $OrderResultCard->setUpdateDate(new \DateTime());
                        $OrderResultCard->setCancelDate(new \DateTime());
                        $this->entityManager->persist($OrderResultCard);
                        $this->entityManager->flush($OrderResultCard);

                        if($Order->getOrderStatus()->getId() == OrderStatus::DELIVERED)
                        {
                            // 発送済みの状態の場合、受注情報のステータスを「返品」に変更
                            $this->utilService->setOrderStatusByReturned($Order);
                            $this->entityManager->persist($Order);
                            $this->entityManager->flush();
                        }
                        else
                    {
                            // 発送済みより前の場合、受注情報のステータスを「注文取消し」に変更
                            $this->utilService->setOrderStatusByCancel($Order);
                            $this->entityManager->persist($Order);
                            $this->entityManager->flush();
                        }

                        // 会員の場合、購入回数、購入金額などを更新
                        if ($Customer = $Order->getCustomer()) {
                            $this->orderRepository->updateOrderSummary($Customer);
                            $this->entityManager->flush($Customer);
                        }

                    }
                    // 返品
                    else if($job == 'RETURN')
                    {
                        // ルミーズカード決済受注情報を「返品」に変更
                        $OrderResultCard->setJob($job);
                        $OrderResultCard->setPrevTranid($OrderResultCard->getTranid());
                        $OrderResultCard->setPrevPaymentTotal($Order->getPaymentTotal());
                        $OrderResultCard->setTranid($retData['X-TRANID']);
                        $OrderResultCard->setUpdateDate(new \DateTime());
                        $OrderResultCard->setCancelDate(new \DateTime());
                        $this->entityManager->persist($OrderResultCard);
                        $this->entityManager->flush($OrderResultCard);

                        if($Order->getOrderStatus()->getId() == OrderStatus::DELIVERED)
                        {
                            // 発送済みの状態の場合、受注情報のステータスを「返品」に変更
                            $this->utilService->setOrderStatusByReturned($Order);
                            $this->entityManager->persist($Order);
                            $this->entityManager->flush();
                        }
                        else
                    {
                            // 発送済みより前の場合、受注情報のステータスを「注文取消し」に変更
                            $this->utilService->setOrderStatusByCancel($Order);
                            $this->entityManager->persist($Order);
                            $this->entityManager->flush();
                        }

                        // 会員の場合、購入回数、購入金額などを更新
                        if ($Customer = $Order->getCustomer()) {
                            $this->orderRepository->updateOrderSummary($Customer);
                            $this->entityManager->flush($Customer);
                        }

                    }

                    // ログ出力
                    $this->logService->logInfo('Sub Order Edit Extset -- SUCCESS',Array("order_id:".$orderId));
                    $this->addSuccess(trans('remise_payment4.extset.admin_order_edit.text.success.'.mb_strtolower($job)),'admin');
                }
                // 異常
                else
              {
                    // エラーメッセージ取得
                    $errMsg = $this->utilService->getExtsetErrorMassage($job,$retData['X-R_CODE']);
                    // ログ出力
                    $this->logService->logError('Sub Order Edit Extset',Array("order_id:".$orderId,$errMsg));
                    // エラーを返却
                    $this->addError($errMsg,'admin');
                    return $this->redirectToRoute('admin_order_edit', ['id' => $orderId]);
                }
            }
            // 終了ログ
            $this->logService->logInfo('Sub Order Edit Extset -- Done');

            return $this->redirectToRoute('admin_order_edit', ['id' => $orderId]);
        }
        catch (\Exception $e)
        {
            // ログ出力
            $this->logService->logError('Sub Order Edit Extset',
                Array(trans('remise_payment4.extset.admin_order_edit.text.failed.error'),
                    'ErrCode:' . $e->getCode(),
                    'ErrMessage:' . $e->getMessage()));
            $this->addError(trans('remise_payment4.extset.admin_order_edit.text.failed.error'),'admin');
            if(isset($orderId))
            {
                return $this->redirectToRoute('admin_order_edit', ['id' => $orderId]);
            }
            else
          {
                return $this->redirectToRoute('admin_order_page', ['page_no' => '1']);
            }
        }
    }

    /**
     * 売上・キャンセル・金額変更処理入力チェック
     *
     * @param  $requestData
     *
     */
    private function inputCheck($requestData)
    {
        // 拡張セット以外の呼出の場合、リターン
        if(!array_key_exists('remise_option_extset',$requestData))
        {
            $this->logService->logWarning('Sub Order Edit Extset',
                Array(trans('remise_payment4.extset.admin_order_edit.text.warning.bat_request'),'remise_option_extset'));
            $this->addWarning(trans('remise_payment4.extset.admin_order_edit.text.warning.bat_request'),'admin');
            return 1;
        }

        if(!array_key_exists('order_id',$requestData['remise_option_extset']))
        {
            $this->logService->logWarning('Sub Order Edit Extset',
                Array(trans('remise_payment4.extset.admin_order_edit.text.warning.bat_request'),'order_id'));
            $this->addWarning(trans('remise_payment4.extset.admin_order_edit.text.warning.bat_request'),'admin');
            return 1;
        }

        if(!array_key_exists('job',$requestData['remise_option_extset']))
        {
            $this->logService->logWarning('Sub Order Edit Extset',
                Array(trans('remise_payment4.extset.admin_order_edit.text.warning.bat_request'),'job'));
            $this->addWarning(trans('remise_payment4.extset.admin_order_edit.text.warning.bat_request'),'admin');
            return 2;
        }

        if($requestData['remise_option_extset']['job'] != "SALES" &&
            $requestData['remise_option_extset']['job'] != "CHANGE" &&
            $requestData['remise_option_extset']['job'] != "RETURN")
        {
            $this->logService->logWarning('Sub Order Edit Extset',
                Array(trans('remise_payment4.extset.admin_order_edit.text.warning.bat_request'),'job'));
            $this->addWarning(trans('remise_payment4.extset.admin_order_edit.text.warning.bat_request'),'admin');
            return 2;
        }

        return 0;
    }

    /**
     * 一括売上処理
     *
     * @param  $request
     *
     * @Route("/%eccube_admin_route%/remise_payment_extset4_sub_order_sales", name="remise_payment_extset4_sub_order_sales")
     * @Template("@RemisePayment42/admin/sub_order_index_result.twig")
     */
    public function sales(Request $request)
    {
        try
       {
            $requestData = $request->request->all();

            // リクエストログ
            $this->logService->logInfo('Sub Order Sales Extset -- Request', $requestData);

            // ページ番号取得
            if(array_key_exists('remise_option_extset',$requestData) &&
               array_key_exists('page_no',$requestData['remise_option_extset']) &&
               $requestData['remise_option_extset']['page_no'] != "")
            {
                $page_no = $requestData['remise_option_extset']['page_no'];
            }
            else
          {
                $page_no = "1";
            }

            // 入力チェック
            if(!$this->inputCheckSales($requestData))
            {
                return $this->redirectToRoute('admin_order_page', ['page_no' => $page_no]);
            }

            // オーダーIDリストを取得
            $orderIdList = explode(',', $requestData['remise_option_extset']['order_ids']);

            // 決済履歴詳細を取得
            $states = Array(
                trans('remise_payment4.common.label.card.state.complete'),
                trans('remise_payment4.common.label.card.state.result.deleted'),
                trans('remise_payment4.common.label.card.state.result.ac.success'));

            $OrderResultCards = $this->utilService->getOrderResultCardsOrderIdsStates($orderIdList,$states);
            if (!$OrderResultCards)
            {
                $this->logService->logError('Sub Order Sales Extset',Array(trans('remise_payment4.extset.admin_order_index.text.warning.notfound_card')));
                $this->addError(trans('remise_payment4.extset.admin_order_index.text.warning.notfound_card'),'admin');
                return $this->redirectToRoute('admin_order_page', ['page_no' => $page_no]);
            }

            // プラグイン基本情報の取得
            $Plugin = $this->utilService->getPlugin();
            if(!$Plugin)
            {
                $this->logService->logWarning('Sub Order Edit Extset',
                    Array(trans('remise_payment4.extset.admin_order_index.text.warning.plugin')));
                $this->addWarning(trans('remise_payment4.extset.admin_order_index.text.warning.plugin'),'admin');
                return $this->redirectToRoute('admin_order_page', ['page_no' => $page_no]);
            }

            // プラグイン設定情報の取得
            $Config = $this->utilService->getConfig();
            if(!$Config)
            {
                $this->logService->logWarning('Sub Order Edit Extset',
                    Array(trans('remise_payment4.extset.admin_order_index.text.warning.plugin')));
                $this->addWarning(trans('remise_payment4.extset.admin_order_index.text.warning.plugin'),'admin');
                return $this->redirectToRoute('admin_order_page', ['page_no' => $page_no]);
            }

            // プラグイン設定詳細情報の取得
            $ConfigInfo = $Config->getUnserializeInfo();
            if(!$ConfigInfo)
            {
                $this->logService->logWarning('Sub Order Edit Extset',
                    Array(trans('remise_payment4.extset.admin_order_index.text.warning.plugin')));
                $this->addWarning(trans('remise_payment4.extset.admin_order_index.text.warning.plugin'),'admin');
                return $this->redirectToRoute('admin_order_page', ['page_no' => $page_no]);
            }

            // 拡張セットURL取得
            $url = $ConfigInfo->getExtsetCardUrl();
            // ヘッダ
            $headers = array(
                "User-Agent:" . trans('remise_payment4.extset.common.label.plugin_code') . '_PLG_VER ' . $Plugin->getVersion() . "(SALES)",
            );
            // パラメータ
            $postData = array(
                'SHOPCO'        => $ConfigInfo->getShopco(),
                'HOSTID'        => $ConfigInfo->getExtsetHostid(),
                'S_TORIHIKI_NO' => "",
                'JOB'           => "SALES",
                'TRANID'        => "",
                'RETURL'        => 'https://www.remise.co.jp/',
            );

            $responceResultList = array();
            $totalCount = 0;
            $errorCount = 0;
            foreach ($OrderResultCards as $OrderResultCard) {

                // 返却結果の設定
                $responceResult = Array();

                // 受注情報の取得
                $Order = $this->utilService->getOrder($OrderResultCard->getId());
                if (!$Order)
                {
                    $this->logService->logError('Sub Order Sales Extset',Array("order_id:".$OrderResultCard->getId(),trans('remise_payment4.extset.admin_order_index.text.warning.notfound_card')));
                    $totalCount++;
                    $errorCount++;
                    $responceResult = [
                        "createDate" => $OrderResultCard->getCreateDate()->format('Y-m-d H:i:s'),
                        "orderId" => $OrderResultCard->getId(),
                        "name" => "―",
                        "paymentTotal" => "―",
                        "result" => trans('remise_payment4.extset.admin_order_index.text.warning.notfound_card')
                    ];
                    array_push($responceResultList, $responceResult);
                    continue;
                }
                else
              {
                    $responceResult = [
                      "createDate" => $OrderResultCard->getCreateDate()->format('Y-m-d H:i:s'),
                      "orderId" => $OrderResultCard->getId(),
                      "name" => $Order->getName01().$Order->getName02(),
                      "paymentTotal" => $Order->getPaymentTotal(),
                      "result" => ""
                    ];
                }

                // 仮売上以外はエラーを返却
                if($OrderResultCard->getJob() != 'AUTH')
                {
                    $this->logService->logError('Sub Order Edit Extset',Array("order_id:".$OrderResultCard->getId(),trans('remise_payment4.extset.admin_order_edit.text.warning.not_auth')));
                    $totalCount++;
                    $errorCount++;
                    $responceResult = [
                        "createDate" => $OrderResultCard->getCreateDate()->format('Y-m-d H:i:s'),
                        "orderId" => $OrderResultCard->getId(),
                        "name" => $Order->getName01().$Order->getName02(),
                        "paymentTotal" => $Order->getPaymentTotal(),
                        "result" => trans('remise_payment4.extset.admin_order_edit.text.warning.not_auth')
                    ];
                    array_push($responceResultList, $responceResult);
                    continue;
                }

                // POSTデータの設定
                $postData['S_TORIHIKI_NO'] = $Order->getOrderNo();
                $postData['TRANID'] = $OrderResultCard->getTranid();

                // リクエストデータログ出力
                $this->logService->logInfo('Sub Order Sales Extset -- Request URL', Array($url));
                $this->logService->logInfo('Sub Order Sales Extset -- Request Data', $postData);

                // リクエスト処理
                $retData = $this->extsetRequest($url, $headers, $postData);

                // 接続エラー
                if ($retData === false)
                {
                    // ログ出力
                    $this->logService->logError('Sub Order Sales Extset',Array("order_id:".$OrderResultCard->getId(),trans('remise_payment4.extset.admin_order_index.text.failed.url'), $this->curlError, $this->curlInfo));
                    $totalCount++;
                    $errorCount++;
                    $responceResult["result"] = trans('remise_payment4.extset.admin_order_index.text.failed.url');
                    array_push($responceResultList, $responceResult);
                    continue;
                }
                // 正常
                else
              {
                    // レスポンスデータログ出力
                    $this->logService->logInfo('Sub Order Sales Extset -- Response Data',$retData);

                    if (!isset($retData['X-R_CODE'])  ) $retData['X-R_CODE']   = "9:0000";
                    if (!isset($retData['X-ERRLEVEL'])) $retData['X-ERRLEVEL'] = "";
                    if (!isset($retData['X-ERRCODE']) ) $retData['X-ERRCODE']  = "";

                    // 正常
                    if ($retData['X-R_CODE'] == "0:0000" && $retData['X-ERRLEVEL'] == "0")
                    {
                        // ルミーズカード決済受注情報を「売上済み」に変更
                        $OrderResultCard->setJob("SALES");
                        $OrderResultCard->setPrevTranid($OrderResultCard->getTranid());
                        $OrderResultCard->setPrevPaymentTotal($Order->getPaymentTotal());
                        $OrderResultCard->setTranid($retData['X-TRANID']);
                        $OrderResultCard->setUpdateDate(new \DateTime());
                        $OrderResultCard->setSalesDate(new \DateTime());
                        $this->entityManager->persist($OrderResultCard);
                        $this->entityManager->flush($OrderResultCard);

                        // 受注情報のステータスが新規受付の場合、「入金済み」に変更
                        if($Order->getOrderStatus()->getId() == OrderStatus::NEW)
                        {
                            $this->utilService->setOrderStatusByPaid($Order);
                            $this->entityManager->persist($Order);
                            $this->entityManager->flush($Order);
                        }
                        else
                    {
                            $Order->setPaymentDate(new \DateTime());
                            $this->entityManager->persist($Order);
                            $this->entityManager->flush();
                        }

                        // ログ出力
                        $this->logService->logInfo('Sub Order Sales Extset -- SUCCESS',Array("order_id:".$OrderResultCard->getId()));
                        $totalCount++;
                        $responceResult["result"] = trans('remise_payment4.extset.admin_order_index.text.success.sales');
                        array_push($responceResultList, $responceResult);
                        continue;
                    }
                    // 異常
                    else
                 {
                        // エラーメッセージ取得
                        $errMsg = $this->utilService->getExtsetErrorMassage("SALES",$retData['X-R_CODE']);
                        // ログ出力
                        $this->logService->logError('Sub Order Sales Extset',Array("order_id:".$OrderResultCard->getId(),$errMsg));
                        $totalCount++;
                        $errorCount++;
                        $responceResult["result"] = $errMsg;
                        array_push($responceResultList, $responceResult);
                        continue;
                    }
                }
            }

            // 入力フォーム（空）
            $form = $this->createForm(SubOrderIndexResultType::class);

            return [
                'form' => $form->createView(),
                'totalCount' => $totalCount,
                'errorCount' => $errorCount,
                'responceResultList' => $responceResultList,
            ];
        }
        catch (\Exception $e)
        {
            // ログ出力
            $this->logService->logError('Sub Order Sales Extset',
                Array(trans('remise_payment4.extset.admin_order_index.text.failed.sales'),
                    'ErrCode:' . $e->getCode(),
                    'ErrMessage:' . $e->getMessage()));
            $this->addError(trans('remise_payment4.extset.admin_order_index.text.failed.sales'),'admin');

            if(isset($page_no))
            {
                return $this->redirectToRoute('admin_order_page', ['page_no' => $page_no]);
            }
            else
          {
                return $this->redirectToRoute('admin_order_page', ['page_no' => "1"]);
            }
        }
    }

    /**
     * 一括売上処理入力チェック
     *
     * @param  $requestData
     *
     */
    private function inputCheckSales($requestData)
    {
        // 拡張セット以外の呼出の場合、リターン
        if(!array_key_exists('remise_option_extset',$requestData))
        {
            $this->logService->logWarning('Sub Order Sales Extset',
                Array(trans('remise_payment4.extset.admin_order_index.text.warning.bat_request'),'remise_option_extset'));
            $this->addWarning(trans('remise_payment4.extset.admin_order_index.text.warning.bat_request'),'admin');
            return false;
        }

        if(!array_key_exists('order_ids',$requestData['remise_option_extset']))
        {
            $this->logService->logWarning('Sub Order Sales Extset',
                Array(trans('remise_payment4.extset.admin_order_index.text.warning.bat_request'),'order_ids'));
            $this->addWarning(trans('remise_payment4.extset.admin_order_index.text.warning.bat_request'),'admin');
            return false;
        }

        return true;
    }

    /**
     * リクエスト処理
     *
     * @param  $url
     * @param  $headers
     * @param  $postData
     *
     */
    private function extsetRequest($url, $headers, $postData)
    {
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // ヘッダ
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        // パラメータ
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postData));

        // 実行
        $this->curlResponse  = curl_exec($curl);
        $this->curlInfo  = curl_getinfo($curl);
        $this->curlError = curl_error($curl);

        // 終了
        curl_close($curl);

        // 接続エラー
        if ($this->curlResponse === false)
        {
            return false;
        }
        // 正常
        else
       {
            // 戻りデータ解析
            $this->curlResponse  = mb_convert_encoding($this->curlResponse, 'UTF-8', 'Shift-JIS');

            $crawler = new Crawler();
            $crawler->addHTMLContent($this->curlResponse);
            $Elements = $crawler->filter('input');

            // 結果判定
            $retData = array();
            foreach ($Elements as $node) {
                if (!$node->attributes) continue;
                $nm = "";
                $val = "";
                if ($node->attributes->getNamedItem('name')) {
                    $nm = $node->attributes->getNamedItem('name')->nodeValue;
                }
                if ($node->attributes->getNamedItem('value')) {
                    $val = $node->attributes->getNamedItem('value')->nodeValue;
                }
                if (!empty($nm)) {
                    $retData[$nm] = $val;
                }
            }

            return $retData;
        }
    }
}
