<?php

namespace Plugin\RemisePayment42\Controller;

use Eccube\Controller\AbstractController;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\MailHistory;
use Eccube\Entity\MailTemplate;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Entity\Payment;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\MailHistoryRepository;
use Eccube\Repository\MailTemplateRepository;
use Eccube\Service\MailService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

use Plugin\RemisePayment42\Entity\Config;
use Plugin\RemisePayment42\Entity\ConfigInfo;
use Plugin\RemisePayment42\Entity\OrderResult;
use Plugin\RemisePayment42\Entity\OrderResultCvs;
use Plugin\RemisePayment42\Entity\RemiseMailTemplate;
use Plugin\RemisePayment42\Entity\RemisePayment;
use Plugin\RemisePayment42\Service\LogService;
use Plugin\RemisePayment42\Service\UtilService;

/**
 * 収納情報通知用
 */
class ReceiptController extends AbstractController
{
    /**
     * @var Environment
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
     * @var UtilService
     */
    protected $utilService;

    /**
     * @var LogService
     */
    protected $logService;

    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

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
     * コンストラクタ
     *
     * @param Environment $twig
     * @param MailerInterface $mailer
     * @param MailService $mailService
     * @param UtilService $utilService
     * @param LogService $logService
     * @param BaseInfoRepository $baseInfoRepository
     * @param MailTemplateRepository $mailTemplateRepository
     * @param MailHistoryRepository $mailHistoryRepository
     */
    public function __construct(
        Environment $twig,
        MailerInterface $mailer,
        MailService $mailService,
        UtilService $utilService,
        LogService $logService,
        BaseInfoRepository $baseInfoRepository,
        MailTemplateRepository $mailTemplateRepository,
        MailHistoryRepository $mailHistoryRepository
    ) {
        $this->twig = $twig;
        $this->mailer = $mailer;
        $this->mailService = $mailService;
        $this->utilService = $utilService;
        $this->logService = $logService;
        $this->BaseInfo = $baseInfoRepository->get();
        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->mailHistoryRepository = $mailHistoryRepository;
    }

