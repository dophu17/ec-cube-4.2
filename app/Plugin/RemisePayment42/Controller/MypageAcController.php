<?php
namespace Plugin\RemisePayment42\Controller;

use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Plugin\RemisePayment42\Service\LogService;
use Plugin\RemisePayment42\Service\UtilService;
use Plugin\RemisePayment42\Service\AcService;
use Plugin\RemisePayment42\Service\AcApiService;
use Plugin\RemisePayment42\Entity\RemiseACApiResult;
use Plugin\RemisePayment42\Entity\RemiseACMember;
use Plugin\RemisePayment42\Form\Type\MyPageAcSkipType;
use Plugin\RemisePayment42\Form\Type\MyPageAcCancelType;

/**
 * マイページ定期購買機能
 */
class MypageAcController extends AbstractController
{

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
     * @var AcService
     */
    protected $acService;

    /**
     *
     * @var AcApiService
     */
    protected $acApiService;

    /**
     * コンストラクタ
     *
     * @param UtilService $utilService
     * @param LogService $logService
     * @param AcService $acService
     * @param AcApiService $acApiService
     */
    public function __construct(UtilService $utilService, LogService $logService, AcService $acService, AcApiService $acApiService)
    {
        $this->utilService = $utilService;
        $this->logService = $logService;
        $this->acService = $acService;
        $this->acApiService = $acApiService;
    }

    /**
     * 登録カード情報更新画面表示
     *
     * @param $request
     * @param $order_id
     * @param $mode
     *
     * @Route("/mypage/remise_payment4_ac_card_update/{order_id}/{mode}", requirements={"order_id" = "\d+"}, name="remise_payment4_ac_mypage_card_update")
     * @Template("@RemisePayment42/sub_mypage_ac_card_update.twig")
     */
    public function update(Request $request, $order_id = null, $mode = null)
    {
        // 開始ログ
        $this->logService->logInfo('Ac Mypage Update');

        // 顧客情報を取得
        $Customer = $this->getUser();

        // 受注情報を取得
        $order = $this->utilService->getOrderByOrderIdCustomer($order_id,$Customer);
        if(!$order){
            throw new NotFoundHttpException();
        }

        // 表示モード(init:初期、complete:完了)
        $dispErrMsg = null;
        switch ($mode) {
            case "complete":
                $process = "complete";
                $queryParameters = $request->query;
                $dispErrMsg = $queryParameters->get("dispErrMsg");
                break;
            default :
                $process = "init";
                break;
        }

        // ルミーズ定期購買メンバー情報を取得
        $remiseACMember = $this->acService->getRemiseACMemberByOrderId($order_id);
        if(!$remiseACMember){
            throw new NotFoundHttpException();
        }

        // 終了ログ
        $this->logService->logInfo('Ac Mypage Update -- Done');

        // 結果通知の応答
        return [
            'Order' => $order,
            'remiseACMember' => $remiseACMember,
            'process' => $process,
            'dispErrMsg' => $dispErrMsg,
        ];
    }

