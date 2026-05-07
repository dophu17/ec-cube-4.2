<?php

namespace Plugin\RemisePayment42\Service;

/**
 * ログ出力
 */
class LogService
{
    /**
     * ログヘッダー
     */
    const REMISE_HEADER = '[Remise] ';

    /**
     * コンストラクタ
     */
    public function __construct()
    {
    }

    /**
     * エラーログ出力
     *
     * @param $message
     * @param $context
     */
    public function logError($message, array $context = [])
    {
        logs('RemisePayment4')->error(self::REMISE_HEADER . $message);
        if (!empty($context))
        {
            logs('RemisePayment4')->error(print_r($context, true));
        }
    }

    /**
     * 警告ログ出力
     *
     * @param $message
     * @param $context
     */
    public function logWarning($message, array $context = [])
    {
        logs('RemisePayment4')->warning(self::REMISE_HEADER . $message);
        if (!empty($context))
        {
            logs('RemisePayment4')->warning(print_r($context, true));
        }
    }

    /**
     * 情報ログ出力
     *
     * @param $message
     * @param $context
     */
    public function logInfo($message, array $context = [])
    {
        logs('RemisePayment4')->info(self::REMISE_HEADER . $message);
        if (!empty($context))
        {
            logs('RemisePayment4')->info(print_r($context, true));
        }
    }

    /**
     * クリティカルログ出力
     *
     * @param $message
     * @param $context
     */
    public function logCritical($message, array $context = [])
    {
        logs('RemisePayment4')->critical(self::REMISE_HEADER . $message);
        if (!empty($context))
        {
            logs('RemisePayment4')->critical(print_r($context, true));
        }
    }
}
