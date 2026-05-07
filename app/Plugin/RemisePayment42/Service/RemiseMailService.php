<?php

namespace Plugin\RemisePayment42\Service;

use Eccube\Entity\BaseInfo;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Service\MailService;
use Plugin\RemisePayment42\Entity\RemiseACImportStack;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class RemiseMailService
{

    /**
     * @var BaseInfo
     */
    protected $baseInfo;

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
     * @var  \Twig\Environment
     */
    protected $twig;

    public function __construct(
        BaseInfoRepository $baseInfoRepository,
        MailerInterface $mailer,
        MailService $mailService,
        UtilService $utilService,
        LogService $logService,
        \Twig\Environment $twig)
    {
        $this->baseInfo = $baseInfoRepository->get();
        $this->mailer = $mailer;
        $this->mailService = $mailService;
        $this->utilService = $utilService;
        $this->logService = $logService;
        $this->twig = $twig;
    }

    /**
     * メール送信 定期購買結果バッチ取込受付メール
     *
     * @param array $postData 応答情報
     */
    public function acImportStackAccept($postData) {
        $this->logService->logInfo('Ac Import Stack Accept Send Mail -- Begin');

        try {
            $kind = trans('remise_payment4.common.label.mail_template.kind.result_import_stack.accept');

            // ルミーズメールテンプレートの取得
            $RemiseMailTemplate = $this->utilService->getRemiseMailTemplateByKind($kind);

            if (!$RemiseMailTemplate) {
                $this->logService->logError('Ac Import Stack Accept Send Mail -- Not Found RemiseMailTemplate');
                return 'NOT_FOUND_REMISE_MAIL_TEMPLATE:' . $kind;
            }

            // EC-CUBEメールテンプレートの取得
            $MailTemplate = $this->utilService->getMailTemplate($RemiseMailTemplate->getId());

            if (!$MailTemplate) {
                $this->logService->logError('Ac Import Stack Accept Send Mail -- Not Found MailTemplate (' . $RemiseMailTemplate->getId() . ')');
                return 'NOT_FOUND_MAIL_TEMPLATE:' . $RemiseMailTemplate->getId();
            }

            // メール本文生成
            $body = $this->twig->render($MailTemplate->getFileName(), [
                'importDate' => new \DateTime($postData['EXEC_DATE']),
                'importTotalCnt' => $postData['TOTAL_CNT']
            ]);

            $message = (new Email())
                ->subject('['.$this->baseInfo->getShopName().'] '.$MailTemplate->getMailSubject())
                ->from(new Address($this->baseInfo->getEmail01() , $this->baseInfo->getShopName()))
                ->to($this->baseInfo->getEmail01())
                ->replyTo($this->baseInfo->getEmail03())
                ->returnPath($this->baseInfo->getEmail04()
            );

            // HTMLテンプレートが存在する場合
            $htmlFileName = $this->mailService->getHtmlTemplate($MailTemplate->getFileName());

            if (!is_null($htmlFileName)) {
                $htmlBody = $this->twig->render($MailTemplate->getFileName(), [
                    'importDate' => new \DateTime($postData['EXEC_DATE']),
                    'importTotalCnt' => $postData['TOTAL_CNT']
                ]);

                $message
                    ->text($body)
                    ->html($htmlBody);
            } else {
                $message->text($body);
            }

            // メール送信
            $this->mailer->send($message);

            $this->logService->logInfo('Ac Import Stack Accept Send Mail -- Done');
        } catch (\Exception $e) {
            // ログ出力
            $this->logService->logError('Ac Import Stack Accept Send Mail -- Error', Array(
                trans('admin.common.system_error'),
                'ErrCode:' . $e->getCode(),
                'ErrMessage:' . $e->getMessage(),
                'ErrTrace:' . $e->getTraceAsString()
            ));
        }
    }

    /**
     * メール送信 定期購買結果バッチ取込エラーメール
     *
     * @param RemiseACImportStack $remiseACImportStack ルミーズ定期購買バッチ取込管理情報
     * @param string $errMsg エラー内容
     */
    public function acImportStackError(RemiseACImportStack $remiseACImportStack, $errMsg) {
        $this->logService->logInfo('Ac Result Batch Import Error Send Mail -- Begin');

        try {
            $kind = trans('remise_payment4.common.label.mail_template.kind.result_import_stack.error');

            // ルミーズメールテンプレートの取得
            $RemiseMailTemplate = $this->utilService->getRemiseMailTemplateByKind($kind);

            if (!$RemiseMailTemplate) {
                $this->logService->logError('Ac Result Batch Import Error Send Mail -- Not Found RemiseMailTemplate');
                return 'NOT_FOUND_REMISE_MAIL_TEMPLATE:' . $kind;
            }

            // EC-CUBEメールテンプレートの取得
            $MailTemplate = $this->utilService->getMailTemplate($RemiseMailTemplate->getId());

            if (!$MailTemplate) {
                $this->logService->logError('Ac Result Batch Import Send Mail -- Not Found MailTemplate (' . $RemiseMailTemplate->getId() . ')');
                return 'NOT_FOUND_MAIL_TEMPLATE:' . $RemiseMailTemplate->getId();
            }

            // メール本文生成
            $body = $this->twig->render($MailTemplate->getFileName(), [
                'remiseACImportStack' => $remiseACImportStack,
                'errMsg' => $errMsg
            ]);

            $message = (new Email())
                ->subject('['.$this->baseInfo->getShopName().'] '.$MailTemplate->getMailSubject())
                ->from(new Address($this->baseInfo->getEmail01() , $this->baseInfo->getShopName()))
                ->to($this->baseInfo->getEmail01())
                ->replyTo($this->baseInfo->getEmail03())
                ->returnPath($this->baseInfo->getEmail04()
            );

            // HTMLテンプレートが存在する場合
            $htmlFileName = $this->mailService->getHtmlTemplate($MailTemplate->getFileName());

            if (!is_null($htmlFileName)) {
                $htmlBody = $this->twig->render($MailTemplate->getFileName(), [
                    'remiseACImport' => $remiseACImportStack,
                    'errMsg' => $errMsg
                ]);

                $message
                    ->text($body)
                    ->html($htmlBody);
            } else {
                $message->text($body);
            }

            // メール送信
            $this->mailer->send($message);

            $this->logService->logInfo('Ac Result Batch Import Error Send Mail -- Done');
        } catch (\Exception $e) {
            // ログ出力
            $this->logService->logError('Ac Result Batch Import Error Send Mail -- Error', Array(
                trans('admin.common.system_error'),
                'ErrCode:' . $e->getCode(),
                'ErrMessage:' . $e->getMessage(),
                'ErrTrace:' . $e->getTraceAsString()
            ));
        }
    }
}