    /**
     * 登録カード情報更新画面リダイレクト
     *
     * @param $request
     *
     * @Route("/mypage/remise_payment4_ac_card_update/redirect/{order_id}", requirements={"order_id" = "\d+"}, name="remise_payment4_ac_mypage_card_update_redirect")
     * @Template("@RemisePayment42/redirect.twig")
     */
    public function updateRedirect(Request $request, $order_id = null)
    {
        $this->logService->logInfo('Ac Mypage Update Redirect');

        // プラグイン基本情報の取得
        $Plugin = $this->utilService->getPlugin();
        if(!$Plugin)
        {
            $this->logService->logWarning('Ac Mypage Update Redirect',
                Array(trans('remise_payment4.ac.front.sub_mypage_ac_card_update.text.update.warning.plugin')));
            $this->addWarning(trans('remise_payment4.ac.front.sub_mypage_ac_card_update.text.update.warning.plugin'));
            return $this->forwardToRoute('remise_payment4_ac_mypage_card_update', ['order_id' => $order_id]);
        }

        // プラグイン設定情報の取得
        $Config = $this->utilService->getConfig();
        if(!$Config)
        {
            $this->logService->logWarning('Ac Mypage Update Redirect',
                Array(trans('remise_payment4.ac.front.sub_mypage_ac_card_update.text.update.warning.plugin')));
            $this->addWarning(trans('remise_payment4.ac.front.sub_mypage_ac_card_update.text.update.warning.plugin'));
            return $this->forwardToRoute('remise_payment4_ac_mypage_card_update', ['order_id' => $order_id]);
        }

        // プラグイン設定詳細情報の取得
        $ConfigInfo = $Config->getUnserializeInfo();
        if(!$ConfigInfo)
        {
            $this->logService->logWarning('Ac Mypage Update Redirect',
                Array(trans('remise_payment4.ac.front.sub_mypage_ac_card_update.text.update.warning.plugin')));
            $this->addWarning(trans('remise_payment4.ac.front.sub_mypage_ac_card_update.text.update.warning.plugin'));
            return $this->forwardToRoute('remise_payment4_ac_mypage_card_update', ['order_id' => $order_id]);
        }

        // カード更新URL取得
        $sendUrl = $ConfigInfo->getCardUrl();
        if(!$sendUrl or empty($sendUrl)){
            $this->logService->logWarning('Ac Mypage Update Redirect',
                Array(trans('remise_payment4.ac.front.sub_mypage_ac_card_update.text.update.warning.plugin.card_url')));
            $this->addWarning(trans('remise_payment4.ac.front.sub_mypage_ac_card_update.text.update.warning.plugin.card_url'));
            return $this->forwardToRoute('remise_payment4_ac_mypage_card_update', ['order_id' => $order_id]);
        }

        $exitUrl = $this->generateUrl('remise_payment4_ac_mypage_card_update', ['order_id' => $order_id], UrlGeneratorInterface::ABSOLUTE_URL);
        $retUrl = $this->generateUrl('remise_payment4_ac_mypage_card_update_return', ['order_id' => $order_id], UrlGeneratorInterface::ABSOLUTE_URL);
        $ngRetUrl = $this->generateUrl('remise_payment4_ac_mypage_card_update_return', ['order_id' => $order_id], UrlGeneratorInterface::ABSOLUTE_URL);

        // 顧客情報を取得
        $Customer = $this->getUser();

        // 受注情報を取得
        $order = $this->utilService->getOrderByOrderIdCustomer($order_id,$Customer);
        if(!$order){
            throw new NotFoundHttpException();
        }

        // ルミーズ定期購買メンバー情報を取得
        $remiseACMember = $this->acService->getRemiseACMemberByOrderId($order_id);
        if(!$remiseACMember){
            throw new NotFoundHttpException();
        }

        $ac_mail = "";
        $opt = '';
        $customerId = "";
        $name1 = "";
        $name2 = "";
        $kana1 = "";
        $kana2 = "";
        $tel = "";
        if ($Customer) {
            // メールアドレス
            $ac_mail = $Customer->getEmail();
            // オプション
            $opt = $Customer->getId() . ',';
            // 会員ID
            $customerId = $Customer->getId();
            // 会員名取得
            $name1 = mb_convert_kana($Customer->getName01(), "ASKV", "UTF-8");
            $name2 = mb_convert_kana($Customer->getName02(), "ASKV", "UTF-8");
            // 会員名カナ取得
            $kana1 = mb_convert_kana($Customer->getKana01(), "ASKV", "UTF-8");
            $kana2 = mb_convert_kana($Customer->getKana02(), "ASKV", "UTF-8");
            // 会員電話番号取得
            $tel   = $order->getPhoneNumber();
        } else {
            // メールアドレス
            $ac_mail = $order->getEmail();
            // 会員名取得
            $name1 = mb_convert_kana($order->getName01(), "ASKV", "UTF-8");
            $name2 = mb_convert_kana($order->getName02(), "ASKV", "UTF-8");
            // 会員名カナ取得
            $kana1 = mb_convert_kana($order->getKana01(), "ASKV", "UTF-8");
            $kana2 = mb_convert_kana($order->getKana02(), "ASKV", "UTF-8");
            // 会員電話番号取得
            $tel   = $order->getPhoneNumber();
        }

        // 次回課金日補正
        $nowDate = new \DateTime();
        $nowDate->setTime(0, 0, 0, 0);

        $nextDate = $remiseACMember->getNextDate();
        $nextDate->setTime(0, 0, 0, 0);

        if ($nextDate <= $nowDate) {
            $nextDate = $this->acService->getNextDate($remiseACMember);
            $remiseACMember->setNextDate($nextDate);
        }

        $sendData = array();
        // 基本情報部
        $sendData['ECCUBE_VER']     = Constant::VERSION;            // EC-CUBEバージョン番号
        $sendData[$Plugin->getCode() . '_PLG_VER'] = $Plugin->getVersion(); // ルミーズ決済プラグインバージョン番号
        $sendData['SHOPCO']         = $ConfigInfo->getShopco();     // 加盟店コード
        $sendData['HOSTID']         = $ConfigInfo->getHostid();     // ホストID
        $sendData['S_TORIHIKI_NO']  = $order->getOrderNo();         // 受注番号
        $sendData['JOB']            = "CHECK";                      // 処理区分
        $sendData['MAIL']           = $ac_mail;                     // e-mail
        $sendData['ITEM']           = '0000120';                    // 商品コード(ルミーズ固定)
        $sendData['AMOUNT']         = '0';                          // 金額
        $sendData['TAX']            = '0';                          // 税送料
        $sendData['TOTAL']          = '0';                          // 合計金額

        // 設定情報部
        $sendData['EXITURL']        = $exitUrl;                     // 中止URL
        $sendData['RETURL']         = $retUrl;                      // 完了通知URL
        $sendData['NG_RETURL']      = $ngRetUrl;                    // NG完了通知URL
        $sendData['DIRECT']         = 'OFF';                        // ダイレクトモード
        $sendData['OPT']            = $opt;                         // オプション
        $sendData['REMARKS3']       = 'A0000155';                   // 代理店コード

        // 継続課金情報部
        $sendData['AUTOCHARGE']     = '1';                          // 自動継続課金（1：自動継続課金登録を行う）
        $sendData['AC_MEMBERID']    = $remiseACMember->getId();     // メンバーID
        $sendData['AC_S_KAIIN_NO']  = $customerId;                  // 加盟店会員番号
        $sendData['AC_NAME']        = $name1.$name2;                // 会員名
        $sendData['AC_KANA']        = $kana1.$kana2;                // 会員名カナ
        $sendData['AC_TEL']         = $tel;                         // 会員電話番号
        $sendData['AC_AMOUNT']      = $remiseACMember->getTotal();  // 金額
        $sendData['AC_TOTAL']       = $remiseACMember->getTotal();  // 合計金額
        $sendData['AC_NEXT_DATE']   = $remiseACMember->getNextDateStr(); // 次回課金日
        $sendData['AC_INTERVAL']    = strval($remiseACMember->getIntervalValue()).strval($remiseACMember->getIntervalMark());  // 決済間隔
        $sendData['AC_COUNT']       = $remiseACMember->getCount();  // 決済回数

        // セッション設定
        $this->session->set('remise_payment4.ac.front.sub_mypage_ac_card_update.redirect', 1);

        $this->logService->logInfo('Ac Mypage Update Redirect -- Done');
        return [
            'send_url'  => $sendUrl,
            'send_data' => $sendData,
        ];
    }

