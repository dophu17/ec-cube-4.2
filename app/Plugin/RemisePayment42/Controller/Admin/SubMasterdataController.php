<?php
namespace Plugin\RemisePayment42\Controller\Admin;

use Eccube\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Plugin\RemisePayment42\Form\Type\Admin\ConfigType;
use Plugin\RemisePayment42\Service\LogService;
use Plugin\RemisePayment42\Service\UtilService;
use Plugin\RemisePayment42\Service\AcService;

/**
 * マスターデータ画面
 */
class SubMasterdataController extends AbstractController
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
     * コンストラクタ
     *
     * @param UtilService $utilService
     * @param LogService $logService
     * @param AcService $acService
     */
    public function __construct(UtilService $utilService, LogService $logService, AcService $acService)
    {
        $this->utilService = $utilService;
        $this->logService = $logService;
        $this->acService = $acService;
    }

    /**
     * ルミーズ販売種別追加
     *
     * @param
     *            $request
     *
     * @Route("/%eccube_admin_route%/remise_payment4/masterdata/sale_type/add", name="remise_payment4_masterdata_sale_type_add")
     */
    public function saleTypeAdd(Request $request)
    {
        try {
            $this->logService->logInfo('Masterdata SaleTypeAdd');

            // プラグイン設定情報の取得
            $Config = $this->utilService->getConfig();
            if (!$Config) {
                $this->logService->logInfo('Masterdata SaleTypeAdd -- Done');
                $this->addWarning(trans('remise_payment4.ac.common.text.warning.plugin'), 'admin');
                return $this->redirectToRoute('admin_setting_system_masterdata_view', [
                    'entity' => 'Eccube-Entity-Master-SaleType'
                ]);
            }

            // 設定情報の取得
            $ConfigInfo = $Config->getUnserializeInfo();
            if (!$ConfigInfo) {
                $this->logService->logInfo('Masterdata SaleTypeAdd -- Done');
                $this->addWarning(trans('remise_payment4.ac.common.text.warning.plugin'), 'admin');
                return $this->redirectToRoute('admin_setting_system_masterdata_view', [
                    'entity' => 'Eccube-Entity-Master-SaleType'
                ]);
            }

            // 定期購買が無効の場合
            if(!$ConfigInfo->useOptionAC())
            {
                $this->logService->logInfo('Masterdata SaleTypeAdd -- Done');
                $this->addWarning(trans('remise_payment4.ac.common.text.warning.plugin'), 'admin');
                return $this->redirectToRoute('admin_setting_system_masterdata_view', [
                    'entity' => 'Eccube-Entity-Master-SaleType'
                ]);
            }

            $this->acService->insertRemiseSaleTypeMasterAdd();

            $this->logService->logInfo('Masterdata SaleTypeAdd -- Done');

            $this->addSuccess(trans('remise_payment4.ac.masterdata.saletype.text.success'), 'admin');

            return $this->redirectToRoute('admin_setting_system_masterdata_view', [
                'entity' => 'Eccube-Entity-Master-SaleType'
            ]);
        } catch (\Exception $e) {
            // ログ出力
            $this->logService->logError('Masterdata SaleTypeAdd', Array(
                trans('admin.common.system_error'),
                'ErrCode:' . $e->getCode(),
                'ErrMessage:' . $e->getMessage(),
                'ErrTrace:' . $e->getTraceAsString()
                ));

            $this->addError(trans('admin.common.system_error'), 'admin');

            return $this->redirectToRoute('admin_setting_system_masterdata_view', [
                'entity' => 'Eccube-Entity-Master-SaleType'
            ]);
        }
    }
}
