<?php
namespace Plugin\RemisePayment42\Controller;

use Doctrine\ORM\QueryBuilder;
use Eccube\Common\EccubeConfig;
use Eccube\Controller\AbstractController;
use Eccube\Entity\ExportCsvRow;
use Eccube\Service\CsvExportService;
use Eccube\Repository\Master\CsvTypeRepository;
use Eccube\Repository\CsvRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Debug\ErrorHandler;
use Plugin\RemisePayment42\Service\LogService;
use Plugin\RemisePayment42\Service\RemiseMailService;
use Plugin\RemisePayment42\Service\UtilService;
use Plugin\RemisePayment42\Service\AcService;
use Plugin\RemisePayment42\Service\AcApiService;
use Plugin\RemisePayment42\Entity\RemiseACApiResult;
use Plugin\RemisePayment42\Entity\RemiseACImport;
use Plugin\RemisePayment42\Service\MailService;

/**
 * 結果通知用
 */
class ResultAcController extends AbstractController
{
    /**
     * @var string %kernel.project_dir%
     */
    private $projectRoot;

    /**
     * @var resource
     */
    protected $fp;

    /**
     * @var boolean
     */
    protected $closed = false;

    /**
     * @var \Closure
     */
    protected $convertEncodingCallBack;

    /**
     *
     * @var CsvExportService
     */
    protected $csvExportService;

    /**
     *
     * @var CsvRepository
     */
    protected $csvRepository;

    /**
     *
     * @var CsvTypeRepository
     */
    protected $csvTypeRepository;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

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
     * @var RemiseMailService
     */
    protected $remiseMailService;

    /**
     * コンストラクタ
     *
     * @param CsvExportService $csvExportService
     * @param CsvRepository $csvRepository
     * @param CsvTypeRepository $csvTypeRepository
     * @param EccubeConfig $eccubeConfig
     * @param UtilService $utilService
     * @param LogService $logService
     * @param AcService $acService
     * @param AcApiService $acApiService
     * @param RemiseMailService $remiseMailService
     */
    public function __construct(
        CsvExportService $csvExportService,
        CsvRepository $csvRepository,
        CsvTypeRepository $csvTypeRepository,
        EccubeConfig $eccubeConfig,
        UtilService $utilService,
        LogService $logService,
        AcService $acService,
        AcApiService $acApiService,
        RemiseMailService $remiseMailService) {
        $this->csvExportService = $csvExportService;
        $this->csvRepository = $csvRepository;
        $this->csvTypeRepository = $csvTypeRepository;
        $this->eccubeConfig = $eccubeConfig;
        $this->projectRoot = $eccubeConfig->get('kernel.project_dir');
        $this->utilService = $utilService;
        $this->logService = $logService;
        $this->acService = $acService;
        $this->acApiService = $acApiService;
        $this->remiseMailService = $remiseMailService;
    }

    /**
     * 結果通知
     *
     * @param
     *            $request
     *
     * @Route("/remise_payment4_ac_result", name="remise_payment4_ac_result")
     */
    public function result(Request $request)
    {
        // 開始ログ
        $this->logService->logInfo('Ac Result');

        $requestData = $request->request->all();

        // リクエストログ
        $this->logService->logInfo('Ac Result -- Request', [
            $requestData
        ]);

        $returnContents = "";
        switch ($this->lfGetMode($requestData)) {
            // 自動継続課金結果通知処理
            case 'autocharge_complete':
                $returnContents = $this->lfRemiseAutochargeCheck($requestData);
                break;

            default:
                $error_code = 901;
                $returnContents = 'RESULT_FORMAT_ERROR:' . $error_code;
                break;
        }

        $response = new Response($returnContents, Response::HTTP_OK, array(
            'Content-Type' => 'text/html; charset=Shift_JIS'
        ));

        // 終了ログ
        $this->logService->logInfo('Ac Result -- Done');

        // 結果通知の応答
        return $response;
    }

    /**
     * 処理モード設定
     *
     * @param array $requestData
     *            リクエスト情報
     *
     * @return string $mode 処理モード名
     */
    private function lfGetMode($requestData)
    {
        $mode = '';
        // 自動継続課金結果通知処理
        if (isset($requestData["SHOPCO"]) && isset($requestData["EXEC_DATE"])) {
            $mode = 'autocharge_complete';
        }
        return $mode;
    }