    /**
     * 登録カード情報更新完了通知処理
     *
     * @param
     *            $request
     *
     * @Route("/mypage/remise_payment4_ac_card_update/return/{order_id}", requirements={"order_id" = "\d+"}, name="remise_payment4_ac_mypage_card_update_return")
     */
    public function updateReturn(Request $request, $order_id = null)
    {
        $this->logService->logInfo('Ac Mypage Update Return');

        $requestData = $request->request->all();

        $this->logService->logInfo('Ac Mypage Update Return -- Request', $requestData);

        // 実行フラグ
        $updateFlag = $this->session->get('remise_payment4.ac.front.sub_mypage_ac_card_update.redirect');
        $execFlag = 0;
        if (!empty($updateFlag)) {
            $execFlag = $updateFlag;
        }
        $this->session->remove('remise_payment4.ac.front.sub_mypage_ac_card_update.redirect');

        // マイページからのリクエストのみ受け付ける
        if ($execFlag == 1) {

            // 結果取得
            $rCode = $this->utilService->getValue($requestData, 'X-R_CODE');
            $errcode = $this->utilService->getValue($requestData, 'X-ERRCODE');
            $errinfo = $this->utilService->getValue($requestData, 'X-ERRINFO');
            $errlevel = $this->utilService->getValue($requestData, 'X-ERRLEVEL');
            if (!empty($errcode)) $errcode = trim($errcode);

            // 結果判定
            $errMsg = '';
            $dispErrMsg = '';
            if ($rCode != trans('remise_payment4.common.label.r_code.success')
                || $errcode !== ""
                || $errinfo != trans('remise_payment4.common.label.errinfo.success')
                || $errlevel != trans('remise_payment4.common.label.errlevel.success')) {
                // エラーメッセージ取得
                $messageKey = 'remise_payment4.front.text.r_code.' . $rCode;
                $errMsg = trans($messageKey);
                if ($errMsg == $messageKey) {
                    $head = substr($rCode, 0, 3);
                    $tail = substr($rCode, -1);
                    if ($head == "8:1" || $head == "8:2" || $head == "8:3") {
                        if ($tail == "8") {
                            $messageKey = 'remise_payment4.front.text.r_code.8:x008';
                        } else {
                            $messageKey = 'remise_payment4.front.text.r_code.' . $head . 'xxx';
                        }
                        $errMsg = trans($messageKey);
                    }
                }
                if ($errMsg == $messageKey) {
                    $errMsg = trans('remise_payment4.front.text.r_code.error');
                }

                $dispErrMsg = $errMsg . "(" . $rCode . ")";
                if (empty($dispErrMsg)) {
                    $dispErrMsg = trans('remise_payment4.ac.front.sub_mypage_ac_card_update.text.update.error_else');
                }
                $this->logService->logError('Ac Mypage Update Return',[$dispErrMsg]);
                return $this->forwardToRoute('remise_payment4_ac_mypage_card_update', ['order_id' => $order_id,'mode' => 'complete'], ['dispErrMsg' => $dispErrMsg]);
            } else {
                // 顧客情報を取得
                $Customer = $this->getUser();

                // 受注情報を取得
                $order = $this->utilService->getOrderByOrderIdCustomer($order_id,$Customer);
                if (!$order) {
                    $dispErrMsg = trans('remise_payment4.ac.front.sub_mypage_ac_card_update.text.update.error_else');
                    $this->logService->logError('Ac Mypage Update Return',[$dispErrMsg]);
                    return $this->forwardToRoute('remise_payment4_ac_mypage_card_update', ['order_id' => $order_id,'mode' => 'complete'], ['dispErrMsg' => $dispErrMsg]);
                }

                // ルミーズ定期購買メンバー情報を取得
                $remiseACMember = $this->acService->getRemiseACMemberByOrderId($order_id);
                if (!$remiseACMember) {
                    $dispErrMsg = trans('remise_payment4.ac.front.sub_mypage_ac_card_update.text.update.error_else');
                    $this->logService->logError('Ac Mypage Update Return',[$dispErrMsg]);
                    return $this->forwardToRoute('remise_payment4_ac_mypage_card_update', ['order_id' => $order_id,'mode' => 'complete'], ['dispErrMsg' => $dispErrMsg]);
                }

                // 定期購買カード情報更新通知メール送信
                $this->acService->sendMailMypage($order, $Customer, $remiseACMember, trans('remise_payment4.common.label.mail_template.kind.mypage.card_update'));

                $this->logService->logInfo('Ac Mypage Update Return -- Done');
                return $this->forwardToRoute('remise_payment4_ac_mypage_card_update', ['order_id' => $order_id,'mode' => 'complete']);
            }
        }

        $this->logService->logInfo('Ac Mypage Update Return -- Done');
        return $this->forwardToRoute('remise_payment4_ac_mypage_card_update', ['order_id' => $order_id]);
    }