    /**
     * 収納情報通知
     *
     * @param  $request
     *
     * @Route("/remise_payment4_receipt", name="remise_payment4_receipt")
     */
    public function result(Request $request)
    {
        $this->logService->logInfo('Payment Cvs Receipt');

        $requestData = $request->request->all();

        $this->logService->logInfo('Payment Cvs Receipt -- Request', $requestData);

        // リクエストデータなし
        $jobid = $this->utilService->getValue($requestData, 'JOB_ID');
        if (empty($jobid))
        {
            $this->logService->logError('Payment Cvs Receipt -- Request Error');
            return new Response('REQUEST_ERROR');
        }
        // 収納済以外
        if ($this->utilService->getValue($requestData, 'REC_FLG') != '1')
        {
            $this->logService->logError('Payment Cvs Receipt -- REC_FLG Error');
            return new Response('REC_FLG_ERROR:' . $this->utilService->getValue($requestData, 'REC_FLG'));
        }

        // ルミーズ設定情報の取得
        $ConfigInfo = $this->utilService->getConfigInfo();

        if (!$ConfigInfo)
        {
            $this->logService->logError('Payment Cvs Receipt -- Not Found Config');
            return new Response('NOT_FOUND_CONFIG:' . $pluginCode);
        }

        // 入金お知らせメールを利用する場合
        $MailTemplate = null;
        if ($ConfigInfo->getAcptMailFlag() == '1')
        {
            // メール種別の取得
            $kind = trans('remise_payment4.common.label.mail_template.kind.acpt');

            // ルミーズメールテンプレートの取得
            $RemiseMailTemplate = $this->utilService->getRemiseMailTemplateByKind($kind);

            if (!$RemiseMailTemplate)
            {
                $this->logService->logError('Payment Cvs Receipt -- Not Found RemiseMailTemplate');
                return new Response('NOT_FOUND_REMISE_MAIL_TEMPLATE:' . $kind);
            }

            // EC-CUBEメールテンプレートの取得
            $MailTemplate = $this->utilService->getMailTemplate($RemiseMailTemplate->getId());

            if (!$MailTemplate)
            {
                $this->logService->logError('Payment Cvs Receipt -- Not Found MailTemplate (' . $RemiseMailTemplate->getId() . ')');
                return new Response('NOT_FOUND_MAIL_TEMPLATE:' . $RemiseMailTemplate->getId());
            }
        }

        // 受注番号
        $orderNo = $this->utilService->getValue($requestData, 'S_TORIHIKI_NO');
        // 金額
        $paymentTotal = $this->utilService->getValue($requestData, 'TOTAL');

        // 受注情報の取得
        $Order = $this->utilService->getOrderByNew($orderNo);

        if (!$Order)
        {
            $this->logService->logError('Payment Cvs Receipt -- Not Found Order (' . $orderNo . ')');
            return new Response('NOT_FOUND_ORDER:' . $orderNo);
        }

        // EC-CUBE支払方法の取得
        $Payment = $Order->getPayment();

        if (!$Payment)
        {
            $this->logService->logError('Payment Cvs Receipt -- Not Found Payment (' . $orderNo . ')');
            return new Response('NOT_FOUND_PAYMENT:' . $orderNo);
        }

        // 支払方法
        $paymentId = $Payment->getId();

        // ルミーズ支払方法の取得
        $RemisePayment = $this->utilService->getRemisePayment($paymentId);

        if (!$RemisePayment)
        {
            $this->logService->logError('Payment Cvs Receipt -- Unmatch Payment (' . $orderNo . ')');
            return new Response('UNMATCH_PAYMENT:' . $orderNo . '(' . $paymentId . ')');
        }

        // 決済履歴の取得
        $OrderResult = $this->utilService->getOrderResult($Order->getId());

        if (!$OrderResult)
        {
            $this->logService->logError('Payment Cvs Receipt -- Not Found OrderResult (' . $orderNo . ')');
            return new Response('NOT_FOUND_ORDER_RESULT:' . $orderNo);
        }

        // マルチ決済履歴詳細の取得
        $OrderResultCvs = $this->utilService->getOrderResultCvs($Order->getId());

        if (!$OrderResultCvs)
        {
            $this->logService->logError('Payment Cvs Receipt -- Not Found OrderResultCard (' . $orderNo . ')');
            return new Response('NOT_FOUND_ORDER_RESULT_CVS:' . $orderNo);
        }

        // 金額の相違
        if (round($Order->getPaymentTotal()) != $paymentTotal)
        {
            $this->logService->logError('Payment Cvs Receipt -- Unmatch Total (' . $orderNo . ', EC-CUBE:' . round($Order->getPaymentTotal()) . ', REMISE:' . $paymentTotal . ')');
            return new Response('UNMATCH_TOTAL:' . $orderNo . '(EC-CUBE=' . round($Order->getPaymentTotal()) . ',REMISE=' . $paymentTotal . ')');
        }

        // 決済履歴の設定
        $OrderResult->setUpdateDate(new \DateTime());

        // 決済履歴登録
        $this->logService->logInfo('Payment Cvs Receipt -- Save OrderResult (' . $orderNo . ')');
        $this->entityManager->persist($OrderResult);
        $this->entityManager->flush($OrderResult);

        // マルチ決済履歴詳細の設定
        $OrderResultCvs->setJobid       ($this->utilService->getValue($requestData, 'JOB_ID'        ));
        $OrderResultCvs->setRecCvscode  ($this->utilService->getValue($requestData, 'REC_CVSCODE'   ));
        $OrderResultCvs->setRecCvsname  (mb_convert_encoding($this->utilService->getValue($requestData, 'REC_CVSNAME'), "UTF-8", "SJIS"));
        $OrderResultCvs->setRecScode    ($this->utilService->getValue($requestData, 'REC_SCODE'     ));
        $OrderResultCvs->setRecDate     ($this->utilService->getDate ($requestData, 'RECDATE'       ));
        $OrderResultCvs->setCenDate     ($this->utilService->getDate ($requestData, 'CENDATE'       ));
        $OrderResultCvs->setReceiptDate(new \DateTime());
        $OrderResultCvs->setUpdateDate(new \DateTime());

        // マルチ決済履歴詳細の登録
        $this->logService->logInfo('Payment Cvs Receipt -- Save OrderResultCard (' . $orderNo . ')');
        $this->entityManager->persist($OrderResultCvs);
        $this->entityManager->flush($OrderResultCvs);

        // 入金済みに更新
        $this->logService->logInfo('Payment Cvs Receipt -- Save Order (' . $orderNo . ')');
        $Order = $this->utilService->setOrderStatusByPaid($Order);

        $this->entityManager->flush();

        // 入金お知らせメール利用する場合
        if ($ConfigInfo->getAcptMailFlag() == '1')
        {
            $this->logService->logInfo('Payment Cvs Receipt -- Send Mail (' . $orderNo . ')');

            // メール本文生成
            $body = $this->twig->render($MailTemplate->getFileName(), [
                'Order' => $Order,
            ]);

            $message = (new Email())
                ->subject('['.$this->BaseInfo->getShopName().'] '.$MailTemplate->getMailSubject())
                ->from(new Address($this->BaseInfo->getEmail01() , $this->BaseInfo->getShopName()))
                ->to($Order->getEmail())
                ->bcc($this->BaseInfo->getEmail01())
                ->replyTo($this->BaseInfo->getEmail03())
                ->returnPath($this->BaseInfo->getEmail04());

            // HTMLテンプレートが存在する場合
            $htmlFileName = $this->mailService->getHtmlTemplate($MailTemplate->getFileName());
            if (!is_null($htmlFileName)) {
                $htmlBody = $this->twig->render($htmlFileName, [
                    'Order' => $Order,
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
                $this->logService->logCritical('Payment Cvs Receipt', Array(
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
                ->setOrder($Order);

            // HTML用メールの設定
            $htmlBody = $message->getHtmlBody();
            if (!empty($htmlBody)) {
                $MailHistory->setMailHtmlBody($htmlBody);
            }

            // 送信履歴の登録
            $this->mailHistoryRepository->save($MailHistory);
        }

        // 収納情報通知の応答
        $this->logService->logInfo('Payment Cvs Receipt -- Done');
        return new Response(trans('remise_payment4.common.label.receipt.cvs.success'));
    }
}
