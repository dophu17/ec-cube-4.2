<?php

namespace Plugin\RemisePayment42\Command;

use Plugin\RemisePayment42\Controller\ResultAcController;
use Plugin\RemisePayment42\Service\AcService;
use Plugin\RemisePayment42\Service\LogService;
use Plugin\RemisePayment42\Service\RemiseMailService;
use Plugin\RemisePayment42\Entity\RemiseACApiResult;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ResultAcCronCommand extends Command {
    protected static $defaultName = 'eccube:plugin:remise-result-ac';

    /**
     * @var LogService
     */
    protected $logService;

    /**
     * @var AcService
     */
    protected $acService;

    /**
     * @var RemiseMailService
     */
    protected $remiseMailService;

    /**
     * @var ResultAcController
     */
    private $resultAcController;

    public function __construct(
        LogService $logService,
        AcService $acService,
        RemiseMailService $remiseMailService) {
            parent::__construct();
            $this->logService = $logService;
            $this->acService = $acService;
            $this->remiseMailService = $remiseMailService;
    }

    protected function configure(): void
    {
        $this->setDescription('Import Remise subscription billing result as order information.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // タイムアウト無効
        set_time_limit(0);

        // 取込日
        $chargeDate = date_format(new \DateTime('now', new \DateTimeZone('Asia/Tokyo')), 'Ymd');
        
        // ルミーズ定期購買取込情報の確認
        $checkRemiseACImport = $this->acService->checkRemiseACImport($chargeDate);
        
        if (empty($checkRemiseACImport)) {
            // ルミーズ定期購買取込情報（自動継続課金取込結果通知）を「取込中」に更新
            $this->acService->acImportUpdateRemiseACImport(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_import.import_flg_prs'), $chargeDate);
        }

        // ルミーズ定期購買バッチ取込管理情報の取得
        $remiseACImportStackList = $this->acService->acImportStackGetRemiseAcImportStack($chargeDate);

        if ($remiseACImportStackList) {
            // 開始ログ
            $this->logService->logInfo('Ac Result Batch Import -- Begin', ['charge_date' => $chargeDate]);

            // ルミーズ定期購買バッチ取込管理情報の１件目を取得
            $remiseACImportStack = $remiseACImportStackList[0];

            // 実行フラグを「済」に変更
            $this->acService->acImportStackUpdateExecFlg($remiseACImportStack);

            // パラメータを設定
            $startRow = $remiseACImportStack->getStartNum();
            $count = $remiseACImportStack->getEndNum() - $remiseACImportStack->getStartNum() + 1;

            // 定期購買課金結果取込
            $returnRemiseACApiResult = new RemiseACApiResult();
            $remiseACImportInfoList = $this->acService->acImportOrder("", $chargeDate, $startRow, $count, $returnRemiseACApiResult);

            if ($returnRemiseACApiResult->isResult()) {
                // 完了フラグを「済」に変更
                $this->acService->acImportStackUpdateCompleteFlg($remiseACImportStack);

                // ルミーズ定期購買バッチ取込情報の登録
                $this->acService->acImportStackInsertAcImportStackResult($chargeDate, $remiseACImportInfoList);
            } else {
                if ($returnRemiseACApiResult->getErrorLevel() != 0) {
                    // エラーメール送信
                    $this->remiseMailService->acImportStackError($remiseACImportStack, $returnRemiseACApiResult->getErrorMessage());
                }
            }

            // ルミーズ定期購買バッチ取込管理情報の取得
            $remiseACImportStackList = $this->acService->acImportStackGetRemiseAcImportStack($chargeDate);

            if (!$remiseACImportStackList) {
                // ルミーズ定期購買取込詳細情報の取得
                $remiseACImportInfoList = $this->acService->acImportStackGetRemiseAcImportInfo($chargeDate);

                if ($remiseACImportInfoList) {
                    // ルミーズ定期購買取込情報（自動継続課金取込結果通知）を「取込済」に更新
                    $remiseACImportList = $this->acService->acImportUpdateRemiseACImport(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_import.import_flg_cmp'), $chargeDate, $remiseACImportInfoList);

                    // 添付ファイルを作成
                    $filePath = $this->acService->createTemporaryFile($remiseACImportInfoList);

                    // 完了メールを送信
                    $this->acService->sendMailResultImport(trans('remise_payment4.common.label.mail_template.kind.result_import.success'), $remiseACImportList[0], $remiseACImportInfoList, $filePath);
                }
            }

            // 終了ログ
            $this->logService->logInfo('Ac Result Batch Import -- Done');
        }

        return 0;
    }
}
