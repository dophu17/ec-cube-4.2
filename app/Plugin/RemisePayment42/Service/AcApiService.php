<?php
namespace Plugin\RemisePayment42\Service;

use Symfony\Component\DomCrawler\Crawler;

use Doctrine\ORM\EntityManagerInterface;

use Plugin\RemisePayment42\Entity\RemiseACApiResult;
use Plugin\RemisePayment42\Service\LogService;
use Plugin\RemisePayment42\Service\UtilService;
use Plugin\RemisePayment42\Entity\RemiseACMember;

/**
 * 定期購買処理
 */
class AcApiService
{

    /**
     *
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $entityManager;

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
     * コンストラクタ
     *
     * @param EntityManagerInterface $entityManager
     * @param UtilService $utilService
     * @param LogService $logService
     */
    public function __construct(EntityManagerInterface $entityManager, UtilService $utilService, LogService $logService)
    {
        $this->entityManager = $entityManager;
        $this->utilService = $utilService;
        $this->logService = $logService;
    }

    /**
     * 定期購買結果取込用URLの設定有無を確認する
     *
     * @return bool
     */
    public function useKeizokuResultExtend()
    {
        // プラグイン設定情報の取得
        $Config = $this->utilService->getConfig();
        if ($Config) {
            // 設定情報の取得
            $ConfigInfo = $Config->getUnserializeInfo();
            if ($ConfigInfo) {
                // 定期購買が有効の場合
                if ($ConfigInfo->useOptionAC()) {
                    if ($ConfigInfo->getAcAppid() && $ConfigInfo->getAcPassword() && $ConfigInfo->getAcResultUrl()) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * 定期購買更新用URLの設定有無を確認する
     *
     * @return bool
     */
    public function useKeizokuEditExtend()
    {
        // プラグイン設定情報の取得
        $Config = $this->utilService->getConfig();
        if ($Config) {
            // 設定情報の取得
            $ConfigInfo = $Config->getUnserializeInfo();
            if ($ConfigInfo) {
                // 定期購買が有効の場合
                if ($ConfigInfo->useOptionAC()) {
                    if ($ConfigInfo->getAcAppid() && $ConfigInfo->getAcPassword() && $ConfigInfo->getAcEditUrl()) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * リクエスト処理
     *
     * @param
     *            $url
     * @param
     *            $headers
     * @param
     *            $postData
     *
     * @return $curlResult
     */
    public function curlRequest($url, $headers, $postData)
    {
        try {
            $curlResult = null;

            $curl = curl_init($url);

            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            // ヘッダ
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            // パラメータ
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postData));

            // 実行
            $curlResult['curlResponse'] = curl_exec($curl);
            $curlResult['curlInfo'] = curl_getinfo($curl);
            $curlResult['curlError'] = curl_error($curl);
            $curlResult['xmlData'] = "";

            // 接続エラー
            if ($curlResult['curlResponse'] === false)
            {
                return $curlResult;
            }
            // 接続正常
            else
            {
                $xml = simplexml_load_string($curlResult['curlResponse']);
                // 解析エラー
                if ($xml === false)
                {
                    return $curlResult;
                }
                // 解析正常
                else
                {
                    $xmlList = $this->parseXml($xml);
                    $curlResult['xmlData'] = $xmlList;
                }
            }

            // 終了
            curl_close($curl);
        }
        catch (\Exception $e)
        {
            // 終了
            if ($curl != null) curl_close($curl);
            throw $e;
        }

        return $curlResult;
    }

    /**
     * API結果の解析
     *
     * @param   Xml $xml    XMLデータ
     *
     * @return  array  $xmlList  結果データ
     */
    public function parseXml($xml)
    {
        $xmlList = array();
        $xmlObj = get_object_vars($xml);
        foreach ($xmlObj as $key => $val)
        {
            if (is_object($xmlObj[$key]))
            {
                $obj = $this->parseXml($val);
                if (empty($obj)) $obj = "";
                $xmlList[$key] = $obj;
            }
            else if (is_array($val))
            {
                foreach($val as $k => $v)
                {
                    if (is_object($v) || is_array($v))
                    {
                        $obj = $this->parseXml($v);
                        if (empty($obj)) $obj = "";
                        $xmlList[$key][$k] = $obj;
                    }
                    else
                    {
                        $arr[$key][$k] = $v;
                    }
                }
            }
            else
            {
                $xmlList[$key] = $val;
            }
        }
        return $xmlList;
    }

    /**
     * 定期購買結果取込用URLを呼び出す
     *
     * @param $memberId
     * @param $chargeDate
     * @param $startRow
     * @param $count
     *
     * @return RemiseACApiResult
     */
    public function requestKeizokuResultExtend($memberId, $chargeDate, $startRow, $count)
    {
        // 開始ログ
        $this->logService->logInfo('Request KeizokuResultExtend -- Begin', ['member_id' => $memberId, 'charge_date' => $chargeDate, 'start_row' => $startRow, 'count' => $count]);

        $remiseACApiResult = new RemiseACApiResult();

        // プラグイン基本情報の取得
        $Plugin = $this->utilService->getPlugin();
        if(!$Plugin)
        {
            $remiseACApiResult->setResult(false);
            $remiseACApiResult->setErrorLevel(1);
            $remiseACApiResult->setErrorMessage(trans('remise_payment4.ac.common.text.warning.plugin'));
            $this->logService->logWarning('Request KeizokuResultExtend',[$remiseACApiResult->getErrorMessage()]);
            return $remiseACApiResult;
        }

        // プラグイン設定情報の取得
        $Config = $this->utilService->getConfig();
        if(!$Config)
        {
            $remiseACApiResult->setResult(false);
            $remiseACApiResult->setErrorLevel(1);
            $remiseACApiResult->setErrorMessage(trans('remise_payment4.ac.common.text.warning.plugin'));
            $this->logService->logWarning('Request KeizokuResultExtend',[$remiseACApiResult->getErrorMessage()]);
            return $remiseACApiResult;
        }

        // プラグイン設定詳細情報の取得
        $ConfigInfo = $Config->getUnserializeInfo();
        if(!$ConfigInfo)
        {
            $remiseACApiResult->setResult(false);
            $remiseACApiResult->setErrorLevel(1);
            $remiseACApiResult->setErrorMessage(trans('remise_payment4.ac.common.text.warning.plugin'));
            $this->logService->logWarning('Request KeizokuResultExtend',[$remiseACApiResult->getErrorMessage()]);
            return $remiseACApiResult;
        }

        // 定期購買結果取込用URL取得
        $requestUrl = $ConfigInfo->getAcResultUrl();
        if(!$requestUrl or empty($requestUrl)){
            $remiseACApiResult->setResult(false);
            $remiseACApiResult->setErrorLevel(1);
            $remiseACApiResult->setErrorMessage(trans('remise_payment4.ac.common.text.warning.plugin.ac_result_url'));
            $this->logService->logWarning('Request KeizokuResultExtend',[$remiseACApiResult->getErrorMessage()]);
            return $remiseACApiResult;
        }

        // ヘッダ
        $requestHeaders = array(
            "User-Agent:" . trans('remise_payment4.extset.common.label.plugin_code') . '_PLG_VER ' . $Plugin->getVersion(),
        );

        // パラメータ
        $requestPostData = array(
            'APPID'         => $ConfigInfo->getAcAppid(),
            'PASSWORD'      => $this->utilService->getDecryptedStr($ConfigInfo->getAcPassword()),
            'SHOPCO'        => $ConfigInfo->getShopco(),
            'AC_MEMBERID'   => $memberId,
            'AC_S_KAIIN_NO' => "",
            'AC_CHARGE_DATE'=> $chargeDate,
            'AC_RESULT'     => "5", // 全て
            'S_ROWNUM'      => $startRow,
            'COUNT'         => $count,
            'PROC'          => "",
        );

        // 自動継続課金APIリクエスト実行
        $curlResult = $this->curlRequest($requestUrl, $requestHeaders, $requestPostData);

        // 接続エラー
        if ($curlResult['curlResponse'] === false)
        {
            $remiseACApiResult->setResult(false);
            $remiseACApiResult->setErrorLevel(2);
            $remiseACApiResult->setErrorMessage(trans('remise_payment4.ac.common.text.failed.not_connect.ac_result_url'));
            $this->logService->logError('Request KeizokuResultExtend',[$remiseACApiResult->getErrorMessage(), $curlResult['curlError'], $curlResult['curlInfo']]);
            return $remiseACApiResult;
        }

        // レスポンスデータ取得
        $responseData = $curlResult['xmlData'];

        // レスポンスデータログ出力(出力量が多いのでコメントアウト)
        // $this->logService->logInfo('Request KeizokuResultExtend -- Response Data',[$responseData]);

        $rcode = "";
        $count = 0;
        if (!empty($responseData)) {
            if (array_key_exists('typRetKeizokuResult',$responseData) && !empty($responseData['typRetKeizokuResult'])) {
                if (array_key_exists('X_R_CODE',$responseData['typRetKeizokuResult'])) {
                    $rcode = $responseData['typRetKeizokuResult']['X_R_CODE'];
                } else if (array_key_exists('X_R_CODE',$responseData['typRetKeizokuResult'][0])) {
                    $rcode = $responseData['typRetKeizokuResult'][0]['X_R_CODE'];
                }

                if (array_key_exists('X_COUNT',$responseData['typRetKeizokuResult'])) {
                    $count = $responseData['typRetKeizokuResult']['X_COUNT'];
                } elseif (array_key_exists('X_COUNT',$responseData['typRetKeizokuResult'][0])) {
                    $count = $responseData['typRetKeizokuResult'][0]['X_COUNT'];
                }
            }
        }

        // APPID、パスワード不正
        if(strcmp($rcode, "9:0000") == 0 ){
            $remiseACApiResult->setResult(false);
            $remiseACApiResult->setErrorLevel(2);
            $remiseACApiResult->setErrorMessage(trans('remise_payment4.ac.common.text.failed.keizoku_result_extend.rcode_error'));
            $this->logService->logError('Request KeizokuResultExtend',[$remiseACApiResult->getErrorMessage()]);
            return $remiseACApiResult;
        }

        // 指定日の定期購買情報がない
        if(strcmp($rcode, "8:3006") == 0 ){
            $remiseACApiResult->setResult(false);
            $remiseACApiResult->setErrorLevel(0);
            $remiseACApiResult->setErrorMessage(trans('remise_payment4.ac.common.text.failed.keizoku_result_extend.notfound'));
            $this->logService->logInfo('Request KeizokuResultExtend',[$remiseACApiResult->getErrorMessage()]);
            return $remiseACApiResult;
        }

        // その他エラー
        if(strcmp($rcode, "0:0000") != 0 ){
            $remiseACApiResult->setResult(false);
            $remiseACApiResult->setErrorLevel(2);
            $remiseACApiResult->setErrorMessage(trans('remise_payment4.ac.common.text.failed.keizoku_result_extend.rcode_error_else',['%rcode%' => $rcode]));
            $this->logService->logError('Request KeizokuResultExtend',[$remiseACApiResult->getErrorMessage()]);
            return $remiseACApiResult;
        }

        // 定期購買情報がない
        if (strcmp($rcode, "0:0000") == 0 && $count == 0) {
            $remiseACApiResult->setResult(false);
            $remiseACApiResult->setErrorLevel(0);
            $remiseACApiResult->setErrorMessage(trans('remise_payment4.ac.common.text.failed.keizoku_result_extend.notfound'));
            $this->logService->logInfo('Request KeizokuResultExtend',[$remiseACApiResult->getErrorMessage()]);
            return $remiseACApiResult;
        }

        // 正常終了
        $remiseACApiResult->setResult(true);
        $remiseACApiResult->setResultData($responseData);

        // 終了ログ
        $this->logService->logInfo('Request KeizokuResultExtend -- Done');

        return $remiseACApiResult;
    }

    /**
     * 定期購買更新用URLを呼び出す
     *
     * @param $mode 0:参照 1:編集
     * @param $remiseACMember
     *
     * @return RemiseACApiResult
     */
    public function requestKeizokuEditExtend($mode, RemiseACMember $remiseACMember)
    {
        // 開始ログ
        $this->logService->logInfo('Request KeizokuEditExtend');

        $remiseACApiResult = new RemiseACApiResult();

        // プラグイン基本情報の取得
        $Plugin = $this->utilService->getPlugin();
        if(!$Plugin)
        {
            $remiseACApiResult->setResult(false);
            $remiseACApiResult->setErrorLevel(1);
            $remiseACApiResult->setErrorMessage(trans('remise_payment4.ac.common.text.warning.plugin'));
            $this->logService->logWarning('Request KeizokuResultExtend',[$remiseACApiResult->getErrorMessage()]);
            return $remiseACApiResult;
        }

        // プラグイン設定情報の取得
        $Config = $this->utilService->getConfig();
        if(!$Config)
        {
            $remiseACApiResult->setResult(false);
            $remiseACApiResult->setErrorLevel(1);
            $remiseACApiResult->setErrorMessage(trans('remise_payment4.ac.common.text.warning.plugin'));
            $this->logService->logWarning('Request KeizokuResultExtend',[$remiseACApiResult->getErrorMessage()]);
            return $remiseACApiResult;
        }

        // プラグイン設定詳細情報の取得
        $ConfigInfo = $Config->getUnserializeInfo();
        if(!$ConfigInfo)
        {
            $remiseACApiResult->setResult(false);
            $remiseACApiResult->setErrorLevel(1);
            $remiseACApiResult->setErrorMessage(trans('remise_payment4.ac.common.text.warning.plugin'));
            $this->logService->logWarning('Request KeizokuResultExtend',[$remiseACApiResult->getErrorMessage()]);
            return $remiseACApiResult;
        }

        // 定期購買更新URL取得
        $requestUrl = $ConfigInfo->getAcEditUrl();
        if(!$requestUrl or empty($requestUrl)){
            $remiseACApiResult->setResult(false);
            $remiseACApiResult->setErrorLevel(1);
            $remiseACApiResult->setErrorMessage(trans('remise_payment4.ac.common.text.warning.plugin.ac_edit_url'));
            $this->logService->logWarning('Request KeizokuResultExtend',[$remiseACApiResult->getErrorMessage()]);
            return $remiseACApiResult;
        }

        // ヘッダ
        $requestHeaders = array(
            "User-Agent:" . trans('remise_payment4.extset.common.label.plugin_code') . '_PLG_VER ' . $Plugin->getVersion(),
        );

        if($mode == 0){
            // 参照モードでの呼出
            $requestPostData = array(
                'APPID'         => $ConfigInfo->getAcAppid(),
                'PASSWORD'      => $this->utilService->getDecryptedStr($ConfigInfo->getAcPassword()),
                'SHOPCO'        => $ConfigInfo->getShopco(),
                'MODE'          => 0,   // 参照モード
                'AC_MEMBERID'   => $remiseACMember->getId(),
                'AC_S_KAIIN_NO' => "",
                'AC_NAME'       => "",
                'AC_KANA'       => "",
                'AC_TEL'        => "",
                'AC_MAIL'       => "",
                'CHECK'         => "",
                'CARD'          => "",
                'EXPIRE'        => "",
                'NAME'          => "",
                'AC_AMOUNT'     => "",
                'AC_TAX'        => "",
                'AC_TOTAL'      => "",
                'AC_INTERVAL'   => "",
                'AC_NEXT_DATE'  => "",
                'AUTOCHARGE'    => "",
                'AC_STOP_DATE'  => "",
            );

        }elseif($mode == 1){
            // 編集モードでの呼出
            // 会員情報
            $customerId = "";
            $ac_name = "";
            $ac_kana = "";
            $ac_tel = "";
            $ac_mail = "";

            $orderResultCards = $remiseACMember->getOrderResultCards();
            $orderResultCard = $orderResultCards[0];
            $orderResult = $orderResultCard->getOrderResult();
            $order = $orderResult->getOrder();
            $customer = $order->getCustomer();

            if ($customer) {
                $customerId = $customer->getId();
                $ac_name = $customer->getName01() . $customer->getName02();
                $ac_kana = $customer->getKana01() . $customer->getKana02();
                $ac_tel = $customer->getPhoneNumber();
                $ac_mail = $customer->getEmail();
            } else {
                $ac_name = $order->getName01() . $order->getName02();
                $ac_kana = $order->getKana01() . $order->getKana02();
                $ac_tel = $order->getPhoneNumber();
                $ac_mail = $order->getEmail();
            }

            $ac_interval = $remiseACMember->getIntervalValue().$remiseACMember->getIntervalMark();

            // パラメータ
            if($remiseACMember->isStatus()){

                // AC_NAMEの変換
                $ac_name_converted = mb_convert_kana($ac_name, "ASKV", "UTF-8");
                $ac_name_converted = mb_convert_kana($ac_name_converted, "H", "UTF-8");

                // AC_KANAの変換
                $ac_kana_converted = mb_convert_kana($ac_kana, "ASKV", "UTF-8");
                $ac_kana_converted = mb_convert_kana($ac_kana_converted, "H", "UTF-8");


                $requestPostData = array(
                    'APPID'         => $ConfigInfo->getAcAppid(),
                    'PASSWORD'      => $this->utilService->getDecryptedStr($ConfigInfo->getAcPassword()),
                    'SHOPCO'        => $ConfigInfo->getShopco(),
                    'MODE'          => 1,   // 編集モード
                    'AC_MEMBERID'   => $remiseACMember->getId(),
                    'AC_S_KAIIN_NO' => $customerId,
                    'AC_NAME'       => $ac_name_converted,
                    'AC_KANA'       => $ac_kana_converted,
                    'AC_TEL'        => !empty($ac_tel) ? $ac_tel : "NULL",
                    'AC_MAIL'       => $ac_mail,
                    'CHECK'         => "",  // カードの有効性チェックは行わない
                    'CARD'          => "",
                    'EXPIRE'        => "",
                    'NAME'          => "",
                    'AC_AMOUNT'     => !is_null($remiseACMember->getTotal()) ? $remiseACMember->getTotal() : 0,
                    'AC_TAX'        => 0,
                    'AC_TOTAL'      => !is_null($remiseACMember->getTotal()) ? $remiseACMember->getTotal() : 0,
                    'AC_INTERVAL'   => $ac_interval,
                    'AC_NEXT_DATE'  => $remiseACMember->getNextDateStr(),
                    'AUTOCHARGE'    => !is_null($remiseACMember->isStatus()) ? $remiseACMember->isStatus() : "",
                    'AC_STOP_DATE'  => !empty($remiseACMember->getStopDateStr()) ? $remiseACMember->getStopDateStr() : "NULL",
                );
            }else{

                // AC_NAMEの変換
                $ac_name_converted = mb_convert_kana($ac_name, "ASKV", "UTF-8");
                $ac_name_converted = mb_convert_kana($ac_name_converted, "H", "UTF-8");

                // AC_KANAの変換
                $ac_kana_converted = mb_convert_kana($ac_kana, "ASKV", "UTF-8");
                $ac_kana_converted = mb_convert_kana($ac_kana_converted, "H", "UTF-8");

                $requestPostData = array(

                    'APPID'         => $ConfigInfo->getAcAppid(),
                    'PASSWORD'      => $this->utilService->getDecryptedStr($ConfigInfo->getAcPassword()),
                    'SHOPCO'        => $ConfigInfo->getShopco(),
                    'MODE'          => 1,   // 編集モード
                    'AC_MEMBERID'   => $remiseACMember->getId(),
                    'AC_S_KAIIN_NO' => $customerId,
                    'AC_NAME'       => $ac_name_converted,
                    'AC_KANA'       => $ac_kana_converted,
                    'AC_TEL'        => !empty($ac_tel) ? $ac_tel : "NULL",
                    'AC_MAIL'       => $ac_mail,
                    'CHECK'         => "",
                    'CARD'          => "",
                    'EXPIRE'        => "",
                    'NAME'          => "",
                    'AC_AMOUNT'     => "",
                    'AC_TAX'        => "",
                    'AC_TOTAL'      => "",
                    'AC_INTERVAL'   => "",
                    'AC_NEXT_DATE'  => "",
                    'AUTOCHARGE'    => "0",
                    'AC_STOP_DATE'  => "",
                );
            }
        }

        $this->logService->logInfo('Request KeizokuEditExtend -- Request Data',[$requestPostData]);

        // 自動継続課金APIリクエスト実行
        $curlResult = $this->curlRequest($requestUrl, $requestHeaders, $requestPostData);

        // 接続エラー
        if ($curlResult['curlResponse'] === false)
        {
            $remiseACApiResult->setResult(false);
            $remiseACApiResult->setErrorLevel(2);
            $remiseACApiResult->setErrorMessage(trans('remise_payment4.ac.common.text.failed.not_connect.ac_edit_url'));
            $this->logService->logError('Request KeizokuEditExtend',[$remiseACApiResult->getErrorMessage(), $curlResult['curlError'], $curlResult['curlInfo']]);
            return $remiseACApiResult;
        }

        // レスポンスデータ取得
        $responseData = $curlResult['xmlData'];

        // レスポンスデータログ出力(出力量が多いのでコメントアウト)
        // $this->logService->logInfo('Request KeizokuEditExtend -- Response Data',[$remiseACApiResult->getResultData()]);

        $rcode = "";
        if(!empty($responseData) && array_key_exists('X_R_CODE',$responseData)){
            $rcode = $responseData['X_R_CODE'];
        }

        // APPID、パスワード不正
        if(strcmp($rcode, "9:0000") == 0 ){
            $remiseACApiResult->setResult(false);
            $remiseACApiResult->setErrorLevel(2);
            $remiseACApiResult->setErrorMessage(trans('remise_payment4.ac.common.text.failed.keizoku_edit_extend.rcode_error'));
            $this->logService->logError('Request KeizokuResultExtend',[$remiseACApiResult->getErrorMessage()]);
            return $remiseACApiResult;
        }

        // その他エラー
        if(strcmp($rcode, "0:0000") != 0 ){
            $remiseACApiResult->setResult(false);
            $remiseACApiResult->setErrorLevel(2);
            $remiseACApiResult->setErrorMessage(trans('remise_payment4.ac.common.text.failed.keizoku_edit_extend.rcode_error_else',['%rcode%' => $rcode]));
            $this->logService->logError('Request KeizokuResultExtend',[$remiseACApiResult->getErrorMessage()]);
            return $remiseACApiResult;
        }

        // 正常終了
        $remiseACApiResult->setResult(true);
        $remiseACApiResult->setResultData($responseData);

        // 終了ログ
        $this->logService->logInfo('Request KeizokuEditExtend -- Done');

        return $remiseACApiResult;
    }

}
