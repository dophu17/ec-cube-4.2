<?php

namespace Plugin\RemisePayment42\Controller\Admin;

use Eccube\Controller\AbstractController;
use Eccube\Entity\MailTemplate;
use Eccube\Entity\Payment;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use Plugin\RemisePayment42\Entity\Config;
use Plugin\RemisePayment42\Entity\ConfigInfo;
use Plugin\RemisePayment42\Entity\RemiseMailTemplate;
use Plugin\RemisePayment42\Entity\RemisePayment;
use Plugin\RemisePayment42\Entity\RemiseTaxRate;
use Plugin\RemisePayment42\Form\Type\Admin\ConfigType;
use Plugin\RemisePayment42\Service\LogService;
use Plugin\RemisePayment42\Service\PaymentService;
use Plugin\RemisePayment42\Service\UtilService;
use Plugin\RemisePayment42\Service\AcService;
use Plugin\RemisePayment42\Repository\RemiseTaxRateRepository;

/**
 * プラグイン設定画面用
 */
class ConfigController extends AbstractController
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
     * @var AcService
     */
    protected $AcService;

    /**
     * @var RemiseTaxRateRepository
     */
    protected $remiseTaxRateRepository;

    /**
     * コンストラクタ
     *
     * @param UtilService $utilService
     * @param LogService $logService
     * @param AcService $acService
     * @param RemiseTaxRateRepository $remiseTaxRateRepository
     */
    public function __construct(
        UtilService $utilService,
        LogService $logService,
        AcService $acService,
        RemiseTaxRateRepository $remiseTaxRateRepository
    ) {
        $this->utilService = $utilService;
        $this->logService = $logService;
        $this->acService = $acService;
        $this->remiseTaxRateRepository = $remiseTaxRateRepository;
    }

    /**
     * 設定画面
     *
     * @param  $request
     *
     * @Route("/%eccube_admin_route%/remise_payment4/config", name="remise_payment42_admin_config")
     * @Template("@RemisePayment42/admin/config.twig")
     */
    public function index(Request $request)
    {
        $this->logService->logInfo('Plugin Config');

        // プラグイン設定情報の取得
        $Config = $this->utilService->getConfig();

        if (!$Config)
        {
            // プラグイン設定情報が存在しない場合は新規作成
            $Config = $this->utilService->createConfig();
        }

        // 設定情報の取得
        $ConfigInfo = $Config->getUnserializeInfo();

        // 定期購買用パスワードのマスク化
        $acPasswordEncriped = "";
        $acPasswordMasked = "";

        if ($ConfigInfo && mb_strlen($ConfigInfo->getAcPassword()) > 0) {
            $acPasswordEncriped = $ConfigInfo->getAcPassword();
            $acPasswordDecrypted = $this->utilService->getDecryptedStr($ConfigInfo->getAcPassword());
            $acPasswordMasked = str_repeat('*', mb_strlen($acPasswordDecrypted));
            $ConfigInfo->setAcPassword($acPasswordMasked);
        }

        // 入力フォーム
        $form = $this->createForm(ConfigType::class, $ConfigInfo);
        $form->handleRequest($request);

        // 正常登録時
        if ($form->isSubmitted() && $form->isValid() && $this->remiseTaxRateInputcheck($request)) {
            $this->logService->logInfo('Plugin Config -- Save');

            // 入力フォームから設定情報の取得
            $ConfigInfo = $form->getData();

            // 定期購買用パスワードの暗号化
            if (mb_strlen($ConfigInfo->getAcPassword()) > 0 && strcmp($ConfigInfo->getAcPassword(), $acPasswordMasked) != 0) {
                $ConfigInfo->setAcPassword($this->utilService->getEncryptedStr($ConfigInfo->getAcPassword()));
            } elseif (mb_strlen($ConfigInfo->getAcPassword()) == 0) {
                $ConfigInfo->setAcPassword("");
            } else {
                $ConfigInfo->setAcPassword($acPasswordEncriped);
            }

            // 設定情報の設定
            $Config->setSerializeInfo($ConfigInfo);

            // プラグイン設定情報の登録
            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);

            // 支払方法登録
            $this->registPayment($ConfigInfo);
//             // メールテンプレート登録
//             $this->registMailTemplateAcpt();

            // 定期購買用初期設定処理
            if($ConfigInfo->useOptionAC())
            {
                // ルミーズ販売種別の追加
                $this->acService->insertRemiseSaleType();
                // 配送先の追加
                $this->acService->insertDelivery();

            }

            // マルチ決済 消費税率設定
            $requestData = $request->request->all();
            $multi_flg = false;
            foreach ($requestData['config']['use_payment'] as $use_payment)
            {
                // マルチ決済
                if (strcmp($use_payment,trans('remise_payment4.common.label.payment.kind.cvs'))==0)
                {
                    $multi_flg = true;
                }
            }
            if($multi_flg){

                $requestDataRemiseRaxRateArray = $requestData['config_tax']['remise_tax_rate'];
                $currentDateTime = new \DateTime();
                foreach ($requestDataRemiseRaxRateArray as $key => $requestDataRemiseRaxRate) {

                    if (strcmp($requestDataRemiseRaxRate['mode'],"new") == 0) {
                        // 新規データ作成
                        $remiseTaxRate = new RemiseTaxRate();
                        $remiseTaxRate->setTaxRate($requestDataRemiseRaxRate['tax_rate']);
                        $remiseTaxRate->setCalcRule($requestDataRemiseRaxRate['calc_rule']);
                        $remiseTaxRate->setApplyDate(new \DateTime($requestDataRemiseRaxRate['apply_date']));
                        $remiseTaxRate->setCreateDate($currentDateTime);
                        $remiseTaxRate->setUpdateDate($currentDateTime);

                        // INSERT
                        $this->entityManager->persist($remiseTaxRate);
                        $this->entityManager->flush();
                    } else if (strcmp($requestDataRemiseRaxRate['mode'],"upd") == 0) {
                        // 更新データ作成
                        $remiseTaxRate = $this->remiseTaxRateRepository->findOneById(array('id' => $key));

                        if(!$remiseTaxRate){
                            // 更新対象データが存在しない場合、次の処理へ
                            continue;
                        }
                        // 1つでも変更があれば更新する
                        if($remiseTaxRate->getTaxRate() != $requestDataRemiseRaxRate['tax_rate']
                            || $remiseTaxRate->getCalcRule() != $requestDataRemiseRaxRate['calc_rule']
                            || $remiseTaxRate->getApplyDate() != $requestDataRemiseRaxRate['apply_date']){

                                $remiseTaxRate->setTaxRate($requestDataRemiseRaxRate['tax_rate']);
                                $remiseTaxRate->setCalcRule($requestDataRemiseRaxRate['calc_rule']);
                                $remiseTaxRate->setApplyDate(new \DateTime($requestDataRemiseRaxRate['apply_date']));
                                $remiseTaxRate->setUpdateDate($currentDateTime);

                                // UPDATE
                                $this->entityManager->persist($remiseTaxRate);
                                $this->entityManager->flush();
                        }
                    } else if (strcmp($requestDataRemiseRaxRate['mode'],"del") == 0) {
                        // 削除データ作成
                        $remiseTaxRate = $this->remiseTaxRateRepository->findOneById(array('id' => $key));

                        if(!$remiseTaxRate){
                            // 削除対象データが存在しない場合、次の処理へ
                            continue;
                        }

                        // DELETE
                        $this->entityManager->remove($remiseTaxRate);
                        $this->entityManager->flush();
                    }
                }
            }

            // カード決済設定 設定無しの場合に、入力がある場合警告を出す
            if(!$ConfigInfo->usePaymentCard())
            {
                if($ConfigInfo->getCardUrl())
                {
                    $this->addWarning(trans('remise_payment4.admin_config.text.warning.card'), 'admin');
                }
            }

            // マルチ決済設定 設定無しの場合に、入力がある場合警告を出す
            if(!$ConfigInfo->usePaymentCvs())
            {
                if($ConfigInfo->getCvsUrl())
                {
                    $this->addWarning(trans('remise_payment4.admin_config.text.warning.cvs'), 'admin');
                }
            }

            // カード拡張セット設定 設定無しの場合に、入力がある場合警告を出す
            if(!$ConfigInfo->useOptionExtset())
            {
                if($ConfigInfo->getExtsetHostid() || $ConfigInfo->getExtsetCardUrl())
                {
                    $this->addWarning(trans('remise_payment4.admin_config.text.warning.extset'), 'admin');
                }
            }

            // 定期購買設定 設定無しの場合に、入力がある場合警告を出す
            if(!$ConfigInfo->useOptionAC())
            {
                if($ConfigInfo->getAcAppid() || $ConfigInfo->getAcPassword() || $ConfigInfo->getAcResultUrl() || $ConfigInfo->getAcEditUrl())
                {
                    $this->addWarning(trans('remise_payment4.admin_config.text.warning.ac'), 'admin');
                }
            }

            $this->logService->logInfo('Plugin Config -- Done');

            $this->addSuccess(trans('remise_payment4.admin_config.text.complete'), 'admin');
            return $this->redirectToRoute('remise_payment42_admin_config');
        }

        // 税率設定取得
        $currentTaxRate = $this->remiseTaxRateRepository->getCurrentTaxRate();
        $allTaxRateArray = $this->remiseTaxRateRepository->getAllTaxRate();

        return [
            'form' => $form->createView(),
            'currentTaxRate' => $currentTaxRate,
            'allTaxRateArray' => $allTaxRateArray,
        ];
    }

    /**
     * 支払方法登録
     *
     * @param  $ConfigInfo  設定情報
     */
    protected function registPayment($ConfigInfo)
    {
        // マルチ決済
        $this->registPaymentSub(
            $ConfigInfo->usePaymentCvs(),
            trans('remise_payment4.common.label.payment.kind.cvs'),
            trans('remise_payment4.common.label.payment.cvs')
        );
        // カード決済
        $this->registPaymentSub(
            $ConfigInfo->usePaymentCard(),
            trans('remise_payment4.common.label.payment.kind.card'),
            trans('remise_payment4.common.label.payment.card')
        );
    }

    /**
     * 支払方法登録
     *
     * @param  $use  利用判定
     * @param  $kind  支払方法（1:カード決済、2:マルチ決済）
     * @param  $method  支払方法名
     */
    protected function registPaymentSub($use, $kind, $method)
    {
        // ルミーズ支払方法の取得
        $RemisePayments = $this->utilService->getRemisePaymentByKind($kind);

        // 決済利用
        if ($use)
        {
            // ルミーズ支払方法が未登録の場合
            if (!$RemisePayments)
            {
                $this->logService->logInfo('Plugin Config -- Create New Payment');

                // EC-CUBE支払方法の新規生成
                $Payment = $this->utilService->createPayment($method);

                // EC-CUBE支払方法の登録
                $this->entityManager->persist($Payment);
                $this->entityManager->flush($Payment);

                $this->logService->logInfo('Plugin Config -- Create New RemisePayment (' . $Payment->getId() . ')');

                // ルミーズ支払方法の新規作成
                $RemisePayment = new RemisePayment();
                $RemisePayment->setId($Payment->getId());
                $RemisePayment->setKind($kind);
                $RemisePayment->setCreateDate(new \DateTime());
                $RemisePayment->setUpdateDate(new \DateTime());

                // ルミーズ支払方法の登録
                $this->entityManager->persist($RemisePayment);
                $this->entityManager->flush($RemisePayment);
            }
            // ルミーズ支払方法が登録済の場合
            else
            {
                $isPayment = false;
                foreach ($RemisePayments as $RemisePayment)
                {
                    $this->logService->logInfo('Plugin Config -- Found RemisePayment (' . $RemisePayment->getId() . ')');

                    // EC-CUBE支払方法の取得
                    $Payment = $this->utilService->getPayment($RemisePayment->getId());

                    // 取得できなかった場合
                    if (!$Payment)
                    {
                        $this->logService->logInfo('Plugin Config -- Not Found Payment : Delete RemisePayment (' . $RemisePayment->getId() . ')');

                        // ルミーズ支払方法削除
                        $this->utilService->deleteRemisePayment($RemisePayment);
                        $this->entityManager->flush();
                    }
                    // 取得できた場合
                    else
                    {
                        $this->logService->logInfo('Plugin Config -- Found Payment');

                        $isPayment = true;

                        // EC-CUBE支払方法の設定
                        $Payment->setMethodClass(PaymentService::class);
                        $Payment->setVisible(true);
                        $Payment->setUpdateDate(new \DateTime());

                        // EC-CUBE支払方法の更新
                        $this->entityManager->persist($Payment);
                        $this->entityManager->flush($Payment);

                        // ルミーズ支払方法の設定
                        $RemisePayment->setUpdateDate(new \DateTime());

                        // ルミーズ支払方法の登録
                        $this->entityManager->persist($RemisePayment);
                        $this->entityManager->flush($RemisePayment);
                    }
                }
                if (!$isPayment)
                {
                    // EC-CUBE支払方法の新規生成
                    $Payment = $this->utilService->createPayment($method);

                    // EC-CUBE支払方法の登録
                    $this->entityManager->persist($Payment);
                    $this->entityManager->flush($Payment);

                    $this->logService->logInfo('Plugin Config -- Create New RemisePayment (' . $Payment->getId() . ')');

                    // ルミーズ支払方法の新規作成
                    $RemisePayment = new RemisePayment();
                    $RemisePayment->setId($Payment->getId());
                    $RemisePayment->setKind($kind);
                    $RemisePayment->setCreateDate(new \DateTime());
                    $RemisePayment->setUpdateDate(new \DateTime());

                    // ルミーズ支払方法の登録
                    $this->entityManager->persist($RemisePayment);
                    $this->entityManager->flush($RemisePayment);
                }
            }
        }
        // 決済未利用
        else
        {
            // ルミーズ支払方法が未登録の場合
            if (!$RemisePayments)
            {
                // 特に処理の必要なし
                return;
            }
            // ルミーズ支払方法が登録済の場合
            else
            {
                foreach ($RemisePayments as $RemisePayment)
                {
                    $this->logService->logInfo('Plugin Config -- Found RemisePayment (' . $RemisePayment->getId() . ')');

                    // EC-CUBE支払方法の取得
                    $Payment = $this->utilService->getPayment($RemisePayment->getId());

                    // 取得できなかった場合
                    if (!$Payment)
                    {
                        $this->logService->logInfo('Plugin Config -- Not Found Payment : Delete RemisePayment (' . $RemisePayment->getId() . ')');

                        // ルミーズ支払方法削除
                        $this->utilService->deleteRemisePayment($RemisePayment);
                        $this->entityManager->flush();
                    }
                    // 取得できた場合
                    else
                    {
                        $this->logService->logInfo('Plugin Config -- Found Payment');

                        // EC-CUBE支払方法の設定
                        $Payment->setMethodClass(PaymentService::class);
                        $Payment->setVisible(false);
                        $Payment->setUpdateDate(new \DateTime());

                        // EC-CUBE支払方法の更新
                        $this->entityManager->persist($Payment);
                        $this->entityManager->flush($Payment);

                        // ルミーズ支払方法の設定
                        $RemisePayment->setUpdateDate(new \DateTime());

                        // ルミーズ支払方法の登録
                        $this->entityManager->persist($RemisePayment);
                        $this->entityManager->flush($RemisePayment);
                    }
                }
            }
        }
    }

