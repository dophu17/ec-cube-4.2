<?php

namespace Plugin\RemisePayment42\Controller;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Entity\Payment;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Plugin\RemisePayment42\Entity\OrderResult;
use Plugin\RemisePayment42\Entity\OrderResultCard;
use Plugin\RemisePayment42\Entity\Payquick;
use Plugin\RemisePayment42\Entity\RemisePayment;
use Plugin\RemisePayment42\Service\LogService;
use Plugin\RemisePayment42\Service\UtilService;
use Plugin\RemisePayment42\Service\AcService;
use Plugin\RemisePayment42\Repository\RemiseACMemberRepository;

/**
 * 結果通知用
 */
class ResultController extends AbstractController
{
    /**
     * @var PurchaseFlow
     */
    private $purchaseFlow;

    /**
     * @var UtilService
     */
    protected $utilService;

    /**
     * @var LogService
     */
    protected $logService;

    /**
     * @var AcService
     */
    protected $acService;

    /**
     *
     * @var RemiseACMemberRepository
     */
    protected $remiseACMemberRepository;

    /**
     * コンストラクタ
     *
     * @param PurchaseFlow $shoppingPurchaseFlow
     * @param UtilService $utilService
     * @param LogService $logService
     * @param AcService $acService
     * @param RemiseACMemberRepository $remiseACMemberRepository
     */
    public function __construct(
        PurchaseFlow $shoppingPurchaseFlow,
        UtilService $utilService,
        LogService $logService,
        AcService $acService,
        RemiseACMemberRepository $remiseACMemberRepository
    ) {
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->utilService = $utilService;
        $this->logService = $logService;
        $this->acService = $acService;
        $this->remiseACMemberRepository = $remiseACMemberRepository;
    }