    /**
     * 定期購買スキップ画面表示
     *
     * @param $request
     * @param $order_id
     * @param $mode
     *
     * @Route("/mypage/remise_payment4_ac_skip/{order_id}", requirements={"order_id" = "\d+"}, name="remise_payment4_ac_mypage_skip")
     * @Template("@RemisePayment42/sub_mypage_ac_skip.twig")
     */
    public function skip(Request $request, $order_id = null)
    {
        // 開始ログ
        $this->logService->logInfo('Ac Mypage Skip');

        // 顧客情報を取得
        $Customer = $this->getUser();

        // 受注情報を取得
        $order = $this->utilService->getOrderByOrderIdCustomer($order_id,$Customer);
        if(!$order){
            throw new NotFoundHttpException();
        }

        // 商品明細を取得
        $orderItems = $order->getItems();
        if (! $orderItems) {
            throw new NotFoundHttpException();
        }
        $orderItem = new OrderItem();
        foreach ($orderItems as $tempOrderItem) {
            if ($tempOrderItem->isProduct()) {
                $orderItem = $tempOrderItem;
                break;
            }
        }

        // ルミーズ定期購買メンバー情報を取得
        $remiseACMember = $this->acService->getRemiseACMemberByOrderId($order_id);
        if(!$remiseACMember){
            throw new NotFoundHttpException();
        }

        // 入力フォーム（空）
        $form = $this->createForm(MyPageAcSkipType::class);
        $form->handleRequest($request);

        $dispErrMsg = null;
        $newNextDate = null;
        if ($form->isSubmitted()) {
            $dispErrMsg = $this->skipRequest($order, $Customer, $remiseACMember);
            $process = "complete";
            $dispErrMsg = $dispErrMsg;
        }else{
            $process = "init";
            // スキップ後の次回課金日を取得
            $newNextDate = $this->acService->getNextDate($remiseACMember,$remiseACMember->getNextDate());
        }

        // 終了ログ
        $this->logService->logInfo('Ac Mypage Skip -- Done');

        // 結果通知の応答
        return [
            'form' => $form->createView(),
            'order' => $order,
            'orderItem' => $orderItem,
            'remiseACMember' => $remiseACMember,
            'newNextDate' => $newNextDate,
            'process' => $process,
            'dispErrMsg' => $dispErrMsg
        ];
    }