//     /**
//      * メールテンプレート登録
//      */
//     protected function registMailTemplateAcpt()
//     {
//         // 入金のお知らせメール
//         $kind = trans('remise_payment4.common.label.mail_template.kind.acpt');

//         // ルミーズメールテンプレートの取得
//         $RemiseMailTemplate = $this->utilService->getRemiseMailTemplateByKind($kind);

//         // EC-CUBEメールテンプレート
//         $MailTemplate = null;

//         // ルミーズメールテンプレートが未登録の場合
//         if (!$RemiseMailTemplate)
//         {
//             $this->logService->logInfo('Plugin Config -- Create New MailTemplate');

//             // EC-CUBEメールテンプレートの新規生成
//             $MailTemplate = $this->utilService->createMailTemplate($kind);

//             // EC-CUBEメールテンプレートの登録
//             $this->entityManager->persist($MailTemplate);
//             $this->entityManager->flush($MailTemplate);

//             $this->logService->logInfo('Plugin Config -- Create New RemiseMailTemplate (' . $MailTemplate->getId() . ')');

//             // ルミーズメールテンプレートの新規作成
//             $RemiseMailTemplate = new RemiseMailTemplate();
//             $RemiseMailTemplate->setId($MailTemplate->getId());
//             $RemiseMailTemplate->setKind($kind);
//             $RemiseMailTemplate->setCreateDate(new \DateTime());
//         }
//         // ルミーズメールテンプレートが登録済の場合
//         else
//         {
//             $this->logService->logInfo('Plugin Config -- Found RemiseMailTemplate (' . $RemiseMailTemplate->getId() . ')');