    /**
     * 結果通知
     *
     * @param  $request
     *
     * @Route("/remise_payment4_result", name="remise_payment4_result")
     */
    public function result(Request $request)
    {
        $this->logService->logInfo('Payment Card Result');
        $ConfigInfo = $this->utilService->getConfigInfo();

        $requestData = $request->request->all();

        $this->logService->logInfo('Payment Card Result -- Request', $requestData);

        // リクエストデータなし
        $tranid = $this->utilService->getValue($requestData, 'X-TRANID');
        if (empty($tranid)) {
            $this->logService->logError('Payment Card Result -- Request Error');
            return new Response('REQUEST_ERROR');
        }
        // 戻り区分不正
        if ($this->utilService->getValue($requestData, 'REC_TYPE') != 'RET') {
            $this->logService->logError('Payment Card Result -- REC_TYPE Error');
            return new Response('REC_TYPE_ERROR:' . $this->utilService->getValue($requestData, 'REC_TYPE'));
        }

        // 受注番号
        $orderNo = $this->utilService->getValue($requestData, 'X-S_TORIHIKI_NO');
        // 金額
        $paymentTotal = $this->utilService->getValue($requestData, 'X-TOTAL');

        // 受注情報
        // $Order = $this->utilService->getOrderByPending($orderNo);
        $Order = $this->utilService->getOrderByOrderNo($orderNo);

        if (!$Order) {
            $this->logService->logError('Payment Card Result -- Not Found Order (' . $orderNo . ')');
            return new Response('NOT_FOUND_ORDER:' . $orderNo);
        }

        // EC-CUBE支払方法の取得
        $Payment = $Order->getPayment();

        if (!$Payment) {
            $this->logService->logError('Payment Card Result -- Not Found Payment (' . $orderNo . ')');
            return new Response('NOT_FOUND_PAYMENT:' . $orderNo);
        }

        // 支払方法
        $paymentId = $Payment->getId();

        // ルミーズ支払方法の取得
        $RemisePayment = $this->utilService->getRemisePayment($paymentId);

        if (!$RemisePayment) {
            $this->logService->logError('Payment Card Result -- Unmatch Payment (' . $orderNo . ')');
            return new Response('UNMATCH_PAYMENT:' . $orderNo . '(' . $paymentId . ')');
        }

        // 決済履歴の取得
        $OrderResult = $this->utilService->getOrderResult($Order->getId());

        if (!$OrderResult) {
            $this->logService->logError('Payment Card Result -- Not Found OrderResult (' . $orderNo . ')');
            return new Response('NOT_FOUND_ORDER_RESULT:' . $orderNo);
        }

        // カード決済履歴詳細の取得
        $OrderResultCard = $this->utilService->getOrderResultCard($Order->getId());

        if (!$OrderResultCard) {
            $this->logService->logError('Payment Card Result -- Not Found OrderResultCard (' . $orderNo . ')');
            return new Response('NOT_FOUND_ORDER_RESULT_CARD:' . $orderNo);
        }

        // 金額の相違
        if(!($ConfigInfo && $ConfigInfo->useOptionAC() && $OrderResultCard->getMemberId())) {
            // カード情報更新ではない場合
            if (round($Order->getPaymentTotal()) != $paymentTotal) {
                $this->logService->logError('Payment Card Result -- Unmatch Total (' . $orderNo . ', EC-CUBE:' . round($Order->getPaymentTotal()) . ', REMISE:' . $paymentTotal . ')');
                return new Response('UNMATCH_TOTAL:' . $orderNo . '(EC-CUBE=' . round($Order->getPaymentTotal()) . ',REMISE=' . $paymentTotal . ')');
            }
        }

        // 在庫数チェック

        // 結果取得
        $rCode = $this->utilService->getValue($requestData, 'X-R_CODE');
        $errcode = $this->utilService->getValue($requestData, 'X-ERRCODE');
        $errinfo = $this->utilService->getValue($requestData, 'X-ERRINFO');
        $errlevel = $this->utilService->getValue($requestData, 'X-ERRLEVEL');
        if (!empty($errcode)) $errcode = trim($errcode);

        // 結果判定
        if ($rCode != trans('remise_payment4.common.label.r_code.success')
         || $errcode !== ""
         || $errinfo != trans('remise_payment4.common.label.errinfo.success')
         || $errlevel != trans('remise_payment4.common.label.errlevel.success')) {
            return new Response(trans('remise_payment4.common.label.result.card.success'));
        }

        if($this->utilService->getOrderByPending($orderNo)) {
            // 購入処理中の受注情報の場合（通常の決済処理の場合）

            // 決済履歴の設定
            $OrderResult->setUpdateDate(new \DateTime());

            // 決済履歴の登録
            $this->logService->logInfo('Payment Card Result -- Save OrderResult (' . $orderNo . ')');
            $this->entityManager->persist($OrderResult);
            $this->entityManager->flush($OrderResult);

            // 決済履歴詳細の設定
            $OrderResultCard->setState      (trans('remise_payment4.common.label.card.state.result')     );
            $OrderResultCard->setTranid     ($this->utilService->getValue($requestData, 'X-TRANID'      ));
            $OrderResultCard->setRCode      ($this->utilService->getValue($requestData, 'X-R_CODE'      ));
            $OrderResultCard->setPayquickId ($this->utilService->getValue($requestData, 'X-PAYQUICKID'  ));
            $OrderResultCard->setCardParts  ($this->utilService->getValue($requestData, 'X-PARTOFCARD'  ));
            $OrderResultCard->setExpire     ($this->utilService->getValue($requestData, 'X-EXPIRE'      ));
            $OrderResultCard->setName       ($this->utilService->getValue($requestData, 'X-NAME'        ));
            $OrderResultCard->setCardBrand  ($this->utilService->getValue($requestData, 'X-CARDBRAND'   ));
            $OrderResultCard->setMemberId   ($this->utilService->getValue($requestData, 'X-AC_MEMBERID' ));
            $OrderResultCard->setAcTotal    ($this->utilService->getValue($requestData, 'X-AC_TOTAL'    ));
            $OrderResultCard->setAcNextDate ($this->utilService->getDate ($requestData, 'X-AC_NEXT_DATE'));
            $OrderResultCard->setAcInterval ($this->utilService->getValue($requestData, 'X-AC_INTERVAL' ));
            $OrderResultCard->setResultDate(new \DateTime());
            $OrderResultCard->setUpdateDate(new \DateTime());

            // カード決済履歴詳細の登録
            $this->logService->logInfo('Payment Card Result -- Save OrderResultCard (' . $orderNo . ')');
            $this->entityManager->persist($OrderResultCard);
            $this->entityManager->flush($OrderResultCard);

            // 新規受付に更新
            $this->logService->logInfo('Payment Card Result -- Save Order (' . $orderNo . ')');
            $Order = $this->utilService->setOrderStatusByNew($Order);
            $this->entityManager->persist($Order);

            // 購入処理中の受注を確定
            $this->logService->logInfo('Payment Card Result -- Commit Order (' . $orderNo . ')');
            $this->purchaseFlow->commit($Order, new PurchaseContext());

            $this->entityManager->flush();

            // 会員の購入処理でペイクイックを利用する場合
            if ($Order->getCustomer() && $OrderResultCard->getPayquickId() != "") {
                // ペイクイック情報の取得
                $Payquick = $this->utilService->createPayquick($Order->getCustomer());

                // ペイクイック情報の設定
                $Payquick->setPrePayquickId ($Payquick->getPayquickId()         );
                $Payquick->setPreCardParts  ($Payquick->getCardParts()          );
                $Payquick->setPreExpire     ($Payquick->getExpire()             );
                $Payquick->setPreName       ($Payquick->getName()               );
                $Payquick->setPreCardBrand  ($Payquick->getCardBrand()          );
                $Payquick->setPayquickId    ($OrderResultCard->getPayquickId()  );
                $Payquick->setCardParts     ($OrderResultCard->getCardParts()   );
                $Payquick->setExpire        ($OrderResultCard->getExpire()      );
                $Payquick->setName          ($OrderResultCard->getName()        );
                $Payquick->setCardBrand     ($OrderResultCard->getCardBrand()   );
                $Payquick->setUpdateDate(new \DateTime());

                // ペイクイック情報の登録
                $this->logService->logInfo('Payment Card Result -- Save Payquick (' . $orderNo . ')');
                $this->entityManager->persist($Payquick);
                $this->entityManager->flush($Payquick);
            }

            // 定期購買が有効の場合
            if ($ConfigInfo && $ConfigInfo->useOptionAC() && $OrderResultCard->getMemberId()) {
                // ルミーズ定期購買情報作成
                $remiseACMember = $this->remiseACMemberRepository->findOneBy([
                    'id' => $OrderResultCard->getMemberId()
                ]);
                $remiseACMember = $this->acService->createRemiseACMember($Order, $remiseACMember);
                $remiseACMember->setId($OrderResultCard->getMemberId());
                $remiseACMember->setCardparts($OrderResultCard->getCardParts());
                $remiseACMember->setExpire($OrderResultCard->getExpire());

                // ルミーズ定期購買メンバー情報登録
                $this->logService->logInfo('Payment Card Result -- Save RemiseACMember (' . $OrderResultCard->getMemberId() . ')');
                $this->entityManager->persist($remiseACMember);
                $this->entityManager->flush($remiseACMember);

                // ルミーズ定期購買結果情報作成
                $remiseACResult = $this->acService->createRemiseACResult($Order->getId(),trans('remise_payment4.ac.plg_remise_payment4_remise_ac_result.result.first.key'));

                // ルミーズ定期購買結果情報登録
                $this->logService->logInfo('Payment Card Result -- Save RemiseACResult (' . $orderNo . ')');
                $this->entityManager->persist($remiseACResult);
                $this->entityManager->flush($remiseACResult);
            }
        } else if ($ConfigInfo && $ConfigInfo->useOptionAC() && $OrderResultCard->getMemberId()) {
            // カード情報更新の場合、かつ、定期購買が有効の場合
            // 決済履歴詳細の設定
            // $OrderResultCard->setTranid     ($this->utilService->getValue($requestData, 'X-TRANID'      ));
            // $OrderResultCard->setRCode      ($this->utilService->getValue($requestData, 'X-R_CODE'      ));
            $OrderResultCard->setCardParts  ($this->utilService->getValue($requestData, 'X-PARTOFCARD'  ));
            $OrderResultCard->setExpire     ($this->utilService->getValue($requestData, 'X-EXPIRE'      ));
            $OrderResultCard->setName       ($this->utilService->getValue($requestData, 'X-NAME'        ));
            $OrderResultCard->setCardBrand  ($this->utilService->getValue($requestData, 'X-CARDBRAND'   ));
            $OrderResultCard->setAcNextDate ($this->utilService->getDate ($requestData, 'X-AC_NEXT_DATE'));
            // $OrderResultCard->setResultDate(new \DateTime());
            $OrderResultCard->setUpdateDate(new \DateTime());

            // カード決済履歴詳細の登録
            $this->logService->logInfo('Payment Card Result -- Save OrderResultCard (' . $orderNo . ')');
            $this->entityManager->persist($OrderResultCard);
            $this->entityManager->flush($OrderResultCard);

            // ルミーズ定期購買メンバー情報作成
            $remiseACMember = $this->remiseACMemberRepository->findOneBy([
                'id' => $OrderResultCard->getMemberId()
            ]);
            $remiseACMember->setCardparts($OrderResultCard->getCardParts());
            $remiseACMember->setExpire($OrderResultCard->getExpire());
            $remiseACMember->setNextDate($OrderResultCard->getAcNextDate());
            $remiseACMember->setUpdateDate(new \DateTime());

            // ルミーズ定期購買メンバー情報登録
            $this->logService->logInfo('Payment Card Result -- Save RemiseACMember (' . $OrderResultCard->getMemberId() . ')');
            $this->entityManager->persist($remiseACMember);
            $this->entityManager->flush($remiseACMember);
        } else {
            // 受注情報無しと判断する
            $this->logService->logError('Payment Card Result -- Not Found Order (' . $orderNo . ')');
            return new Response('NOT_FOUND_ORDER:' . $orderNo);
        }

        // 結果通知の応答
        $this->logService->logInfo('Payment Card Result -- Done');
        return new Response(trans('remise_payment4.common.label.result.card.success'));
    }