    /**
     * 定期購買スキップリクエスト
     *
     * @param $request
     *
     * @return $dispErrMsg
     */
    private function skipRequest(Order $order, $Customer, RemiseACMember $remiseACMember)
    {
        $this->logService->logInfo('Ac Mypage Skip Request');

        $useKeizokuEditExtend = $this->acApiService->useKeizokuEditExtend();

        if(!$useKeizokuEditExtend)
        { // 自動継続課金API無効の場合
            $this->logService->logWarning('Ac Mypage Skip Request',
                Array(trans('remise_payment4.ac.front.sub_mypage_ac_skip.text.skip.warning.not_keizoku_edit_extend')));
            return trans('remise_payment4.ac.front.sub_mypage_ac_skip.text.skip.warning.not_keizoku_edit_extend');
        }

        // スキップチェック
        if($this->acService->getSkippingFlg($remiseACMember) == 2)
        {
            $this->logService->logWarning('Ac Mypage Skip Request',
                Array(trans('remise_payment4.ac.front.sub_mypage_ac_skip.text.skip.warning.skipped')));
            return trans('remise_payment4.ac.front.sub_mypage_ac_skip.text.skip.warning.skipped');
        }

        // スキップ前の情報を設定
        $remiseACMember->setSkipped(true);
        $remiseACMember->setSkippedDate(new \DateTime());
        $remiseACMember->setSkippedNextDate($remiseACMember->getNextDate());

        // スキップ後の次回課金日を取得
        $newNextDate = $this->acService->getNextDate($remiseACMember, $remiseACMember->getNextDate());

        // スキップ後の次回課金日を設定
        $remiseACMember->setNextDate($newNextDate);

        // 課金停止予定日再計算（スキップ分を加算する）
        if (!$remiseACMember->isLimitless()) {
            $newStopDate = $this->acService->getStopDate($remiseACMember, $remiseACMember->getNextDate());
            if($newStopDate != null){
                $remiseACMember->setStopDate($newStopDate);
            }
        }
        $this->entityManager->persist($remiseACMember);
        $this->entityManager->flush($remiseACMember);

        // ルミーズ定期購買関連情報セット
        $this->acService->setRemiseAcMemberRelatedOrder($remiseACMember);

        // 定期購買更新用URL呼出
        $remiseACApiResult = $this->acApiService->requestKeizokuEditExtend(1,$remiseACMember);

        // 定期購買結果取込のエラー判定
        if(!$remiseACApiResult->isResult())
        {
            $this->entityManager->rollback();
            $this->logService->logError('Ac Mypage Skip Request',[$remiseACApiResult->getErrorMessage()]);
            return $remiseACApiResult->getErrorMessage();
        }

        // メール送信 定期購買スキップ通知メール（REMISE定期購買）
        $this->acService->sendMailMypage($order, $Customer, $remiseACMember, trans('remise_payment4.common.label.mail_template.kind.mypage.skip'));

        $this->logService->logInfo('Ac Mypage Skip Request -- Done');

        return null;
    }

