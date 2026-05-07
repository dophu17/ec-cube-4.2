<?php

namespace Plugin\RemisePayment42\Controller;

use Eccube\Common\Constant;
use Eccube\Common\EccubeConfig;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Entity\Payment;
use Eccube\Entity\Plugin;
use Eccube\Service\CartService;
use Eccube\Service\MailService;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Eccube\Repository\OrderItemRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpFoundation\RequestStack;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Plugin\RemisePayment42\Entity\Config;
use Plugin\RemisePayment42\Entity\ConfigInfo;
use Plugin\RemisePayment42\Entity\OrderResult;
use Plugin\RemisePayment42\Entity\OrderResultCard;
use Plugin\RemisePayment42\Entity\OrderResultCvs;
use Plugin\RemisePayment42\Entity\Payquick;
use Plugin\RemisePayment42\Entity\RemisePayment;
use Plugin\RemisePayment42\Entity\RemiseACMember;
use Plugin\RemisePayment42\Service\LogService;
use Plugin\RemisePayment42\Service\UtilService;
use Plugin\RemisePayment42\Service\AcService;
use Plugin\RemisePayment42\Repository\RemiseACMemberRepository;
use Plugin\RemisePayment42\Repository\RemiseTaxRateRepository;
use Plugin\RemisePayment42\Repository\Emv3dsRepository;

/**
 * 決済処理用
 */