    /**
     * 自動継続課金結果通知処理
     *
     * @param array $requestData
     *            リクエスト情報
     */
    private function lfRemiseAutochargeCheck($requestData)
    {
        // 疎通試験データの場合は、ここで処理抜け
        if (isset($requestData["DATA_TYPE"]) && $requestData["DATA_TYPE"] == 1) {
            return trans('remise_payment4.ac.common.const.remise_autocharge_complete_ok');
        }

        // プラグイン設定情報の取得
        $Config = $this->utilService->getConfig();
        if (! $Config) {
            $error_code = 900;
            return 'ERROR:' . $error_code;
        }

        // プラグイン設定詳細情報の取得
        $ConfigInfo = $Config->getUnserializeInfo();
        if (! $ConfigInfo) {
            $error_code = 900;
            return 'ERROR:' . $error_code;
        }

        // 加盟店コードチェック
        if (strcmp($ConfigInfo->getShopco(), $requestData['SHOPCO']) != 0) {
            $error_code = 900;
            return 'ERROR:' . $error_code;
        }

        try {
            // ルミーズ自動継続課金結果通知情報の登録
            $this->acService->acImportInsertRemiseACImport($requestData);

            // 自動継続課金結果取込を行う場合
            if ($this->acApiService->useKeizokuResultExtend()) {
                switch ($ConfigInfo->getAcAcquisitionMethod()) {
                    case 'real':
                        // リアルタイム取込
                        $error_code = $this->importOrder($requestData);
                        break;
                    case 'batch':
                        // バッチ取込
                        $error_code = $this->batchAccept($requestData);
                        break;
                    default:
                        $error_code = 'IMPORT_SETTING_ERROR:900';
                        break;
                }
            } else {
                $error_code = trans('remise_payment4.ac.common.const.remise_autocharge_complete_ok');
            }

            // 自動継続課金結果通知の応答
            return $error_code;
        } catch (\Exception $e) {
            $this->logService->logError('Ac Result Auto Import', Array(
                trans('admin.common.system_error'),
                'ErrCode:' . $e->getCode(),
                'ErrMessage:' . $e->getMessage(),
                'ErrTrace:' . $e->getTraceAsString()
            ));

            $error_code = 901;
            return 'ERROR:' . $error_code;
        }
    }