    /**
     * 定期購買解約画面表示
     *
     * @param $request
     * @param $order_id
     *
     * @Route("/mypage/remise_payment4_ac_cancel/{order_id}/{mode}", requirements={"order_id" = "\d+"}, name="remise_payment4_ac_mypage_cancel")
     * @Template("@RemisePayment42/sub_mypage_ac_cancel.twig")
     */
    public function cancel(Request $request, $order_id = null, $mode = null)
    {
        // 開始ログ
        $this->logService->logInfo('Ac Mypage Cancel');

        // 自動継続課金更新URL有効フラグ
        $useKeizokuEditExtend = $this->acApiService->useKeizokuEditExtend();

        // 顧客情報を取得
        $Customer = $this->getUser();

        // 受注情報を取得
        $order = $this->utilService->getOrderByOrderIdCustomer($order_id,$Customer);
        if(!$order){
            throw new NotFoundHttpException();
        }

        // 商品明細を取得
        $orderItems = $order->getItems();
        if (! $orderItems) {
            throw new NotFoundHttpException();
        }
        $orderItem = new OrderItem();
        foreach ($orderItems as $tempOrderItem) {
            if ($tempOrderItem->isProduct()) {
                $orderItem = $tempOrderItem;
                break;
            }
        }

        // ルミーズ定期購買メンバー情報を取得
        $remiseACMember = $this->acService->getRemiseACMemberByOrderId($order_id);
        if(!$remiseACMember){
            throw new NotFoundHttpException();
        }

        $ac_mail = "";
        if ($Customer) {
            // メールアドレス
            $ac_mail = $Customer->getEmail();
        } else {
            // メールアドレス
            $ac_mail = $order->getEmail();
        }

        // 入力フォーム（空）
        $form = $this->createForm(MyPageAcCancelType::class);
        $form->handleRequest($request);

        $dispErrMsg = null;
        if ($form->isSubmitted()) {
            // 自動継続課金API呼出
            $dispErrMsg = $this->cancelRequest($order, $Customer, $remiseACMember);
            $process = "complete";
            $dispErrMsg = $dispErrMsg;
        }else{
            switch ($mode) {
                case "complete": // 定期購買停止画面からのアクセス
                    $process = "complete";
                    $queryParameters = $request->query;
                    $dispErrMsg = $queryParameters->get("dispErrMsg");
                    break;
                default : // 初期アクセス
                    $process = "init";
                    break;
            }
        }

        // 終了ログ
        $this->logService->logInfo('Ac Mypage Cancel -- Done');

        // 結果通知の応答
        return [
            'form' => $form->createView(),
            'order' => $order,
            'orderItem' => $orderItem,
            'remiseACMember' => $remiseACMember,
            'process' => $process,
            'dispErrMsg' => $dispErrMsg,
            'ac_mail' => $ac_mail,
            'useKeizokuEditExtend' => $useKeizokuEditExtend
        ];
    }

    /**
     * 定期購買解約リクエスト
     *
     * @param $request
     *
     * @return $dispErrMsg
     */
    private function cancelRequest(Order $order, $Customer, RemiseACMember $remiseACMember)
    {
        $this->logService->logInfo('Ac Mypage Cancel Request');

        // 定期購買解約フラグセット
        $remiseACMember->setStatus(false);
        $this->entityManager->persist($remiseACMember);
        $this->entityManager->flush($remiseACMember);

        // ルミーズ定期購買関連情報セット
        $this->acService->setRemiseAcMemberRelatedOrder($remiseACMember);

        // 定期購買更新用URL呼出
        $remiseACApiResult = $this->acApiService->requestKeizokuEditExtend(1,$remiseACMember);

        // 定期購買結果取込のエラー判定
        if(!$remiseACApiResult->isResult())
        {
            $this->entityManager->rollback();
            $this->logService->logError('Ac Mypage Cancel Request',[$remiseACApiResult->getErrorMessage()]);
            return $remiseACApiResult->getErrorMessage();
        }

        // メール送信 定期購買解約通知メール（REMISE定期購買）
        $this->acService->sendMailMypage($order, $Customer, $remiseACMember, trans('remise_payment4.common.label.mail_template.kind.mypage.cancel'));

        $this->logService->logInfo('Ac Mypage Cancel Request -- Done');

        return null;
    }