class PaymentController extends AbstractController
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var RequestStack
     */
    protected $session;

    /**
     * @var ParameterBag
     */
    protected $parameterBag;

    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * @var MailService
     */
    protected $mailService;

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
     * @var RemiseTaxRateRepository
     */
    protected $remiseTaxRateRepository;

    /**
     *
     * @var OrderItemRepository
     */
    protected $orderItemRepository;

    /**
     *
     * @var Emv3dsRepository
     */
    private $emv3dsRepository;

    /**
     * コンストラクタ
     *
     * @param EccubeConfig $eccubeConfig
     * @param RequestStack $session
     * @param ParameterBag $parameterBag
     * @param CartService $cartService
     * @param MailService $mailService
     * @param PurchaseFlow $shoppingPurchaseFlow
     * @param UtilService $utilService
     * @param LogService $logService
     * @param AcService $acService
     * @param RemiseACMemberRepository $remiseACMemberRepository
     * @param RemiseTaxRateRepository $remiseTaxRateRepository
     * @param OrderItemRepository $orderItemRepository
     * @param Emv3dsRepository $emv3dsRepository
     */
    public function __construct(
        EccubeConfig $eccubeConfig,
        RequestStack $session,
        ParameterBag $parameterBag,
        CartService $cartService,
        MailService $mailService,
        PurchaseFlow $shoppingPurchaseFlow,
        UtilService $utilService,
        LogService $logService,
        AcService $acService,
        RemiseACMemberRepository $remiseACMemberRepository,
        RemiseTaxRateRepository $remiseTaxRateRepository,
        OrderItemRepository $orderItemRepository,
        Emv3dsRepository $emv3dsRepository
        ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->session = $session;
        $this->parameterBag = $parameterBag;
        $this->cartService = $cartService;
        $this->mailService = $mailService;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->utilService = $utilService;
        $this->logService = $logService;
        $this->acService = $acService;
        $this->remiseACMemberRepository = $remiseACMemberRepository;
        $this->remiseTaxRateRepository = $remiseTaxRateRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->emv3dsRepository = $emv3dsRepository;
    }

    /**
     * 決済画面の呼び出し
     *
     * @param  $request
     *
     * @Route("/shopping/remise_payment", name="remise_payment")
     * @Template("@RemisePayment42/redirect.twig")
     */
    public function payment(Request $request)
    {
        $this->logService->logInfo('Payment');

        // 受注情報の取得
        $Order = $this->parameterBag->get('remise.Order');

        if (!$Order)
        {
            $this->logService->logError('Payment -- Not Found Order');
            $this->addError(trans('remise_payment4.front.text.notfound.order'));
            return $this->redirectToRoute('shopping');
        }

        $orderNo = $Order->getOrderNo();

        if ($this->getUser() != $Order->getCustomer())
        {
            $this->logService->logError('Payment -- Unmatch Customer (' . $orderNo . ')');
            $this->addError(trans('remise_payment4.front.text.notfound.order'));
            return $this->redirectToRoute('shopping');
        }

        // EC-CUBE支払方法の取得
        $Payment = $Order->getPayment();

        if (!$Payment)
        {
            $this->logService->logError('Payment -- Not Found Payment (' . $orderNo . ')');
            $this->addError(trans('remise_payment4.front.text.invalid.payment'));
            return $this->redirectToRoute('shopping');
        }

        $paymentId = $Payment->getId();

        // ルミーズ支払方法の取得
        $RemisePayment = $this->utilService->getRemisePayment($paymentId);

        if (!$RemisePayment)
        {
            $this->logService->logError('Payment -- Unmatch Payment (' . $orderNo . ')');
            $this->addError(trans('remise_payment4.front.text.invalid.payment'));
            return $this->redirectToRoute('shopping');
        }

        // プラグイン設定情報の取得
        $ConfigInfo = $this->utilService->getConfigInfo();

        if (!$ConfigInfo)
        {
            $this->logService->logError('Payment -- Not Found Config');
            $this->addError(trans('remise_payment4.front.text.unsetting.plugin'));
            return $this->redirectToRoute('shopping');
        }

        $sendUrl = '';
        $sendData = array();

        // 決済履歴の生成
        $OrderResult = $this->utilService->createOrderResult($Order);
        $OrderResult->setRequestDate(new \DateTime());
        $OrderResult->setUpdateDate(new \DateTime());

        $OrderResultCard = null;
        $OrderResultCvs = null;

        // カード決済
        if ($RemisePayment->getKind() == trans('remise_payment4.common.label.payment.kind.card'))
        {
            // 決済情報送信先URL
            $sendUrl = $ConfigInfo->getCardUrl();

            // カード決済用送信データの取得
            $sendData = $this->getCardSendData($Order, $ConfigInfo);

            // 定期購買が有効の場合
            if($ConfigInfo->useOptionAC())
            {
                // カード決済用送信データの取得（定期購買）
                $sendData = $this->getCardSendDataAC($Order, $sendData);
            }

            // カード決済用送信データの取得（EMV3Dセキュア）
            $sendData = $this->getCardSendDataEMV3DS($Order, $sendData);

            // 決済履歴の設定
            $OrderResult->setKind(trans('remise_payment4.common.label.payment.kind.card'));

            // カード決済履歴詳細の生成
            $OrderResultCard = $this->utilService->createOrderResultCard($Order);
            $OrderResultCard->setUsePayquickId($sendData['PAYQUICKID']);
            $OrderResultCard->setState(trans('remise_payment4.common.label.card.state.payment'));
            $OrderResultCard->setJob($sendData['JOB']);
            $OrderResultCard->setUpdateDate(new \DateTime());

            // マルチ決済履歴詳細が存在する場合
            $OrderResultCvs = $this->utilService->getOrderResultCvs($Order->getId());
            if ($OrderResultCvs)
            {
                // マルチ決済履歴詳細を削除
                $this->utilService->deleteOrderResultCvs($OrderResultCvs);
                $this->entityManager->flush();
                $OrderResultCvs = null;
            }
        }
        // マルチ決済
        else if ($RemisePayment->getKind() == trans('remise_payment4.common.label.payment.kind.cvs'))
        {
            // 決済情報送信先URL
            $sendUrl = $ConfigInfo->getCvsUrl();

            // マルチ決済用送信データの取得
            $sendData = $this->getCvsSendData($Order, $ConfigInfo);

            // 決済履歴の設定
            $OrderResult->setKind(trans('remise_payment4.common.label.payment.kind.cvs'));

            // マルチ決済履歴詳細の生成
            $OrderResultCvs = $this->utilService->createOrderResultCvs($Order);
            $OrderResultCvs->setUpdateDate(new \DateTime());

            // カード決済履歴詳細が存在する場合
            $OrderResultCard = $this->utilService->getOrderResultCard($Order->getId());
            if ($OrderResultCard)
            {
                // カード決済履歴詳細を削除
                $this->utilService->deleteOrderResultCard($OrderResultCard);
                $this->entityManager->flush();
                $OrderResultCard = null;
            }
        }

        // 決済履歴の登録
        $this->logService->logInfo('Payment -- Save OrderResult (' . $orderNo . ')');
        $this->entityManager->persist($OrderResult);
        $this->entityManager->flush($OrderResult);

        // カード決済履歴詳細の登録
        if (!empty($OrderResultCard))
        {
            $this->logService->logInfo('Payment -- Save OrderResultCard (' . $orderNo . ')');
            $this->entityManager->persist($OrderResultCard);
            $this->entityManager->flush($OrderResultCard);
        }
        // マルチ決済履歴詳細の登録
        if (!empty($OrderResultCvs))
        {
            $this->logService->logInfo('Payment -- Save OrderResultCvs (' . $orderNo . ')');
            $this->entityManager->persist($OrderResultCvs);
            $this->entityManager->flush($OrderResultCvs);
        }

        $this->logService->logInfo('Payment -- Done');
        return [
            'send_url'  => $sendUrl,
            'send_data' => $sendData,
        ];
    }

    /**
     * カード決済用送信データの取得
     *
     * @param  $Order  受注情報
     * @param  $ConfigInfo  設定情報
     *
     * @return  $sendData  送信データ
     */
    private function getCardSendData($Order, $ConfigInfo)
    {
        // プラグイン情報の取得
        $Plugin = $this->utilService->getPlugin();

        $exitUrl = $this->generateUrl('remise_payment4_back', ['no' => $Order->getOrderNo()], UrlGeneratorInterface::ABSOLUTE_URL);
        $retUrl = $this->generateUrl('remise_payment4_result_card', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $ngRetUrl = $this->generateUrl('remise_payment4_result_card', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $method = '';
        $ptimes = '';
        $payquick = '';
        $payquickId = '';

        // 会員の購入処理でペイクイック利用の場合
        if ($Order->getCustomer() && $ConfigInfo->getPayquickFlag() === '1')
        {
            // セッションから取得
            //   ペイクイック利用
            $work = $this->session->get('remise_payment4.use_payquick.' . $Order->getId());
            if (!is_null($work) && $work == '1')
            {
                // ペイクイック情報の取得
                $Payquick = $this->utilService->getPayquickByCustomer($Order->getCustomer());
                if ($Payquick)
                {
                    $payquickId = $Payquick->getPayquickId();
                }
            }
            //   支払方法
            $work = $this->session->get('remise_payment4.method.' . $Order->getId());
            if (!is_null($work))
            {
                $method = $work;
            }
            //   分割回数
            if ($method == '61')
            {
                $work = $this->session->get('remise_payment4.ptimes.' . $Order->getId());
                if (!is_null($work))
                {
                    $ptimes = $work;
                }
            }
            //   クレジットカード登録
            $work = $this->session->get('remise_payment4.regist_payquick.' . $Order->getId());
            if (!is_null($work) && $work == '1')
            {
                $payquick = '1';
            }
        }

        $sendData = array();
        $sendData['ECCUBE_VER']     = Constant::VERSION;            // EC-CUBEバージョン番号
        if ($Plugin)
        {
            $sendData[$Plugin->getCode() . '_PLG_VER'] = $Plugin->getVersion(); // ルミーズ決済プラグインバージョン番号
        }
        $sendData['SHOPCO']         = $ConfigInfo->getShopco();     // 加盟店コード
        $sendData['HOSTID']         = $ConfigInfo->getHostid();     // ホストID
        $sendData['S_TORIHIKI_NO']  = $Order->getOrderNo();         // 請求番号
        $sendData['JOB']            = $ConfigInfo->getJob();        // 処理区分
        $sendData['CARD']           = '';                           // カード番号、セキュリティコード
        $sendData['EXPIRE']         = '';                           // 有効期限
        $sendData['NAME']           = '';                           // 名義人
        $sendData['MAIL']           = $Order->getEmail();           // e-mail
        $sendData['FORWARD']        = '';                           // 仕向先コード
        $sendData['ITEM']           = '0000120';                    // 商品コード
        $sendData['AMOUNT']         = round($Order->getPaymentTotal()); // 金額
        $sendData['TAX']            = '0';                          // 税送料
        $sendData['TOTAL']          = round($Order->getPaymentTotal()); // 合計金額
        $sendData['METHOD']         = $method;                      // 支払区分
        $sendData['PTIMES']         = $ptimes;                      // 分割回数
        $sendData['BTIMES']         = '';                           // ボーナス回数
        $sendData['BAMOUNT']        = '';                           // ボーナス金額
        $sendData['EXITURL']        = $exitUrl;                     // 中止URL
        $sendData['RETURL']         = $retUrl;                      // 完了通知URL
        $sendData['DIRECT']         = 'OFF';                        // ダイレクトモード
        $sendData['OPT']            = '';                           // オプション
        $sendData['NG_RETURL']      = $ngRetUrl;                    // NG完了通知URL
        $sendData['PAYQUICK']       = $payquick;                    // ペイクイック機能
        $sendData['PAYQUICKID']     = $payquickId;                  // ペイクイックID
        $sendData['REMARKS3']       = 'A0000155';                   // 代理店コード
        if ($Order->getCustomer())
        {
            $sendData['REMARKS5']   = $Order->getCustomer()->getId();
        }
        $sendData['AUTOCHARGE']     = '';                           // 自動継続課金
        $sendData['AC_MEMBERID']    = '';                           // メンバーID
        $sendData['AC_S_KAIIN_NO']  = '';                           // 加盟店会員番号
        $sendData['AC_NAME']        = '';                           // 会員名
        $sendData['AC_KANA']        = '';                           // 会員名カナ
        $sendData['AC_TEL']         = '';                           // 会員電話番号
        $sendData['AC_AMOUNT']      = '';                           // 金額
        $sendData['AC_TAX']         = '';                           // 税送料
        $sendData['AC_TOTAL']       = '';                           // 合計金額
        $sendData['AC_NEXT_DATE']   = '';                           // 次回課金日
        $sendData['AC_INTERVAL']    = '';                           // 決済間隔
        $sendData['AC_COUNT']       = '';                           // 決済回数

        $this->logService->logInfo('Payment -- Create Card Info', [
            'S_TORIHIKI_NO' => $sendData['S_TORIHIKI_NO'],
            'JOB'           => $sendData['JOB'],
            'MAIL'          => $sendData['MAIL'],
            'AMOUNT'        => $sendData['AMOUNT'],
            'TAX'           => $sendData['TAX'],
            'TOTAL'         => $sendData['TOTAL'],
            'METHOD'        => $sendData['METHOD'],
            'PAYQUICK'      => $sendData['PAYQUICK'],
        ]);

        return $sendData;
    }

    /**
     * カード決済用送信データの取得（定期購買）
     *
     * @param  $Order  受注情報
     * @param  $sendData  設定情報
     *
     * @return  $sendData  送信データ
     */
    private function getCardSendDataAC($Order, $sendData)
    {
        // 定期購買商品でない場合は処理を抜ける
        if(!$this->acService->checkACOrder($Order))
        {
            return $sendData;
        }

        // 加盟店会員番号取得
        $Customer = $Order->getCustomer();
        if($Customer){
            // 会員
            $CustomerId = $Customer->getId();
        }else{
            // 非会員
            $CustomerId = '';
        }

        // 会員名取得
        $name1_temp = mb_convert_kana($Order->getName01(), "H", "UTF-8");
        $name1 = mb_convert_kana($name1_temp, "ASKV", "UTF-8");

        $name2_temp = mb_convert_kana($Order->getName02(), "H", "UTF-8");
        $name2 = mb_convert_kana($name2_temp, "ASKV", "UTF-8");

        // 会員名カナ取得
        $kana1_temp = mb_convert_kana($Order->getKana01(), "H", "UTF-8");
        $kana1 = mb_convert_kana($kana1_temp, "ASKV", "UTF-8");

        $kana2_temp = mb_convert_kana($Order->getKana02(), "H", "UTF-8");
        $kana2 = mb_convert_kana($kana2_temp, "ASKV", "UTF-8");

        // 会員電話番号取得
        $tel   = $Order->getPhoneNumber();

        // ルミーズ定期購買情報作成
        $remiseACMember = new RemiseACMember();
        $remiseACMember = $this->acService->createRemiseACMember($Order);

        $sendData['AUTOCHARGE']     = trans('remise_payment4.ac.front.payment.senddata.autocharge'); // 自動継続課金
        $sendData['AC_MEMBERID']    = '';                             // メンバーID
        $sendData['AC_S_KAIIN_NO']  = $CustomerId;                    // 加盟店会員番号
        $sendData['AC_NAME']        = $name1.$name2;                  // 会員名
        $sendData['AC_KANA']        = $kana1.$kana2;                  // 会員名カナ
        $sendData['AC_TEL']         = $tel;                           // 会員電話番号
        $sendData['AC_AMOUNT']      = $remiseACMember->getTotal();    // 金額
        $sendData['AC_TAX']         = trans('remise_payment4.ac.front.payment.senddata.ac_tax');     // 税送料
        $sendData['AC_TOTAL']       = $remiseACMember->getTotal();    // 合計金額
        $sendData['AC_NEXT_DATE']   = $remiseACMember->getNextDateStr(); // 次回課金日
        $sendData['AC_INTERVAL']    = strval($remiseACMember->getIntervalValue()).strval($remiseACMember->getIntervalMark());  // 決済間隔
        $sendData['AC_COUNT']       = $remiseACMember->getCount();    // 決済回数

        // 初回金額0円の場合、有効性チェック（CHECK）に変更する
        if(round($Order->getPaymentTotal()) == 0){
            $sendData['JOB']        = trans('remise_payment4.ac.front.payment.senddata.job.check');
        }

        $this->logService->logInfo('Payment -- Create Card AC Info', [
            'AUTOCHARGE'     => $sendData['AUTOCHARGE'],
            'AC_MEMBERID'    => $sendData['AC_MEMBERID'],
            'AC_S_KAIIN_NO'  => $sendData['AC_S_KAIIN_NO'],
            'AC_AMOUNT'      => $sendData['AC_AMOUNT'],
            'AC_TAX'         => $sendData['AC_TAX'],
            'AC_TOTAL'       => $sendData['AC_TOTAL'],
            'AC_NEXT_DATE'   => $sendData['AC_NEXT_DATE'],
            'AC_INTERVAL'    => $sendData['AC_INTERVAL'],
            'AC_COUNT'       => $sendData['AC_COUNT']
        ]);

        return $sendData;
    }

    /**
     * マルチ決済用送信データの取得
     *
     * @param  $Order  受注情報
     * @param  $ConfigInfo  設定情報
     *
     * @return  $sendData  送信データ
     */
    private function getCvsSendData($Order, $ConfigInfo)
    {
        // プラグイン情報の取得
        $Plugin = $this->utilService->getPlugin();

        $name1_temp = mb_convert_kana($Order->getName01(), "H", "UTF-8");
        $name1 = mb_convert_kana($name1_temp, "ASKV", "UTF-8"); // 再度変換を行う（ASVは全角かなを全角カナに変換）

        $name2_temp = mb_convert_kana($Order->getName02(), "H", "UTF-8");
        $name2 = mb_convert_kana($name2_temp, "ASKV", "UTF-8");

        $kana1_temp = mb_convert_kana($Order->getKana01(), "H", "UTF-8");
        $kana1 = mb_convert_kana($kana1_temp, "ASKV", "UTF-8");

        $kana2_temp = mb_convert_kana($Order->getKana02(), "H", "UTF-8");
        $kana2 = mb_convert_kana($kana2_temp, "ASKV", "UTF-8");

        $yubin1 = '';
        $yubin2 = '';
        if (strlen($Order->getPostalCode()) == 7)
        {
            $yubin1 = substr($Order->getPostalCode(), 0, 3);
            $yubin2 = substr($Order->getPostalCode(), 3, 4);
        }
        $add1_temp = mb_convert_kana($Order->getPref()->getName(), "H", "UTF-8");
        $add1 = mb_convert_kana($add1_temp, "ASKV", "UTF-8");

        $add2_temp = mb_convert_kana($Order->getAddr01(), "H", "UTF-8");
        $add2 = mb_convert_kana($add2_temp, "ASKV", "UTF-8");

        $add3_temp = mb_convert_kana($Order->getAddr02(), "K", "UTF-8");
        $add3 = mb_convert_kana($add3_temp, "ASKV", "UTF-8");

        $payDate = '';
        if ($ConfigInfo->getPayDate() !== "")
        {
            $payDate = date("Ymd", strtotime("+" . $ConfigInfo->getPayDate() . "day"));
        }

        $exitUrl = $this->generateUrl('remise_payment4_back', ['no' => $Order->getOrderNo()], UrlGeneratorInterface::ABSOLUTE_URL);
        $retUrl = $this->generateUrl('remise_payment4_result_cvs', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $ngRetUrl = $this->generateUrl('remise_payment4_result_cvs', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $sendData = array();
        $sendData['ECCUBE_VER']     = Constant::VERSION;            // EC-CUBEバージョン番号
        if ($Plugin)
        {
            $sendData[$Plugin->getCode() . '_PLG_VER'] = $Plugin->getVersion(); // ルミーズ決済プラグインバージョン番号
        }

        // 税率設定取得
        $currentTaxRate = $this->remiseTaxRateRepository->getCurrentTaxRate();

        // 受注情報詳細リスト
        $OrderItems = $this->orderItemRepository->findBy(['Order' => $Order->getId() ],['OrderItemType' => 'ASC']);

        // 受注情報詳細リストから商品明細の受注情報詳細を取得
        $productTax = 0;
        foreach ($OrderItems as $tmpOrderItem){
            if($tmpOrderItem->isProduct()){
                $productTax = $productTax + $tmpOrderItem->getTax();
            }
        }

        // 送料(税込)
        $deliveryFeeTotal = $Order->getDeliveryFeeTotal();

        // 手数料(税込)
        $charge = $Order->getCharge();

        // 送料の消費税額
        if ($currentTaxRate->getCalcRule() == 1) {
            // 四捨五入 stringでキャストを行うことで、丸め誤差を回避する
            $deliveryFeeTotal_tax = round( (string)( $deliveryFeeTotal - ( $deliveryFeeTotal / (1 + ( $currentTaxRate->getTaxRate() / 100) ) ) ) );
        } else if ($currentTaxRate->getCalcRule() == 2) {
            // 切り捨て stringでキャストを行うことで、丸め誤差を回避する
            $deliveryFeeTotal_tax = floor( (string)( $deliveryFeeTotal - ( $deliveryFeeTotal / (1 + ( $currentTaxRate->getTaxRate() / 100) ) ) ) );
        } else if ($currentTaxRate->getCalcRule() == 3) {
            // 切り上げ stringでキャストを行うことで、丸め誤差を回避する
            $deliveryFeeTotal_tax = ceil( (string)( $deliveryFeeTotal - ( $deliveryFeeTotal / (1 + ( $currentTaxRate->getTaxRate() / 100) ) ) ) );
        }

        // 手数料の消費税額
        if ($currentTaxRate->getCalcRule() == 1) {
            // 四捨五入 stringでキャストを行うことで、丸め誤差を回避する
            $charge_tax = round( (string)( $charge - ( $charge / (1 + ( $currentTaxRate->getTaxRate() / 100) ) ) ) );
        } else if ($currentTaxRate->getCalcRule() == 2) {
            // 切り捨て stringでキャストを行うことで、丸め誤差を回避する
            $charge_tax = floor( (string)( $charge - ( $charge / (1 + ( $currentTaxRate->getTaxRate() / 100) ) ) ) );
        } else if ($currentTaxRate->getCalcRule() == 3) {
            // 切り上げ stringでキャストを行うことで、丸め誤差を回避する
            $charge_tax = ceil( (string)( $charge - ( $charge / (1 + ( $currentTaxRate->getTaxRate() / 100) ) ) ) );
        }

        // TAX = 商品の消費税 + 送料の消費税 + 手数料の消費税
        $tax = $productTax + $deliveryFeeTotal_tax + $charge_tax;

        // TOTAL = 合計金額 - 商品の消費税 - 送料の消費税 - 手数料の消費税
        $total = $Order->getPaymentTotal() - $tax;

        $sendData['SHOPCO']         = $ConfigInfo->getShopco();     // 加盟店コード
        $sendData['HOSTID']         = $ConfigInfo->getHostid();     // ホストID
        $sendData['S_TORIHIKI_NO']  = $Order->getOrderNo();         // 請求番号
        $sendData['NAME1']          = $name1;                       // 顧客名1
        $sendData['NAME2']          = $name2;                       // 顧客名2
        $sendData['KANA1']          = $kana1;                       // 顧客名カナ1
        $sendData['KANA2']          = $kana2;                       // 顧客名カナ2
        $sendData['YUBIN1']         = $yubin1;                      // 郵便番号1
        $sendData['YUBIN2']         = $yubin2;                      // 郵便番号2
        $sendData['ADD1']           = $add1;                        // 住所1
        $sendData['ADD2']           = $add2;                        // 住所2
        $sendData['ADD3']           = $add3;                        // 住所3
        $sendData['TEL']            = $Order->getPhoneNumber();     // 電話番号
        $sendData['MAIL']           = $Order->getEmail();           // e-mail
        $sendData['TOTAL']          = round($Order->getPaymentTotal()); // 合計金額
        $sendData['TAX']            = $tax;                         // 税送料
        $sendData['S_PAYDATE']      = $payDate;                     // 支払期限
        $sendData['SEIYAKUDATE']    = '';                           // 成約日
        $sendData['REMARKS3']       = 'A0000155';                   // 代理店コード
        $sendData['BIKO']           = '';                           // 備考
        $sendData['PAY_WAY']        = '';                           // 支払方法コード
        $sendData['PAY_CSV']        = '';                           // 支払先コード
        $sendData['MNAME_01']       = '商品代金';                   // 明細品名1（最大7個のため、商品代金として全体で出力する）
        $sendData['MSUM_01']        = $total;                       // 明細金額1
        $sendData['EXITURL']        = $exitUrl;                     // 中止URL
        $sendData['RETURL']         = $retUrl;                      // 完了通知URL
        $sendData['DIRECT']         = 'OFF';                        // ダイレクトモード
        $sendData['OPT']            = '';                           // オプション
        $sendData['NG_RETURL']      = $ngRetUrl;                    // NG完了通知URL

        $this->logService->logInfo('Payment -- Create Cvs Info', [
            'S_TORIHIKI_NO' => $sendData['S_TORIHIKI_NO'],
            'NAME1'         => $sendData['NAME1'],
            'NAME2'         => $sendData['NAME2'],
            'MAIL'          => $sendData['MAIL'],
            'TOTAL'         => $sendData['TOTAL'],
            'S_PAYDATE'     => $sendData['S_PAYDATE'],
        ]);

        return $sendData;
    }

    /**
     * 決済画面から加盟店へ戻る
     *
     * @param  $request
     *
     * @Route("/remise_payment4_back", name="remise_payment4_back")
     */
    public function back(Request $request)
    {
        $this->logService->logInfo('Payment Back');

        $orderNo = $request->get('no');
        if (empty($orderNo))
        {
            $this->logService->logError('Payment Back -- Empty Id');
            $this->addError(trans('remise_payment4.front.text.notfound.order'));
            return $this->redirectToRoute('shopping');
        }

        // 受注情報の取得
        $Order = $this->utilService->getOrderByPending($orderNo);

        if (!$Order)
        {
            $this->logService->logError('Payment Back -- Not Found Order (' . $orderNo . ')');
            $this->addError(trans('remise_payment4.front.text.notfound.order'));
            return $this->redirectToRoute('shopping');
        }
        if ($this->getUser() != $Order->getCustomer())
        {
            $this->logService->logError('Payment Back -- Unmatch Customer (' . $orderNo . ')');
            $this->addError(trans('remise_payment4.front.text.notfound.order'));
            return $this->redirectToRoute('shopping');
        }

        // 購入処理をロールバック
        $this->logService->logInfo('Payment Back -- Rollback Order (' . $orderNo . ')');
        $this->rollbackOrder($Order);

        $this->logService->logInfo('Payment Back -- Done');
        return $this->redirectToRoute('shopping');
    }

    /**
     * 購入処理をロールバックする
     *
     * @param  $Order  受注情報
     */
    private function rollbackOrder($Order)
    {
        // 購入処理中に更新
        $Order = $this->utilService->setOrderStatusByProcessing($Order);

        // 仮確定の取り消し
        $this->purchaseFlow->rollback($Order, new PurchaseContext());

        $this->entityManager->flush();
    }

    /**
     * カード決済の完了通知
     *
     * @param  $request
     *
     * @Route("/remise_payment4_result_card", name="remise_payment4_result_card")
     */
    public function cardResult(Request $request)
    {
        $this->logService->logInfo('Payment Card Complete');
        $ConfigInfo = $this->utilService->getConfigInfo();

        // リクエスト情報
        $requestData = $request->request->all();

        $this->logService->logInfo('Payment Card Complete -- Request', $requestData);

        // リクエストデータなし
        $tranid = $this->utilService->getValue($requestData, 'X-TRANID');
        if (empty($tranid))
        {
            $this->logService->logError('Payment Card Complete -- Request Error');
            return $this->redirectToRoute('shopping_error');
        }
        // 戻り区分不正
        if ($this->utilService->getValue($requestData, 'REC_TYPE') != 'END')
        {
            $this->logService->logError('Payment Card Complete -- REC_TYPE Error');
            return $this->redirectToRoute('shopping_error');
        }

        // 受注番号
        $orderNo = $this->utilService->getValue($requestData, 'X-S_TORIHIKI_NO');
        // 金額
        $paymentTotal = $this->utilService->getValue($requestData, 'X-TOTAL');

        // 受注情報の取得
        $Order = $this->utilService->getOrderByNew($orderNo);

        if (!$Order)
        {
            $this->logService->logError('Payment Card Complete -- Not Found Order (' . $orderNo . ')');
            return $this->redirectToRoute('shopping_error');
        }
        if ($this->getUser() != $Order->getCustomer())
        {
            // 購入処理をロールバック
            $this->logService->logInfo('Payment Card Complete -- Rollback Order (' . $orderNo . ')');
            $this->rollbackOrder($Order);

            $this->logService->logError('Payment Card Complete -- Unmatch Customer (' . $orderNo . ')');
            return $this->redirectToRoute('shopping_error');
        }

        // EC-CUBE支払方法の取得
        $Payment = $Order->getPayment();

        if (!$Payment)
        {
            // 購入処理をロールバック
            $this->logService->logInfo('Payment Card Complete -- Rollback Order (' . $orderNo . ')');
            $this->rollbackOrder($Order);

            $this->logService->logError('Payment Card Complete -- Not Found Payment (' . $orderNo . ')');
            return $this->redirectToRoute('shopping_error');
        }

        // 支払方法
        $paymentId = $Payment->getId();

        // ルミーズ支払方法の取得
        $RemisePayment = $this->utilService->getRemisePayment($paymentId);

        if (!$RemisePayment)
        {
            // 購入処理をロールバック
            $this->logService->logInfo('Payment Card Complete -- Rollback Order (' . $orderNo . ')');
            $this->rollbackOrder($Order);

            $this->logService->logError('Payment Card Complete -- Unmatch Payment (' . $orderNo . ')');
            return $this->redirectToRoute('shopping_error');
        }

        // 決済履歴の取得
        $OrderResult = $this->utilService->getOrderResult($Order->getId());

        if (!$OrderResult)
        {
            // 購入処理をロールバック
            $this->logService->logInfo('Payment Card Complete -- Rollback Order (' . $orderNo . ')');
            $this->rollbackOrder($Order);

            $this->logService->logError('Payment Card Complete -- Not Found OrderResult (' . $orderNo . ')');
            return $this->redirectToRoute('shopping_error');
        }

        // カード決済履歴詳細の取得
        $OrderResultCard = $this->utilService->getOrderResultCard($Order->getId());

        if (!$OrderResultCard)
        {
            // 購入処理をロールバック
            $this->logService->logInfo('Payment Card Complete -- Rollback Order (' . $orderNo . ')');
            $this->rollbackOrder($Order);

            $this->logService->logError('Payment Card Complete -- Not Found OrderResultCard (' . $orderNo . ')');
            return $this->redirectToRoute('shopping_error');
        }

        // 金額の相違
        if (round($Order->getPaymentTotal()) != $paymentTotal)
        {
            // 購入処理をロールバック
            $this->logService->logInfo('Payment Card Complete -- Rollback Order (' . $orderNo . ')');
            $this->rollbackOrder($Order);

            $this->logService->logError('Payment Card Complete -- Unmatch Total (' . $orderNo . ', EC-CUBE:' . round($Order->getPaymentTotal()) . ', REMISE:' . $paymentTotal . ')');
            return $this->redirectToRoute('shopping_error');
        }

        // 結果取得
        $rCode = $this->utilService->getValue($requestData, 'X-R_CODE');
        $errcode = $this->utilService->getValue($requestData, 'X-ERRCODE');
        $errinfo = $this->utilService->getValue($requestData, 'X-ERRINFO');
        $errlevel = $this->utilService->getValue($requestData, 'X-ERRLEVEL');
        if (!empty($errcode)) $errcode = trim($errcode);

        // 結果判定
        $errMsg = '';
        if ($rCode != trans('remise_payment4.common.label.r_code.success')
         || $errcode !== ""
         || $errinfo != trans('remise_payment4.common.label.errinfo.success')
         || $errlevel != trans('remise_payment4.common.label.errlevel.success')
        ) {
            // エラーメッセージ取得
            $messageKey = 'remise_payment4.front.text.r_code.' . $rCode;
            $errMsg = trans($messageKey);
            if ($errMsg == $messageKey)
            {
                $head = substr($rCode, 0, 3);
                $tail = substr($rCode, -1);
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
                $errMsg = trans('remise_payment4.front.text.r_code.error');
            }

            $dispErrMsg = trans('remise_payment4.front.text.payment.error') . ":" . $errMsg . "(" . $rCode . ")";

            // 購入処理をロールバック
            $this->logService->logInfo('Payment Card Complete -- Rollback Order (' . $orderNo . ')');
            $this->rollbackOrder($Order);

            $this->logService->logError('Payment Card Complete -- Error Judge (' . $orderNo . ':' . $dispErrMsg . ')');
            $this->addError($dispErrMsg);
            return $this->redirectToRoute('shopping');
        }

        // 決済履歴の設定
        $OrderResult->setCompleteDate(new \DateTime());
        $OrderResult->setUpdateDate(new \DateTime());

        // 決済履歴の登録
        $this->logService->logInfo('Payment Card Complete -- Save OrderResult (' . $orderNo . ')');
        $this->entityManager->persist($OrderResult);
        $this->entityManager->flush();

        // カード決済履歴詳細の設定
        $OrderResultCard->setState      (trans('remise_payment4.common.label.card.state.complete')   );
        $OrderResultCard->setTranid     ($this->utilService->getValue($requestData, 'X-TRANID'      ));
        $OrderResultCard->setMemberId   ($this->utilService->getValue($requestData, 'X-AC_MEMBERID' ));
        $OrderResultCard->setAcTotal    ($this->utilService->getValue($requestData, 'X-AC_TOTAL'    ));
        $OrderResultCard->setAcNextDate ($this->utilService->getDate ($requestData, 'X-AC_NEXT_DATE'));
        $OrderResultCard->setAcInterval ($this->utilService->getValue($requestData, 'X-AC_INTERVAL' ));
        $OrderResultCard->setUpdateDate(new \DateTime());

        // カード決済履歴詳細の登録
        $this->logService->logInfo('Payment Card Complete -- Save OrderResultCard (' . $orderNo . ')');
        $this->entityManager->persist($OrderResultCard);
        $this->entityManager->flush();

        // 売上
        if ($OrderResultCard->getJob() == 'CAPTURE')
        {
            // 入金済みに更新
            $this->logService->logInfo('Payment Card Complete -- Save Order (CAPTURE) (' . $orderNo . ')');
            $Order = $this->utilService->setOrderStatusByPaid($Order);
        }
        // 仮売上
        else
        {
            // 新規受付に更新
            $this->logService->logInfo('Payment Card Complete -- Save Order (AUTH) (' . $orderNo . ')');
            $Order = $this->utilService->setOrderStatusByNew($Order);
        }

        $this->entityManager->persist($Order);
        $this->entityManager->flush();

        // 定期購買が有効の場合
        if ($ConfigInfo && $ConfigInfo->useOptionAC() && $OrderResultCard->getMemberId()) {
            // ルミーズ定期購買情報作成
            $remiseACMember = $this->remiseACMemberRepository->findOneBy([
                'id' => $OrderResultCard->getMemberId()
            ]);
            $remiseACMember = $this->acService->createRemiseACMember($Order, $remiseACMember);
            $remiseACMember->setId($OrderResultCard->getMemberId());
            $this->entityManager->persist($remiseACMember);
            $this->entityManager->flush();

            $remiseACResult = $this->acService->createRemiseACResult($Order->getId(),trans('remise_payment4.ac.plg_remise_payment4_remise_ac_result.result.first.key'));
            $this->entityManager->persist($remiseACResult);
            $this->entityManager->flush();

        }

        // カート削除
        $this->cartService->clear();

        // 受注IDをセッションにセット
        $this->session->set('eccube.front.shopping.order.id', $Order->getId());

        // メール送信
        $this->logService->logInfo('Payment Card Complete -- Send Mail (' . $orderNo . ')');

        if ($ConfigInfo && $ConfigInfo->useOptionAC() && $OrderResultCard->getMemberId()) {
            // 定期購買が有効の場合
            // ルミーズ定期購買メンバー情報を取得
            $remiseACMember = $this->acService->getRemiseACMemberByOrderId($Order->getId());
            // 定期購買用メール送信
            $this->acService->sendMailAcOrder($Order, $remiseACMember);

            // 完了メッセージを追加
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

            // コンテキストのクリア
            $this->entityManager->clear();

            // 受注情報の再取得
            $Order = $this->utilService->getOrderByOrderNo($orderNo);

            $twig = 'RemisePayment42/Resource/template/sub_shopping_complete_autocharge.twig';
            $msg = $this->renderView($twig, ['Order' => $Order, 'RemiseACMember' => $remiseACMember, 'interval' => $interval, 'remiseAcCount' => $countText ]);
            $Order->appendCompleteMessage($msg);
            $this->entityManager->persist($Order);
            $this->entityManager->flush();
        } else { 
            // 通常の商品購入
            $MailHistory = $this->mailService->sendOrderMail($Order);
            $this->entityManager->flush();
        }

        // 受注完了ページへ遷移
        $this->logService->logInfo('Payment Card Complete -- Done');
        return $this->redirectToRoute('shopping_complete');
    }

    /**
     * マルチ決済の完了通知
     *
     * @param  $request
     *
     * @Route("/remise_payment4_result_cvs", name="remise_payment4_result_cvs")
     */
    public function cvsResult(Request $request)
    {
        $this->logService->logInfo('Payment Cvs Complete');

        // リクエスト情報
        $requestData = $request->request->all();

        $this->logService->logInfo('Payment Cvs Complete -- Request', $requestData);

        // リクエストデータなし
        $tranid = $this->utilService->getValue($requestData, 'X-JOB_ID');
        if (empty($tranid))
        {
            $this->logService->logError('Payment Cvs Complete -- Request Error');
            return $this->redirectToRoute('shopping_error');
        }

        // 受注番号
        $orderNo = $this->utilService->getValue($requestData, 'X-S_TORIHIKI_NO');
        // 金額
        $paymentTotal = $this->utilService->getValue($requestData, 'X-TOTAL');

        // 受注情報の取得
        $Order = $this->utilService->getOrderByPending($orderNo);

        if (!$Order)
        {
            $this->logService->logError('Payment Cvs Complete -- Not Found Order (' . $orderNo . ')');
            return $this->redirectToRoute('shopping_error');
        }
        if ($this->getUser() != $Order->getCustomer())
        {
            // 購入処理をロールバック
            $this->logService->logInfo('Payment Cvs Complete -- Rollback Order (' . $orderNo . ')');
            $this->rollbackOrder($Order);

            $this->logService->logError('Payment Cvs Complete -- Unmatch Customer (' . $orderNo . ')');
            return $this->redirectToRoute('shopping_error');
        }

        // EC-CUBE支払方法の取得
        $Payment = $Order->getPayment();

        if (!$Payment)
        {
            // 購入処理をロールバック
            $this->logService->logInfo('Payment Cvs Complete -- Rollback Order (' . $orderNo . ')');
            $this->rollbackOrder($Order);

            $this->logService->logError('Payment Cvs Complete -- Not Found Payment (' . $orderNo . ')');
            return $this->redirectToRoute('shopping_error');
        }

        // 支払方法
        $paymentId = $Payment->getId();

        // ルミーズ支払方法の取得
        $RemisePayment = $this->utilService->getRemisePayment($paymentId);

        if (!$RemisePayment)
        {
            // 購入処理をロールバック
            $this->logService->logInfo('Payment Cvs Complete -- Rollback Order (' . $orderNo . ')');
            $this->rollbackOrder($Order);

            $this->logService->logError('Payment Cvs Complete -- Unmatch Payment (' . $orderNo . ')');
            return $this->redirectToRoute('shopping_error');
        }

        // 決済履歴の取得
        $OrderResult = $this->utilService->getOrderResult($Order->getId());

        if (!$OrderResult)
        {
            // 購入処理をロールバック
            $this->logService->logInfo('Payment Cvs Complete -- Rollback Order (' . $orderNo . ')');
            $this->rollbackOrder($Order);

            $this->logService->logError('Payment Cvs Complete -- Not Found OrderResult (' . $orderNo . ')');
            return $this->redirectToRoute('shopping_error');
        }

        // マルチ決済履歴詳細の取得
        $OrderResultCvs = $this->utilService->getOrderResultCvs($Order->getId());

        if (!$OrderResultCvs)
        {
            // 購入処理をロールバック
            $this->logService->logInfo('Payment Cvs Complete -- Rollback Order (' . $orderNo . ')');
            $this->rollbackOrder($Order);

            $this->logService->logError('Payment Cvs Complete -- Not Found OrderResultCvs (' . $orderNo . ')');
            return $this->redirectToRoute('shopping_error');
        }

        // 金額の相違
        if (round($Order->getPaymentTotal()) != $paymentTotal)
        {
            // 購入処理をロールバック
            $this->logService->logInfo('Payment Cvs Complete -- Rollback Order (' . $orderNo . ')');
            $this->rollbackOrder($Order);

            $this->logService->logError('Payment Cvs Complete -- Unmatch Total (' . $orderNo . ', EC-CUBE:' . round($Order->getPaymentTotal()) . ', REMISE:' . $paymentTotal . ')');
            return $this->redirectToRoute('shopping_error');
        }

        // 結果取得
        $rCode = $this->utilService->getValue($requestData, 'X-R_CODE');

        // 結果判定
        $errMsg = '';
        if ($rCode != trans('remise_payment4.common.label.r_code.success'))
        {
            // エラーメッセージ取得
            $messageKey = 'remise_payment4.front.text.r_code.' . $rCode;
            $errMsg = trans($messageKey);
            if ($errMsg == $messageKey)
            {
                $head = substr($rCode, 0, 3);
                $tail = substr($rCode, -1);
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
                $errMsg = trans('remise_payment4.front.text.r_code.error');
            }

            $dispErrMsg = trans('remise_payment4.front.text.payment.error') . ":" . $errMsg . "(" . $rCode . ")";

            // 購入処理をロールバック
            $this->logService->logInfo('Payment Cvs Complete -- Rollback Order (' . $orderNo . ')');
            $this->rollbackOrder($Order);

            $this->logService->logError('Payment Cvs Complete -- Error Judge (' . $dispErrMsg . ')');
            $this->addError($dispErrMsg);
            return $this->redirectToRoute('shopping');
        }

        // 決済履歴の設定
        $OrderResult->setCompleteDate(new \DateTime());
        $OrderResult->setUpdateDate(new \DateTime());

        // 決済履歴の登録
        $this->logService->logInfo('Payment Cvs Complete -- Save OrderResult (' . $orderNo . ')');
        $this->entityManager->persist($OrderResult);
        $this->entityManager->flush($OrderResult);

        // マルチ決済履歴詳細の設定
        $OrderResultCvs->setJobid   ($this->utilService->getValue($requestData, 'X-JOB_ID'  ));
        $OrderResultCvs->setRCode   ($this->utilService->getValue($requestData, 'X-R_CODE'  ));
        $OrderResultCvs->setPayWay  ($this->utilService->getValue($requestData, 'X-PAY_WAY' ));
        $OrderResultCvs->setPayCsv  ($this->utilService->getValue($requestData, 'X-PAY_CSV' ));
        $OrderResultCvs->setPayNo1  ($this->utilService->getValue($requestData, 'X-PAY_NO1' ));
        $OrderResultCvs->setPayNo2  ($this->utilService->getValue($requestData, 'X-PAY_NO2' ));
        $OrderResultCvs->setPayDate ($this->utilService->getDate ($requestData, 'X-PAYDATE' ));
        $OrderResultCvs->setUpdateDate(new \DateTime());

        // マルチ決済履歴詳細の登録
        $this->logService->logInfo('Payment Cvs Complete -- Save OrderResultCard (' . $orderNo . ')');
        $this->entityManager->persist($OrderResultCvs);
        $this->entityManager->flush($OrderResultCvs);

        // 追加情報
        $cvsInfo = $this->utilService->getCvsInfo($Order, $OrderResultCvs);

        // 完了メッセージを追加
        $twig = 'RemisePayment42/Resource/template/order_cvs.twig';
        $msg = $this->renderView($twig, $cvsInfo);
        $Order->appendCompleteMessage($msg);

        // 注文完了メールにメッセージを追加
        $twig = 'RemisePayment42/Resource/template/mail/order_cvs.twig';
        $msg = $this->renderView($twig, $cvsInfo);
        $Order->appendCompleteMailMessage($msg);

        // 購入処理中の受注を確定
        $this->logService->logInfo('Payment Cvs Complete -- Commit Order (' . $orderNo . ')');
        $this->purchaseFlow->commit($Order, new PurchaseContext());

        // EC-CUBE受注ステータスを更新
        $this->logService->logInfo('Payment Cvs Complete -- Save Order (' . $orderNo . ')');
        $Order = $this->utilService->setOrderStatusByNew($Order);
        $this->entityManager->persist($Order);
        $this->entityManager->flush();

        // カート削除
        $this->cartService->clear();

        // 受注IDをセッションにセット
        $this->session->set('eccube.front.shopping.order.id', $Order->getId());

        // メール送信
        $this->logService->logInfo('Payment Cvs Complete -- Send Mail (' . $orderNo . ')');
        $MailHistory = $this->mailService->sendOrderMail($Order);
        $this->entityManager->flush();

        // 受注完了ページへ遷移
        $this->logService->logInfo('Payment Cvs Complete -- Done');
        return $this->redirectToRoute('shopping_complete');
    }

    /**
     * 定期購買のダミーURL生成用
     *
     * @param  $request
     *
     * @Route("/remise_shopping", name="remise_shopping")
     * @Route("/remise_shopping_complete", name="remise_shopping_complete")
     *
     */
    public function remiseShoppingDummy(Request $request)
    {

    }

    /**
     * カード決済用送信データの取得（EMV3Dセキュア）
     *
     * @param \Eccube\Entity\Order $Order
     * @param $sendData
     *
     * @return $sendData  送信データ
     */
    private function getCardSendDataEMV3DS($Order, $sendData)
    {
        // 会員情報を取得
        $Customer = $Order->getCustomer();
        // 支払情報を取得
        $Payment = $Order->getPayment();
        // 配送方法の取得
        $Shipping = $Order->getShippings()->first();
        
        $sendData['chAccDate'] = $this->getChAccDate($Customer);                                                    // アカウント情報の作成日
        $sendData['chAccAgeInd'] = $this->getChAccAgeInd($Customer);                                                // アカウントを取得してからの経過期間
        // アカウント情報の最終変更日を取得
        $chAccChange = $this->getAccChange($Customer);
        $sendData['chAccChange'] = $this->ConvertYmd($chAccChange);                                                 // アカウント情報の最終変更日
        $sendData['chAccChangeInd'] = $this->getIndicator($chAccChange);                                            // アカウント情報の最終変更日からの経過期間
        $sendData['txnActivityDay'] = $this->getTxnActivityDay($Customer, $Payment);                                // 直近24時間の取引回数
        $sendData['txnActivityYear'] = $this->getTxnActivityYear($Customer, $Payment);                              // 直近1年間の取引回数
        $sendData['nbPurchaseAccount'] = $this->getNbPurchaseAccount($Customer);                                    // 直近6ヶ月の購買回数
        // この取引の出荷先の初回利用日を取得
        $shipAddressUsage = $this->getShipAddressUsage($Order, $Customer, $Shipping);
        $sendData['shipAddressUsage'] = $this->ConvertYmd($shipAddressUsage);                                       // この取引の出荷先の初回利用日
        $sendData['shipAddressUsageInd'] = $this->getIndicator($shipAddressUsage);                                  // この取引の出荷先の初回利用日からの経過期間
        $sendData['shipNameIndicator'] = $this->getShipNameIndicator($Order, $Shipping);                            // カード所有者名と配送先宛名が同じか否か
        $sendData['threeDSReqAuthMethod'] = $this->getThreeDSReqAuthMethod($Customer);                              // 加盟店がアカウントを認証した方法
        $sendData['reorderItemsInd'] = $this->getReorderItemsInd($Order, $Customer);                                // カード会員の過去注文商品か否か
        $sendData['shipIndicator'] = $this->getShipIndicator($Order, $Shipping);                                    // この取引の配送方法
        $sendData['addrMatch'] = $this->getAddrMatch($Order, $Shipping);                                            // カード会員の住所と出荷先が同じか否か
        $sendData['billAddrCountry'] = $this->eccubeConfig['remise_payment4']['emv3ds']['country_codes_jp'];        // カード会員の住所（国）
        $sendData['billAddrPostCode'] = $Order->getPostalCode();                                                    // カード会員の住所（郵便番号）
        $sendData['billAddrState'] = sprintf('%02d', $Order->getPref()->getId());                                   // カード会員の住所（都道府県）
        $sendData['billAddrLine1'] = $Order->getAddr01();                                                           // カード会員の住所（町域・丁目番地）
        $sendData['billAddrLine2'] = $Order->getAddr02();                                                           // カード会員の住所（建物・号室）
        $sendData['email'] = $Order->getEmail();                                                                    // カード会員のメールアドレス
        $sendData['homePhoneCc'] = $this->eccubeConfig['remise_payment4']['emv3ds']['country_calling_codes_jp'];    // カード会員の連絡先（国）
        $sendData['homePhoneSubscriber'] = $Order->getPhoneNumber();                                                // カード会員の連絡先（電話番号）
        $sendData['shipAddrCountry'] = $this->eccubeConfig['remise_payment4']['emv3ds']['country_codes_jp'];        // 出荷先の住所（国）
        $sendData['shipAddrPostCode'] = $Shipping->getPostalCode();                                                 // 出荷先の住所（郵便番号）
        $sendData['shipAddrState'] = sprintf('%02d', $Shipping->getPref()->getId());                                // 出荷先の住所（都道府県）
        $sendData['shipAddrLine1'] = $Shipping->getAddr01();                                                        // 出荷先の住所（町域・丁目番地）
        $sendData['shipAddrLine2'] = $Shipping->getAddr02();                                                        // 出荷先の住所（建物・号室）
        
        $this->logService->logInfo('Payment -- Create Card EMV3DS Info', [
            'chAccDate' => $sendData['chAccDate'],
            'chAccAgeInd' => $sendData['chAccAgeInd'],
            'chAccChange' => $sendData['chAccChange'],
            'chAccChangeInd' => $sendData['chAccChangeInd'],
            'txnActivityDay' => $sendData['txnActivityDay'],
            'txnActivityYear' => $sendData['txnActivityYear'],
            'nbPurchaseAccount' => $sendData['nbPurchaseAccount'],
            'shipAddressUsage' => $sendData['shipAddressUsage'],
            'shipAddressUsageInd' => $sendData['shipAddressUsageInd'],
            'shipNameIndicator' => $sendData['shipNameIndicator'],
            'threeDSReqAuthMethod' => $sendData['threeDSReqAuthMethod'],
            'reorderItemsInd' => $sendData['reorderItemsInd'],
            'shipIndicator' => $sendData['shipIndicator'],
            'addrMatch' => $sendData['addrMatch'],
            'billAddrCountry' => $sendData['billAddrCountry'],
            'billAddrPostCode' => $sendData['billAddrPostCode'],
            'billAddrState' => $sendData['billAddrState'],
            'billAddrLine1' => $sendData['billAddrLine1'],
            'billAddrLine2' => $sendData['billAddrLine2'],
            'email' => $sendData['email'],
            'homePhoneCc' => $sendData['homePhoneCc'],
            'homePhoneSubscriber' => $sendData['homePhoneSubscriber'],
            'shipAddrCountry' => $sendData['shipAddrCountry'],
            'shipAddrPostCode' => $sendData['shipAddrPostCode'],
            'shipAddrState' => $sendData['shipAddrState'],
            'shipAddrLine1' => $sendData['shipAddrLine1'],
            'shipAddrLine2' => $sendData['shipAddrLine2']
        ]);
        
        return $sendData;
    }
    
    /**
     * アカウント情報の作成日を取得
     *
     * @param \Eccube\Entity\Customer $Customer
     *
     * @return string
     */
    private function getChAccDate($Customer)
    {
        if (is_null($Customer)) {
            return "";
        }
        
        $createDate = $Customer->getCreateDate();
        
        return $this->ConvertYmd($createDate);
    }
    
    /**
     * アカウントを取得してからの経過期間を取得
     *
     * @param \Eccube\Entity\Customer $Customer
     *
     * @return string
     */
    private function getChAccAgeInd($Customer)
    {
        if (is_null($Customer)) {
            return $this->eccubeConfig['remise_payment4']['emv3ds']['ch_acc_age']['no_account'];
        }
        
        $createDate = $Customer->getCreateDate();
        $sysDate = new \DateTime();
        $interval = $createDate->diff($sysDate);

        if ($interval->format('%a') == 0) {
            return $this->eccubeConfig['remise_payment4']['emv3ds']['ch_acc_age']['this_transaction'];
        } elseif ($interval->format('%a') < 30) {
            return $this->eccubeConfig['remise_payment4']['emv3ds']['ch_acc_age']['less_30days'];
        } elseif ($interval->format('%a') < 60) {
            return $this->eccubeConfig['remise_payment4']['emv3ds']['ch_acc_age']['less_60days'];
        } else {
            return $this->eccubeConfig['remise_payment4']['emv3ds']['ch_acc_age']['more_60days'];
        }
    }
    
    /**
     * アカウント情報の最終変更日を取得
     *
     * @param \Eccube\Entity\Customer $Customer
     *
     * @return \DateTime|null
     */
    private function getAccChange($Customer)
    {
        if (is_null($Customer)) {
            return NULL;
        }
        
        $updateDate = $Customer->getUpdateDate();
        $lastUpdateDate = $this->emv3dsRepository->getLastUpdateDate($Customer);
        
        return is_null($lastUpdateDate) ? $updateDate : $lastUpdateDate;
    }
    
    /**
     * 直近24時間の取引回数を取得
     *
     * @param \Eccube\Entity\Customer $Customer
     * @param \Eccube\Entity\Payment $payment
     *
     * @return int
     */
    private function getTxnActivityDay($Customer, $Payment)
    {
        if (is_null($Customer)) {
            return "";
        }

        $sysDate = new \DateTime();
        $date = $sysDate->modify('-24 hours');
        $count = $this->emv3dsRepository->getActivityCount($Customer, $Payment, $date);

        return $count < 999 ? $count : 999;
    }
    
    /**
     * 直近1年間の取引回数を取得
     *
     * @param \Eccube\Entity\Customer $Customer
     * @param \Eccube\Entity\Payment $payment
     *
     * @return int
     */
    private function getTxnActivityYear($Customer, $Payment)
    {
        if (is_null($Customer)) {
            return "";
        }

        $sysDate = new \DateTime();
        $date = $sysDate->modify('-12 months');
        $count = $this->emv3dsRepository->getActivityCount($Customer, $Payment, $date);

        return $count < 999 ? $count : 999;
    }
    
    /**
     * 直近6ヶ月の購買回数を取得
     *
     * @param \Eccube\Entity\Customer $Customer
     *
     * @return int
     */
    private function getNbPurchaseAccount($Customer)
    {
        if (is_null($Customer)) {
            return "";
        }

        $sysDate = new \DateTime();
        $date = $sysDate->modify('-6 months');
        $count = $this->emv3dsRepository->getPurchaseCount($Customer, $date);

        return $count < 9999 ? $count : 9999;
    }
    
    /**
     * この取引の出荷先の初回利用日を取得
     *
     * @param \Eccube\Entity\Order $Order
     * @param \Eccube\Entity\Customer $Customer
     * @param \Eccube\Entity\Shipping $Shipping
     *
     * @return \DateTime|null
     */
    private function getShipAddressUsage($Order, $Customer, $Shipping)
    {
        if (is_null($Customer)) {
            return NULL;
        }
        
        $orderDate = $this->emv3dsRepository->getShipAddressUsage($Customer, $Shipping);
        
        return is_null($orderDate) ? new \DateTime() : $orderDate;
    }
    
    /**
     * カード所有者名と配送先宛名が同じか否かを取得
     *
     * @param \Eccube\Entity\Order $Order
     * @param \Eccube\Entity\Shipping $Shipping
     *
     * @return string
     */
    private function getShipNameIndicator($Order, $Shipping)
    {
        if ($Order->getName01() !== $Shipping->getName01()) {
            return $this->eccubeConfig['remise_payment4']['emv3ds']['different'];
        }
        
        if ($Order->getName02() !== $Shipping->getName02()) {
            return $this->eccubeConfig['remise_payment4']['emv3ds']['different'];
        }
        
        return $this->eccubeConfig['remise_payment4']['emv3ds']['identical'];
    }
    
    /**
     * 加盟店がアカウントを認証した方法を取得
     *
     * @param \Eccube\Entity\Customer $Customer
     *
     * @return string
     */
    private function getThreeDSReqAuthMethod($Customer)
    {
        if ($Customer) {
            return $this->eccubeConfig['remise_payment4']['emv3ds']['own_credentials'];
        } else {
            return $this->eccubeConfig['remise_payment4']['emv3ds']['no_authentication'];
        }
    }
    
    /**
     * カード会員の過去注文商品か否かを取得
     *
     * @param \Eccube\Entity\Order $Order
     * @param \Eccube\Entity\Customer $Customer
     *
     * @return string
     */
    private function getReorderItemsInd($Order, $Customer)
    {
        if (is_null($Customer)) {
            return "";
        }
        
        $count = $this->emv3dsRepository->getReorderItemCount($Order, $Customer);
        
        if ($count > 0) {
            return $this->eccubeConfig['remise_payment4']['emv3ds']['reordered'];
        }
        
        return $this->eccubeConfig['remise_payment4']['emv3ds']['first_ordered'];
    }
    
    /**
     * この取引の配送方法を取得
     *
     * @param \Eccube\Entity\Order $Order
     * @param \Eccube\Entity\Shipping $Shipping
     *
     * @return string
     */
    private function getShipIndicator($Order, $Shipping)
    {
        if ($Order->getPostalCode() !== $Shipping->getPostalCode()) {
            return $this->eccubeConfig['remise_payment4']['emv3ds']['different_address'];
        }
        
        if ($Order->getPref() !== $Shipping->getPref()) {
            return $this->eccubeConfig['remise_payment4']['emv3ds']['different_address'];
        }
        
        if ($Order->getAddr01() !== $Shipping->getAddr01()) {
            return $this->eccubeConfig['remise_payment4']['emv3ds']['different_address'];
        }
        
        if ($Order->getAddr02() !== $Shipping->getAddr02()) {
            return $this->eccubeConfig['remise_payment4']['emv3ds']['different_address'];
        }
        
        return $this->eccubeConfig['remise_payment4']['emv3ds']['billing_address'];
    }
    
    /**
     * カード会員の住所と出荷先が同じか否かを取得
     *
     * @param \Eccube\Entity\Order $Order
     * @param \Eccube\Entity\Shipping $Shipping
     *
     * @return string
     */
    private function getAddrMatch($Order, $Shipping)
    {
        if ($Order->getPostalCode() !== $Shipping->getPostalCode()) {
            return $this->eccubeConfig['remise_payment4']['emv3ds']['does_not_match'];
        }
        
        if ($Order->getPref() !== $Shipping->getPref()) {
            return $this->eccubeConfig['remise_payment4']['emv3ds']['does_not_match'];
        }
        
        if ($Order->getAddr01() !== $Shipping->getAddr01()) {
            return $this->eccubeConfig['remise_payment4']['emv3ds']['does_not_match'];
        }
        
        if ($Order->getAddr02() !== $Shipping->getAddr02()) {
            return $this->eccubeConfig['remise_payment4']['emv3ds']['does_not_match'];
        }
        
        return $this->eccubeConfig['remise_payment4']['emv3ds']['matches'];
    }
    
    /**
     * Indicatorを取得
     *
     * @param \DateTime $date
     *
     * @return string
     */
    private function getIndicator($date)
    {
        if (empty($date)) {
            return "";
        }
        
        $sysDate = new \DateTime();
        $interval = $date->diff($sysDate);

        if ($interval->format('%a') == 0) {
            return $this->eccubeConfig['remise_payment4']['emv3ds']['this_transaction'];
        } elseif ($interval->format('%a') < 30) {
            return $this->eccubeConfig['remise_payment4']['emv3ds']['less_30days'];
        } elseif ($interval->format('%a') < 60) {
            return $this->eccubeConfig['remise_payment4']['emv3ds']['less_60days'];
        } else {
            return $this->eccubeConfig['remise_payment4']['emv3ds']['more_60days'];
        }
    }
    
    /**
     * 年月日形式に変換
     *
     * @param \DateTime $dateTime
     *
     * @return string
     */
    private function ConvertYmd($dateTime)
    {
        if (empty($dateTime)) {
            return "";
        }
        
        return $dateTime
        ->setTimeZone(new \DateTimeZone('UTC'))
        ->format('Ymd');
    }
}
