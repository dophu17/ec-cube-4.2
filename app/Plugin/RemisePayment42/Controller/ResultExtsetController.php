<?php

namespace Plugin\RemisePayment42\Controller;

use Eccube\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Plugin\RemisePayment42\Service\LogService;

/**
 * 結果通知用
 */
class ResultExtsetController extends AbstractController
{
    /**
     * @var LogService
     */
    protected $logService;

    /**
     * コンストラクタ
     *
     * @param LogService $logService
     */
    public function __construct(
        LogService $logService
        ) {
            $this->logService = $logService;
    }

    /**
     * 結果通知
     *
     * @param  $request
     *
     * @Route("/remise_payment_extset4_result", name="remise_payment_extset4_result")
     */
    public function result(Request $request)
    {
        $this->logService->logInfo('Payment Extset Result');

        $requestData = $request->request->all();

        $this->logService->logInfo('Payment Extset Result -- Request', $requestData);

        $returnContents = "";

        if (isset($requestData["X-TRANID"])) {
            if ($requestData['REC_TYPE'] == "RET") {
                if (isset($requestData['X-PARTOFCARD'])) {
                    // カード決済・結果通知処理
                    $mode = 'credit_complete';
                } else {
                    // カード決済（拡張セット）・結果通知処理
                    $mode = 'extset_complete';
                }
            }
        }

        if($mode == "extset_complete")
        {
            $returnContents = trans('remise_payment4.extset.common.label.result.card.success');
        }
        else
       {
            $returnContents = "RESULT_FORMAT_ERROR:901";
            $this->logService->logError('Payment Extset Result -- Result Format Error (901)');
        }

        // 結果通知の応答
        $this->logService->logInfo('Payment Extset Result -- Done');
        return new Response($returnContents);
    }
}