    /**
     * 定期購買解約リダイレクト
     *
     * @param $request
     *
     * @Route("/mypage/remise_payment4_ac_cancel/redirect/{order_id}", requirements={"order_id" = "\d+"}, name="remise_payment4_ac_mypage_cancel_redirect")
     * @Template("@RemisePayment42/redirect.twig")
     */
    public function cancelRedirect(Request $request, $order_id = null)
    {
        $this->logService->logInfo('Ac Mypage Update Redirect');

        // プラグイン基本情報の取得
        $Plugin = $this->utilService->getPlugin();
        if(!$Plugin)
        {
            $this->logService->logWarning('Ac Mypage Update Redirect',
                Array(trans('remise_payment4.ac.front.sub_mypage_ac_cancel.text.canlel.warning.plugin')));
            $this->addWarning(trans('remise_payment4.ac.front.sub_mypage_ac_cancel.text.canlel.warning.plugin'));
            return $this->forwardToRoute('remise_payment4_ac_mypage_cancel', ['order_id' => $order_id]);
        }

        // プラグイン設定情報の取得
        $Config = $this->utilService->getConfig();
        if(!$Config)
        {
            $this->logService->logWarning('Ac Mypage Update Redirect',
                Array(trans('remise_payment4.ac.front.sub_mypage_ac_cancel.text.canlel.warning.plugin')));
            $this->addWarning(trans('remise_payment4.ac.front.sub_mypage_ac_cancel.text.canlel.warning.plugin'));
            return $this->forwardToRoute('remise_payment4_ac_mypage_cancel', ['order_id' => $order_id]);
        }

        // プラグイン設定詳細情報の取得
        $ConfigInfo = $Config->getUnserializeInfo();
        if(!$ConfigInfo)
        {
            $this->logService->logWarning('Ac Mypage Update Redirect',
                Array(trans('remise_payment4.ac.front.sub_mypage_ac_cancel.text.canlel.warning.plugin')));
            $this->addWarning(trans('remise_payment4.ac.front.sub_mypage_ac_cancel.text.canlel.warning.plugin'));
            return $this->forwardToRoute('remise_payment4_ac_mypage_cancel', ['order_id' => $order_id]);
        }

        // カード更新URL取得
        $sendUrl = $ConfigInfo->getCardUrl();
        if(!$sendUrl or empty($sendUrl)){
            $this->logService->logWarning('Ac Mypage Update Redirect',
                Array(trans('remise_payment4.ac.front.sub_mypage_ac_cancel.text.canlel.warning.plugin.card_url')));
            $this->addWarning(trans('remise_payment4.ac.front.sub_mypage_ac_cancel.text.canlel.warning.plugin.card_url'));
            return $this->forwardToRoute('remise_payment4_ac_mypage_cancel', ['order_id' => $order_id]);
        }

        $exitUrl = $this->generateUrl('remise_payment4_ac_mypage_cancel', ['order_id' => $order_id], UrlGeneratorInterface::ABSOLUTE_URL);
        $retUrl = $this->generateUrl('remise_payment4_ac_mypage_cancel_return', ['order_id' => $order_id], UrlGeneratorInterface::ABSOLUTE_URL);
        $ngRetUrl = $this->generateUrl('remise_payment4_ac_mypage_cancel_return', ['order_id' => $order_id], UrlGeneratorInterface::ABSOLUTE_URL);

        // 顧客情報を取得
        $Customer = $this->getUser();

        // 受注情報を取得
        $order = $this->utilService->getOrderByOrderIdCustomer($order_id,$Customer);
        if(!$order){
            throw new NotFoundHttpException();
        }

        // ルミーズ定期購買メンバー情報を取得
        $remiseACMember = $this->acService->getRemiseACMemberByOrderId($order_id);
        if(!$remiseACMember){
            throw new NotFoundHttpException();
        }

        $ac_mail = "";
        if ($Customer) {
            // メールアドレス
            $ac_mail = $Customer->getEmail();
        } else {
            // メールアドレス
            $ac_mail = $order->getEmail();
        }

        $sendData = array();
        // 基本情報部
        $sendData['ECCUBE_VER']     = Constant::VERSION;            // EC-CUBEバージョン番号
        if (!$Plugin)
        {
            $sendData[$Plugin->getCode() . '_PLG_VER'] = $Plugin->getVersion(); // ルミーズ決済プラグインバージョン番号
        }
        $sendData['SHOPCO']         = $ConfigInfo->getShopco();     // 加盟店コード
        $sendData['HOSTID']         = $ConfigInfo->getHostid();     // ホストID
        $sendData['MAIL']           = $ac_mail;                     // e-mail

        // 設定情報部
        $sendData['EXITURL']        = $exitUrl;                     // 中止URL
        $sendData['RETURL']         = $retUrl;                      // 完了通知URL
        $sendData['DIRECT']         = 'OFF';                        // ダイレクトモード
        $sendData['OPT']            = '';                           // オプション
        $sendData['NG_RETURL']      = $ngRetUrl;                    // NG完了通知URL

        // 継続課金情報部
        $sendData['AUTOCHARGE']     = trans('remise_payment4.ac.front.payment.senddata.autocharge.stop'); // 自動継続課金
        $sendData['AC_MEMBERID']    = $remiseACMember->getId();     // メンバーID

        // セッション設定
        $this->session->set('remise_payment4.ac.front.sub_mypage_ac_cancel.redirect', 1);

        $this->logService->logInfo('Ac Mypage Cancel Redirect -- Done');
        return [
            'send_url'  => $sendUrl,
            'send_data' => $sendData,
        ];
    }