//             // EC-CUBEメールテンプレートの取得
//             $MailTemplate = $this->utilService->getMailTemplate($RemiseMailTemplate->getId());

//             // 取得できなかった場合
//             if (!$MailTemplate)
//             {
//                 $this->logService->logInfo('Plugin Config -- Create New MailTemplate');

//                 // EC-CUBEメールテンプレートの新規生成
//                 $MailTemplate = $this->utilService->createMailTemplate($kind);

//                 // EC-CUBEメールテンプレートの登録
//                 $this->entityManager->persist($MailTemplate);
//                 $this->entityManager->flush($MailTemplate);

//                 $RemiseMailTemplate->setId($MailTemplate->getId());
//             }
//             // 取得できた場合
//             else
//             {
//                 $this->logService->logInfo('Plugin Config -- Found MailTemplate');
//             }
//         }

//         $this->logService->logInfo('Plugin Config -- Create New RemisePayment (' . $RemiseMailTemplate->getId() . ')');

//         // ルミーズメールテンプレートの更新
//         $RemiseMailTemplate->setUpdateDate(new \DateTime());

//         // ルミーズメールテンプレートの登録
//         $this->entityManager->persist($RemiseMailTemplate);
//         $this->entityManager->flush($RemiseMailTemplate);
//     }

    /**
     * 支払方法複製
     *
     * @param  $request
     * @param  $id  EC-CUBE支払方法ID
     *
     * @Route("/%eccube_admin_route%/remise_payment4/payment_copy/{id}", requirements={"id" = "\d+"}, name="remise_payment4_admin_payment_copy")
     */
    public function copyPayment(Request $request, $id)
    {
        $this->logService->logInfo('Payment Copy');

        if (empty($id))
        {
            $this->logService->logError('Payment Copy -- Empty Id');
            return $this->redirectToRoute('admin_setting_shop_payment');
        }

        // ルミーズ支払方法の取得
        $RemisePayment = $this->utilService->getRemisePayment($id);

        if (!$RemisePayment)
        {
            $this->logService->logError('Payment Copy -- Not Found RemisePayment (' . $id . ')');
            return $this->redirectToRoute('admin_setting_shop_payment');
        }

        // EC-CUBE支払方法の取得
        $Payment = $this->utilService->getPayment($id);

        if (!$Payment)
        {
            $this->logService->logError('Payment Copy -- Not Found Payment (' . $id . ')');
            return $this->redirectToRoute('admin_setting_shop_payment');
        }

        $this->logService->logInfo('Payment Copy -- Create New Payment');

        // EC-CUBE支払方法の新規生成
        $PaymentNew = $this->utilService->createPayment($Payment->getMethod());

        $PaymentNew->setMethod($Payment->getMethod());
        $PaymentNew->setCharge($Payment->getCharge());
        $PaymentNew->setRuleMax($Payment->getRuleMax());
        $PaymentNew->setFixed($Payment->isFixed());
        $PaymentNew->setPaymentImage($Payment->getPaymentImage());
        $PaymentNew->setRuleMin($Payment->getRuleMin());
        $PaymentNew->setMethodClass($Payment->getMethodClass());
        $PaymentNew->setVisible($Payment->isVisible());
        $PaymentNew->setCreateDate(new \DateTime());
        $PaymentNew->setUpdateDate(new \DateTime());

        // EC-CUBE支払方法の登録
        $this->entityManager->persist($PaymentNew);
        $this->entityManager->flush($PaymentNew);

        $this->logService->logInfo('Payment Copy -- Create New RemisePayment');

        // ルミーズ支払方法の新規作成
        $RemisePaymentNew = new RemisePayment();
        $RemisePaymentNew->setId($PaymentNew->getId());
        $RemisePaymentNew->setKind($RemisePayment->getKind());
        $RemisePaymentNew->setCreateDate(new \DateTime());
        $RemisePaymentNew->setUpdateDate(new \DateTime());

        // ルミーズ支払方法の登録
        $this->entityManager->persist($RemisePaymentNew);
        $this->entityManager->flush($RemisePaymentNew);

        $this->logService->logInfo('Payment Copy -- Done');

        $this->addSuccess(trans('remise_payment4.admin_payment.text.copy_complete'), 'admin');
        return $this->redirectToRoute('admin_setting_shop_payment');
    }


    /**
     * 税率設定の入力チェック
     *
     */
    private function remiseTaxRateInputcheck($request)
    {
        // リクエスト情報
        $requestData = $request->request->all();

        // 設定情報
        $config = $requestData['config'];

        // マルチ決済有効フラグ
        $multi_flg = false;

        foreach ($config['use_payment'] as $use_payment)
        {
            // マルチ決済
            if (strcmp($use_payment,trans('remise_payment4.common.label.payment.kind.cvs'))==0)
            {
                $multi_flg = true;
            }
        }

        // マルチ決済が無効の場合は処理を抜ける
        if(!$multi_flg){
            return true;
        }

        // 税率設定の項目が無い
        if(!array_key_exists('config_tax', $requestData))
        {
            $this->addWarning(trans('remise_payment4.admin_config.text.tax_rate_errmsg.1'), 'admin');
            return false;
        }

        $configTax = $requestData['config_tax'];

        // 税率設定の項目が無い
        if(!array_key_exists('remise_tax_rate', $configTax))
        {
            $this->addWarning(trans('remise_payment4.admin_config.text.tax_rate_errmsg.1'), 'admin');
            return false;
        }

        $remiseRaxRateArray = $configTax['remise_tax_rate'];

        // 各設定値の確認
        foreach ($remiseRaxRateArray as $key => $remiseRaxRate) {

            // モード
            if(!array_key_exists('mode', $remiseRaxRate) ||
                (strcmp($remiseRaxRate['mode'],"new") != 0 && strcmp($remiseRaxRate['mode'],"upd") != 0 && strcmp($remiseRaxRate['mode'],"del") != 0))
            {

                $this->addWarning(trans('remise_payment4.admin_config.text.tax_rate_errmsg.2',['%errId%' => $key]), 'admin');
                return false;
            }

            // 新規追加または編集の場合
            if(strcmp($remiseRaxRate['mode'],"new") == 0 || strcmp($remiseRaxRate['mode'],"upd") == 0 ){

                if(!array_key_exists('tax_rate', $remiseRaxRate)
                    || !array_key_exists('calc_rule', $remiseRaxRate)
                    || !array_key_exists('apply_date', $remiseRaxRate)){

                        $this->addWarning(trans('remise_payment4.admin_config.text.tax_rate_errmsg.3',['%errId%' => $key]), 'admin');
                        return false;
                }

                // 消費税率
                if(empty($remiseRaxRate['tax_rate']) || !($remiseRaxRate['tax_rate'] >= 1 && $remiseRaxRate['tax_rate'] <= 100)){
                    $$this->addWarning(trans('remise_payment4.admin_config.text.tax_rate_errmsg.4',['%errId%' => $key]), 'admin');
                    return false;
                }

                // 課税規則
                if(empty($remiseRaxRate['calc_rule']) ||
                    (strcmp($remiseRaxRate['calc_rule'],"1") != 0
                        && strcmp($remiseRaxRate['calc_rule'],"2") != 0
                        && strcmp($remiseRaxRate['calc_rule'],"3") != 0)
                    ){
                        $this->addWarning(trans('remise_payment4.admin_config.text.tax_rate_errmsg.5',['%errId%' => $key]), 'admin');
                        return false;
                }

                // dateタグ有効の場合、Tをスペース、-を/に置換
                if(!empty($remiseRaxRate['apply_date']) &&
                    (
                        preg_match('/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}T[0-9]{2}\:[0-9]{2}$/', $remiseRaxRate['apply_date']) ||
                        preg_match('/^[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}\:[0-9]{2}$/', $remiseRaxRate['apply_date'])
                    )
                    ){
                    $remiseRaxRate['apply_date'] = str_replace('T', ' ', $remiseRaxRate['apply_date']);
                    $remiseRaxRate['apply_date'] = str_replace('-', '/', $remiseRaxRate['apply_date']);
                }

                // dateタグ有効の場合、Tをスペース、-を/に置換
                if(!empty($remiseRaxRate['apply_date']) && preg_match('/^[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}\:[0-9]{2}$/', $remiseRaxRate['apply_date'])){
                    $remiseRaxRate['apply_date'] = str_replace('T', ' ', $remiseRaxRate['apply_date']);
                    $remiseRaxRate['apply_date'] = str_replace('-', '/', $remiseRaxRate['apply_date']);
                }

                // 適用日時
                if(empty($remiseRaxRate['apply_date'])
                    || strlen($remiseRaxRate['apply_date']) < 16
                    || preg_match("/[a-zA-Z]/",$remiseRaxRate['apply_date'])
                    || !preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2} [0-9]{2}\:[0-9]{2}$/', $remiseRaxRate['apply_date'])){
                        $this->addWarning(trans('remise_payment4.admin_config.text.tax_rate_errmsg.6',['%errId%' => $key]), 'admin');
                        return false;
                }

                // 適用日時を日付に変換できるかチェック
                try {
                    $format_date = new \DateTime($remiseRaxRate['apply_date']);
                    $format_date->format('Y/m/d H:i');
                } catch (\Exception $e) {
                    $this->addWarning(trans('remise_payment4.admin_config.text.tax_rate_errmsg.6',['%errId%' => $key]), 'admin');
                    return false;
                }
            }

        }

        // 有効である税率設定が1件以上あるか確認
        $yukoFlg = false;
        $currentDateTime = new \DateTime();
        foreach ($remiseRaxRateArray as $key => $remiseRaxRate) {

            // 新規追加または編集の場合
            if(strcmp($remiseRaxRate['mode'],"new") == 0 || strcmp($remiseRaxRate['mode'],"upd") == 0 ){
                $apply_date = new \DateTime($remiseRaxRate['apply_date']);
                if($currentDateTime >= $apply_date){
                    $yukoFlg = true;
                    break;
                }
            }

        }

        if($yukoFlg == false){
            $this->addWarning(trans('remise_payment4.admin_config.text.tax_rate_errmsg.7'), 'admin');
            return false;
        }

        return true;
    }

}