    /**
     * 受注未確定の解除
     *
     * @param  $request
     * @param  string $id 受注ID
     *
     * @Route("/%eccube_admin_route%/order/{id}/delete_result_state", requirements={"id"="\d+"}, name="remise_payment4_admin_delete_result_state")
     */
    public function deleteResultStateForAdmin(Request $request, $id = null)
    {
        $this->logService->logInfo('Order Delete Result State');

        $this->isTokenValid();

        if (empty($id))
        {
            $this->logService->logError('Order Delete Result State -- Empty Id');
            return $this->redirectToRoute('admin_order_edit', ['id' => $id]);
        }

        // 受注情報の取得
        $Order = $this->utilService->getOrder($id);

        if (!$Order)
        {
            $this->logService->logError('Order Delete Result State -- Not Found Order (' . $id . ')');
            return $this->redirectToRoute('admin_order_edit', ['id' => $id]);
        }

        // 決済履歴詳細の取得
        $OrderResultCard = $this->utilService->getOrderResultCard($Order->getId());

        if (!$OrderResultCard)
        {
            $this->logService->logError('Order Delete Result State -- Not Found OrderResultCard (' . $id . ')');
            return $this->redirectToRoute('admin_order_edit', ['id' => $id]);
        }

        // カード決済履歴詳細の設定
        $OrderResultCard->setState(trans('remise_payment4.common.label.card.state.result.deleted'));
        $OrderResultCard->setUpdateDate(new \DateTime());

        // カード決済履歴詳細の登録
        $this->logService->logInfo('Order Delete Result State -- Save OrderResultCard (' . $id . ')');
        $this->entityManager->persist($OrderResultCard);
        $this->entityManager->flush($OrderResultCard);

        $this->logService->logInfo('Order Delete Result State -- Done');
        $this->addSuccess(trans('remise_payment4.admin_order_edit.text.card.state.result.delete.completed'), 'admin');
        return $this->redirectToRoute('admin_order_edit', ['id' => $id]);
    }
}