    /**
     * 定期購買解約完了通知処理
     *
     * @param
     *            $request
     *
     * @Route("/mypage/remise_payment4_ac_cancel/return/{order_id}", requirements={"order_id" = "\d+"}, name="remise_payment4_ac_mypage_cancel_return")
     */
    public function cancelReturn(Request $request, $order_id = null)
    {
        $this->logService->logInfo('Ac Mypage Cancel Return');

        $requestData = $request->request->all();

        $this->logService->logInfo('Ac Mypage Cancel Return -- Request', $requestData);

        // 実行フラグ
        $cancelFlag = $this->session->get('remise_payment4.ac.front.sub_mypage_ac_cancel.redirect');
        $execFlag = 0;
        if (!empty($cancelFlag)) {
            $execFlag = $cancelFlag;
        }
        $this->session->remove('remise_payment4.ac.front.sub_mypage_ac_cancel.redirect');

        // マイページからのリクエストのみ受け付ける
        if ($execFlag == 1) {

            // 結果取得
            $rCode = $this->utilService->getValue($requestData, 'X-R_CODE');

            // 結果判定
            $errMsg = '';
            $dispErrMsg = '';
            if (strcmp($rCode, trans('remise_payment4.common.label.r_code.success')) != 0)
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

                $dispErrMsg = $errMsg . "(" . $rCode . ")";
                if(empty($dispErrMsg)){
                    $dispErrMsg = trans('remise_payment4.ac.front.sub_mypage_ac_cancel.text.cancel.error_else');
                }
                $this->logService->logError('Ac Mypage Cancel Return_1',[$dispErrMsg]);
                return $this->forwardToRoute('remise_payment4_ac_mypage_cancel', ['order_id' => $order_id,'mode' => 'complete'], ['dispErrMsg' => $dispErrMsg]);
            }else{
                $memberid = $this->utilService->getValue($requestData, 'X-AC_MEMBERID');

                // 顧客情報を取得
                $Customer = $this->getUser();

                // 受注情報を取得
                $order = $this->utilService->getOrderByOrderIdCustomer($order_id,$Customer);
                if(!$order){
                    $dispErrMsg = trans('remise_payment4.ac.front.sub_mypage_ac_cancel.text.cancel.error_else');
                    $this->logService->logError('Ac Mypage Cancel Return_2',[$dispErrMsg]);
                    return $this->forwardToRoute('remise_payment4_ac_mypage_cancel', ['order_id' => $order_id,'mode' => 'complete'], ['dispErrMsg' => $dispErrMsg]);
                }

                // ルミーズ定期購買メンバー情報を取得
                $remiseACMember = $this->acService->getRemiseACMemberByOrderId($order_id);
                if(!$remiseACMember || strcmp($memberid, $remiseACMember->getId()) != 0){
                    $dispErrMsg = trans('remise_payment4.ac.front.sub_mypage_ac_cancel.text.cancel.error_else');
                    $this->logService->logError('Ac Mypage Cancel Return_2',[$dispErrMsg]);
                    return $this->forwardToRoute('remise_payment4_ac_mypage_cancel', ['order_id' => $order_id,'mode' => 'complete'], ['dispErrMsg' => $dispErrMsg]);
                }

                // 定期購買メンバー情報を更新
                $remiseACMember->setStatus(false);
                $remiseACMember->setUpdateDate(new \DateTime());
                $this->entityManager->persist($remiseACMember);
                $this->entityManager->flush();

                // 定期購買カード情報更新通知メール送信
                $this->acService->sendMailMypage($order, $Customer, $remiseACMember, trans('remise_payment4.common.label.mail_template.kind.mypage.cancel'));

                $this->logService->logInfo('Ac Mypage Cancel Return -- Done_STOP');
                return $this->forwardToRoute('remise_payment4_ac_mypage_cancel', ['order_id' => $order_id,'mode' => 'complete']);
            }

        }

        $this->logService->logInfo('Mypage Cancel Return -- Done');
        return $this->forwardToRoute('remise_payment4_ac_mypage_cancel', ['order_id' => $order_id]);
    }
}
