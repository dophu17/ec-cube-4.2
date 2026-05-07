<?php

namespace Plugin\RemisePayment42\Service;

use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Exception\ShoppingException;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Service\Payment\PaymentDispatcher;
use Eccube\Service\Payment\PaymentMethodInterface;
use Eccube\Service\Payment\PaymentResult;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Plugin\RemisePayment42\Service\LogService;
use Plugin\RemisePayment42\Service\UtilService;

/**
 * ルミーズ決済処理
 */
class PaymentService implements PaymentMethodInterface
{
    /**
     * @var Order
     */
    protected $Order;

    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var ParameterBag
     */
    protected $parameterBag;

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
     * コンストラクタ
     *
     * @param ParameterBag $parameterBag
     * @param PurchaseFlow $shoppingPurchaseFlow
     * @param UtilService $utilService
     * @param LogService $logService
     */
    public function __construct(
        ParameterBag $parameterBag,
        PurchaseFlow $shoppingPurchaseFlow,
        UtilService $utilService,
        LogService $logService
    ) {
        $this->parameterBag = $parameterBag;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->utilService = $utilService;
        $this->logService = $logService;
    }

    /**
     * 注文手続き画面でsubmitされた時に実行する処理を実装
     * ※主にクレジットカード決済の有効性チェックをするために使用
     *   PaymentResult には、実行結果、エラーメッセージなどを設定
     *   Response を設定して、他の画面にリダイレクトしたり、独自の出力を実装することも可能
     *
     * @return PaymentResult
     */
    public function verify()
    {
        $result = new PaymentResult();
        $result->setSuccess(true);

        return $result;
    }

    /**
     * 注文確認画面でsubmitされた時に、他の Controller へ処理を移譲する実装
     * ※リンク型決済や、キャリア決済など、決済会社の画面へ遷移する必要がある場合に使用
     *   また、独自に作成した Controller に遷移する場合にも使用可
     *   PaymentDispatcher は、他の Controller へ Redirect もしくは Forward させるための情報を設定
     *   決済会社の画面など、サイト外へ遷移させる場合は、 Response を設定
     *   このメソッドでは購入処理中の受注データを仮確定させるために PurchaseFlow#prepare() を実行
     *   仮確定してサイト外へ遷移した後に、遷移先でユーザが決済をキャンセルした場合は、仮確定を取り消す
     *   仮確定の取り消しには、まずサイト外から決済をキャンセルして戻ってくるためのControllerを用意
     *   このControllerの処理の中で PurchaseFlow#rollback() を実行
     *   仮確定取消後は注文手続き画面に遷移させる
     *   サイト外へ遷移後、ユーザが決済処理を完了したときは、決済手続き完了用のControllerに遷移
     *   このタイミングで購入処理中の受注を確定できる場合は PurchaseFlow#commit() を実行
     *   この遷移タイミングではなく、決済サーバからの完了通知を受けて購入処理を確定する場合は、完了通知用のControllerを実装し PurchaseFlow#commit() を実行
     *
     * @return PaymentDispatcher
     */
    public function apply()
    {
        // 受注情報のステータスを「決済処理中」に変更
        $this->logService->logInfo('Payment -- Save Order Status(' . $this->Order->getOrderNo() . ')');
        $this->Order = $this->utilService->setOrderStatusByPending($this->Order);

        // 購入処理中の受注データを仮確定
        $this->logService->logInfo('Payment -- Prepare Order (' . $this->Order->getOrderNo() . ')');
        $this->purchaseFlow->prepare($this->Order, new PurchaseContext());

        $this->parameterBag->set('remise.Order', $this->Order);

        // 決済画面へリダイレクト
        $dispatcher = new PaymentDispatcher();
        $dispatcher->setRoute('remise_payment');
        $dispatcher->setForward(true);

        return $dispatcher;
    }

    /**
     * 注文確認画面でsubmitされた時に決済完了処理を記載
     * 注文確認画面でsubmitされた時に決済完了処理を記載
     * ※PaymentResult には、実行結果、エラーメッセージなどを設定
     *   3Dセキュア決済の場合は、 Response を設定して、独自の出力を実装することも可能
     *   決済処理をこのメソッドで完了できる場合は、PurchaseFlow#commit() を実行して購入処理中の受注データを確定
     *   3Dセキュア決済などで他サイトへの遷移が必要な場合は、他サイトでの処理を完了して呼び戻されるControllerの中で PurchaseFlow#commit() を実行
     *   他サイトでの処理がキャンセルされたときは PurchaseFlow#rollback() を呼び出す
     *
     * @return PaymentResult
     */
    public function checkout()
    {
        $result = new PaymentResult();
        $result->setSuccess(true);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setOrder(Order $Order)
    {
        $this->Order = $Order;
    }

    /**
     * {@inheritdoc}
     */
    public function setFormType(FormInterface $form)
    {
        $this->form = $form;
    }
}