    /**
     * 自動継続課金結果取込処理
     *
     * @param array $requestData リクエスト情報
     */
    private function importOrder($requestData)
    {
        // 開始ログ
        $this->logService->logInfo('Ac Result Auto Import');

        // タイムアウト無効
        set_time_limit(0);

        // 課金日
        $chargeDate = substr($requestData["EXEC_DATE"], 0, 8);

        // ルミーズ定期購買取込情報の確認
        $checkRemiseACImport = $this->acService->checkRemiseACImport($chargeDate);

        if ($checkRemiseACImport) {
            return $checkRemiseACImport;
        }

        // ルミーズ定期購買取込情報（自動継続課金取込結果通知）を処理中に更新する
        $this->acService->acImportUpdateRemiseACImport(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_import.import_flg_prs'), $chargeDate);

        // 定期購買取込
        $returnRemiseACApiResult = new RemiseACApiResult();
        $remiseACImportInfoList = $this->acService->acImportOrder("", $chargeDate, '1', '999999', $returnRemiseACApiResult);

        // エラーあり(準正常は除く。対象がない場合など)
        if (! $returnRemiseACApiResult->isResult() && $returnRemiseACApiResult->getErrorLevel() != 0) {
            if ($returnRemiseACApiResult->getErrorLevel() == 1) {
                $this->logService->logWarning('Ac Result Auto Import', Array(
                    $returnRemiseACApiResult->getErrorMessage()
                ));
            } else {
                $this->logService->logError('Ac Result Auto Import', Array(
                    $returnRemiseACApiResult->getErrorMessage()
                ));
            }
            // ルミーズ定期購買取込情報（自動継続課金取込結果通知）をエラーに更新する
            $this->acService->acImportUpdateRemiseACImport(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_import.import_flg_err'), $chargeDate, $remiseACImportInfoList);

            // ルミーズ定期購買取込情報（自動継続課金取込結果通知）を取得
            $remiseACImportList = $this->acService->acImportGetRemiseACImport($chargeDate);

            // エラーメール送信
            $this->acService->sendMailResultImport(trans('remise_payment4.common.label.mail_template.kind.result_import.error'), $remiseACImportList[0], $remiseACImportInfoList, $returnRemiseACApiResult->getErrorMessage());

            $error_code = 201;
            return 'ERROR:' . $error_code;
        }

        if (! $returnRemiseACApiResult->isResult() && $returnRemiseACApiResult->getErrorLevel() == 0) {
            $this->logService->logInfo('Ac Result Auto Import', Array(
                $returnRemiseACApiResult->getErrorMessage()
            ));
        }

        // ルミーズ定期購買取込情報（自動継続課金取込結果通知）を完了に更新する
        $this->acService->acImportUpdateRemiseACImport(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_import.import_flg_cmp'), $chargeDate, $remiseACImportInfoList);

        // ルミーズ定期購買取込情報（自動継続課金取込結果通知）を取得
        $remiseACImportList = $this->acService->acImportGetRemiseACImport($chargeDate);

        // 一時ファイルを作成
        $tempDir = $this->projectRoot.'/var/cache/remise';
        $fileName = 'remise_result_'.(new \DateTime())->format('YmdHis').'.csv';
        $filePath = $tempDir.'/'.$fileName;

        try{
            try{
                if (!file_exists($tempDir)) {
                    @mkdir($tempDir);
                }else{
                    $this->del_file_dir($this->get_file_dir_list($tempDir));
                }
            } catch (\Exception $e) {
                $filePath = null;
            }

            if($filePath){
                $remiseCsvType = $this->acService->getRemiseCsvTypeAcResult();
                $this->exportCsv($remiseCsvType->getId(), $remiseACImportInfoList, $filePath);
            }

            // 成功メール送信
            $this->acService->sendMailResultImport(trans('remise_payment4.common.label.mail_template.kind.result_import.success'), $remiseACImportList[0], $remiseACImportInfoList, $filePath);

            // 終了ログ
            $this->logService->logInfo('Ac Result Auto Import -- Done');
        } catch (\Exception $e) {
            // ログ出力
            $this->logService->logError('Ac Result Auto Import -- Error', Array(
                trans('admin.common.system_error'),
                'ErrCode:' . $e->getCode(),
                'ErrMessage:' . $e->getMessage(),
                'ErrTrace:' . $e->getTraceAsString()
                ));

        }

        return trans('remise_payment4.ac.common.const.remise_autocharge_complete_ok');
    }

    /**
     *
     * @param Request $request
     * @param
     *            $csvTypeId
     * @param string $fileName
     *
     * @return StreamedResponse
     */
    private function exportCsv($csvTypeId, $remiseACImportInfoList, $filePath)
    {
        try{
            // タイムアウトを無効にする.
            set_time_limit(0);

            $Csvs = $this->getCsvType($csvTypeId);

            // ヘッダ行の出力.
            $ExportCsvRow = new ExportCsvRow();
            foreach ($Csvs as $Csv) {
                $ExportCsvRow->setData($Csv->getDispName());
                $ExportCsvRow->pushData();
            }

            $this->fopen($filePath);
            $this->fputcsv($ExportCsvRow->getRow());

            // データ行の出力.
            foreach ($remiseACImportInfoList as $remiseACImportInfo) {

                $ExportCsvRow = new ExportCsvRow();

                foreach ($Csvs as $Csv) {

                    switch ($Csv->getFieldName()) {
                        case "order_id": // 注文番号
                            if($remiseACImportInfo->getOrderNo()){
                                $ExportCsvRow->setData($remiseACImportInfo->getOrderNo());
                            }else{
                                $ExportCsvRow->setData(null);
                            }
                            break;
                        case "member_id": // メンバーID
                            $ExportCsvRow->setData($remiseACImportInfo->getMemberId());
                            break;
                        case "s_kaiin_no": // 会員番号
                            $ExportCsvRow->setData($remiseACImportInfo->getSKaiinNo());
                            break;
                        case "ac_amount": // 定期購買金額
                            $ExportCsvRow->setData($remiseACImportInfo->getAcAmount());
                            break;
                        case "ac_result": // 課金結果
                            if (strcmp($remiseACImportInfo->getAcResult(), trans('remise_payment4.ac.plg_remise_payment4_remise_ac_result.result.success.key')) == 0) {
                                $ExportCsvRow->setData(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_result.result.success.name'));
                            } else if (strcmp($remiseACImportInfo->getAcResult(), trans('remise_payment4.ac.plg_remise_payment4_remise_ac_result.result.failure.key')) == 0) {
                                $ExportCsvRow->setData(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_result.result.failure.name'));
                            }
                            break;
                        case "status": // 取込状況
                            if (strcmp($remiseACImportInfo->getStatus(), trans('remise_payment4.ac.plg_remise_payment4_remise_ac_import.import_flg_ini')) == 0) {
                                $ExportCsvRow->setData(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_import.import_flg_ini.value'));
                            } else if (strcmp($remiseACImportInfo->getStatus(), trans('remise_payment4.ac.plg_remise_payment4_remise_ac_import.import_flg_cmp')) == 0) {
                                $ExportCsvRow->setData(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_import.import_flg_cmp.value'));
                            }
                            break;
                        case "note": // 備考
                            $ExportCsvRow->setData($remiseACImportInfo->getNote());
                            break;
                        default:
                            $ExportCsvRow->setData(null);
                            break;
                    }
                    $ExportCsvRow->pushData();
                }

                $this->fputcsv($ExportCsvRow->getRow());
            }

            $this->fclose();
        } catch (\Exception $e) {
            // ログ出力
            $this->logService->logError('Ac Result Auto Import', Array(
                trans('admin.common.system_error'),
                'ErrCode:' . $e->getCode(),
                'ErrMessage:' . $e->getMessage(),
                'ErrTrace:' . $e->getTraceAsString()
                ));

            try{
                $this->fclose();
            } catch (\Exception $e) {}
        }
        return ;
    }

    /**
     * Csv項目を取得する
     *
     * @param
     *            $CsvType|integer
     */
    private function getCsvType($CsvTypeId)
    {
        $CsvType = $this->csvTypeRepository->find($CsvTypeId);

        $criteria = [
            'CsvType' => $CsvType,
            'enabled' => true
        ];
        $orderBy = [
            'sort_no' => 'ASC'
        ];
        $Csvs = $this->csvRepository->findBy($criteria, $orderBy);

        return $Csvs;
    }

    private function fopen($filePath)
    {
        if (is_null($this->fp) || $this->closed) {
            $this->fp = fopen($filePath, 'w');
        }
    }

    /**
     * @param $row
     */
    private function fputcsv($row)
    {
        if (is_null($this->convertEncodingCallBack)) {
            $this->convertEncodingCallBack = $this->getConvertEncodingCallback();
        }

        fputcsv($this->fp, array_map($this->convertEncodingCallBack, $row), $this->eccubeConfig['eccube_csv_export_separator']);
    }

    private function fclose()
    {
        if (!$this->closed) {
            fclose($this->fp);
            $this->closed = true;
        }
    }

    /**
     * 文字エンコーディングの変換を行うコールバック関数を返す.
     *
     * @return \Closure
     */
    private function getConvertEncodingCallback()
    {
        $config = $this->eccubeConfig;

        return function ($value) use ($config) {
            return mb_convert_encoding(
                (string) $value, $config['eccube_csv_export_encoding'], 'UTF-8'
                );
        };
    }

    /**
     * 添付ファイルの一覧を取得
     *
     */
    private function get_file_dir_list($dir=''){
        $list = array();
        foreach(glob($dir.'/*') as $file){
            if(is_file($file)){
                $list[] = $file;
            }
        }
        return $list;
    }

    /**
     * 1日前のファイルを削除
     *
     */
    private function del_file_dir( $list=array(), $expire_date_str='-1 day' ){
        //削除期限
        date_default_timezone_set('Asia/Tokyo');
        $expire_timestamp = 0;
        if (($expire_timestamp = strtotime($expire_date_str)) === false) {}

        foreach ($list as $file_path) {
            $mod = filemtime( $file_path );
            if($mod < $expire_timestamp){
                if (is_file($file_path)){
                    unlink($file_path);
                }
            }
        }
    }

    /**
     * ルミーズ定期購買バッチ取込の受付
     *
     * @param  array  $requestData  応答情報
     */
    private function batchAccept($requestData) {
        // 開始ログ
        $this->logService->logInfo('Ac Import Stack Accept -- Begin');

        // タイムアウト無効
        set_time_limit(0);

        // 課金日
        $chargeDate = substr($requestData["EXEC_DATE"], 0, 8);

        // ルミーズ定期購買取込情報の確認
        $checkRemiseACImport = $this->acService->checkRemiseACImport($chargeDate);

        if ($checkRemiseACImport) {
            return $checkRemiseACImport;
        }
        // ルミーズ定期購買バッチ取込管理情報の取得
        $remiseACImportStackList = $this->acService->acImportStackGetRemiseAcImportStack($chargeDate);

        if ($remiseACImportStackList) {
            return trans('remise_payment4.ac.common.const.remise_autocharge_complete_ok');
        }

        // ルミーズ定期購買バッチ取込管理情報を作成
        $insertAcImportStack = $this->acService->acImportStackInsertAcImportStack($requestData);

        if ($insertAcImportStack) {
            // 受付メールを送信
            $this->remiseMailService->acImportStackAccept($requestData);

            // 終了ログ
            $this->logService->logInfo('Ac Import Stack Accept -- Done');

            // 結果を返却
            return trans('remise_payment4.ac.common.const.remise_autocharge_complete_ok');
        } else {
            return 'ERROR:301';
        }
    }
}
