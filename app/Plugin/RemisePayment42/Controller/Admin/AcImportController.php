<?php
namespace Plugin\RemisePayment42\Controller\Admin;

use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\PaginatorInterface;
use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Util\FormUtil;
use Eccube\Util\StringUtil;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Shipping;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Repository\Master\PageMaxRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Plugin\RemisePayment42\Repository\RemiseACTypeRepository;
use Plugin\RemisePayment42\Repository\RemiseACImportRepository;
use Plugin\RemisePayment42\Repository\RemiseACMemberRepository;
use Plugin\RemisePayment42\Repository\OrderResultCardRepository;
use Plugin\RemisePayment42\Service\LogService;
use Plugin\RemisePayment42\Service\UtilService;
use Plugin\RemisePayment42\Service\AcService;
use Plugin\RemisePayment42\Service\AcApiService;
use Plugin\RemisePayment42\Form\Type\Admin\AcImportType;
use Plugin\RemisePayment42\Entity\RemiseACApiResult;

/**
 * 定期購買取込
 */
class AcImportController extends AbstractController
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
     *
     * @var RemiseACTypeRepository
     */
    protected $remiseACTypeRepository;

    /**
     *
     * @var RemiseACImportRepository
     */
    protected $remiseACImportRepository;

    /**
     *
     * @var RemiseACMemberRepository
     */
    protected $remiseACMemberRepository;

    /**
     *
     * @var OrderResultCardRepository
     */
    protected $orderResultCardRepository;

    /**
     *
     * @var PageMaxRepository
     */
    protected $pageMaxRepository;

    private $keizokuResultExtendErrorCode;

    /**
     * コンストラクタ
     *
     * @param UtilService $utilService
     * @param LogService $logService
     * @param AcService $acService
     * @param AcApiService $acApiService
     * @param RemiseACTypeRepository $remiseACTypeRepository
     * @param RemiseACMemberRepository $remiseACMemberRepository
     * @param OrderResultCardRepository $orderResultCardRepository
     * @param PageMaxRepository $pageMaxRepository
     */
    public function __construct(
        UtilService $utilService,
        LogService $logService,
        AcService $acService,
        AcApiService $acApiService,
        RemiseACTypeRepository $remiseACTypeRepository,
        RemiseACImportRepository $remiseACImportRepository,
        RemiseACMemberRepository $remiseACMemberRepository,
        OrderResultCardRepository $orderResultCardRepository,
        PageMaxRepository $pageMaxRepository)
    {
        $this->utilService = $utilService;
        $this->logService = $logService;
        $this->acService = $acService;
        $this->acApiService = $acApiService;
        $this->remiseACTypeRepository = $remiseACTypeRepository;
        $this->remiseACImportRepository = $remiseACImportRepository;
        $this->remiseACMemberRepository = $remiseACMemberRepository;
        $this->orderResultCardRepository = $orderResultCardRepository;
        $this->pageMaxRepository = $pageMaxRepository;
    }

    /**
     * 定期購買取込一覧画面
     *
     * @param
     *            $request
     *
     * @Route("/%eccube_admin_route%/remise_payment4_ac_import_index", name="remise_payment4_ac_import_index")
     * @Route("/%eccube_admin_route%/remise_payment4_ac_import_index/page/{page_no}", requirements={"page_no" = "\d+"}, name="remise_payment4_ac_import_index_page")
     * @Template("@RemisePayment42/admin/ac_import_index.twig")
     */
    public function index(Request $request, PaginatorInterface $paginator, $page_no = null)
    {
        try {
            $this->logService->logInfo('Ac Import Index');

            $builder = $this->formFactory->createBuilder(AcImportType::class);

            $importForm = $builder->getForm();

            $useKeizokuResultExtend = $this->acApiService->useKeizokuResultExtend();

            /**
             * ページの表示件数は, 以下の順に優先される.
             * - リクエストパラメータ
             * - セッション
             * - デフォルト値
             * また, セッションに保存する際は mtb_page_maxと照合し, 一致した場合のみ保存する.
             */
            $page_count = $this->session->get('remise_payment4.ac.admin_import_index.search.page_count', $this->eccubeConfig->get('eccube_default_page_count'));

            $page_count_param = (int) $request->get('page_count');
            $pageMaxis = $this->pageMaxRepository->findAll();

            if ($page_count_param) {
                foreach ($pageMaxis as $pageMax) {
                    if ($page_count_param == $pageMax->getName()) {
                        $page_count = $pageMax->getName();
                        $this->session->set('remise_payment4.ac.admin_import_index.search.page_count', $page_count);
                        break;
                    }
                }
            }

            if ('POST' === $request->getMethod()) {
                $importForm->handleRequest($request);

                if ($importForm->isSubmitted() && $importForm->isValid()) {
                    if ($useKeizokuResultExtend) {
                        $page_no = 1;

                        $requestData = $request->request->all();
                        $memberId = "";
                        $chargeDate = "";
                        $startRow = "1";
                        $count = "999999";

                        if (array_key_exists('ac_import',$requestData)) {
                            if (array_key_exists('member_id',$requestData['ac_import'])) {
                                // メンバーID
                                $memberId = trim($requestData['ac_import']['member_id']);
                            }
                            if (array_key_exists('import_date',$requestData['ac_import'])) {
                                // 取込対象の課金日
                                $chargeDate = str_replace("-", "", $requestData['ac_import']['import_date']);
                            }
                            if (array_key_exists('import_count_choice', $requestData['ac_import'])) {
                                if ($requestData['ac_import']['import_count_choice'] == 'set') {
                                    if (array_key_exists('import_count_start', $requestData['ac_import'])) {
                                        // 取込開始件数
                                        $startRow = $requestData['ac_import']['import_count_start'];
                                    }
                                    if (array_key_exists('import_count_end', $requestData['ac_import'])) {
                                        // 取得件数
                                        $count = $requestData['ac_import']['import_count_end'] - $startRow + 1;
                                    }
                                }
                            }
                        }

                        $remiseACImportInfoList = null;
                        $returnRemiseACApiResult = new RemiseACApiResult();
                        // メンバーID指定時
                        if (!empty($memberId)) {
                            // 定期購買メンバー情報を取得
                            $remiseACMember = $this->remiseACMemberRepository->findOneBy(['id' => $memberId]);

                            if (!$remiseACMember) {
                                $returnRemiseACApiResult->setResult(false);
                                $returnRemiseACApiResult->setErrorLevel(0);
                                $returnRemiseACApiResult->setErrorMessage(trans('remise_payment4.ac.admin_import_index.text.failed.notfound_member'));
                            } else {
                                // 定期購買取込
                                $remiseACImportInfoList = $this->acService->acImportOrder($memberId, $chargeDate, $startRow, $count, $returnRemiseACApiResult);
                            }
                        } else {
                            // 定期購買取込
                            $remiseACImportInfoList = $this->acService->acImportOrder($memberId, $chargeDate, $startRow, $count, $returnRemiseACApiResult);
                        }

                        if (!$returnRemiseACApiResult->isResult()) {
                            if ($returnRemiseACApiResult->getErrorLevel() == 0) {
                                $this->logService->logInfo('Ac Import Index',
                                    Array($returnRemiseACApiResult->getErrorMessage()));
                                $this->addWarning($returnRemiseACApiResult->getErrorMessage(),'admin');
                            } else if ($returnRemiseACApiResult->getErrorLevel() == 1) {
                                $this->logService->logWarning('Ac Import Index',
                                    Array($returnRemiseACApiResult->getErrorMessage()));
                                $this->addWarning($returnRemiseACApiResult->getErrorMessage(),'admin');
                            } else {
                                $this->logService->logError('Ac Import Index',
                                    Array($returnRemiseACApiResult->getErrorMessage()));
                                $this->addError($returnRemiseACApiResult->getErrorMessage(),'admin');
                            }
                        } else {
                            // ルミーズ定期購買取込情報（自動継続課金取込結果通知）を更新する
                            $this->acService->acImportUpdateRemiseACImport('', $chargeDate, $remiseACImportInfoList);

                            if ($remiseACImportInfoList) {
                                // オブジェクトを配列に変換
                                $tempArrayRemiseACImportInfoList = (array)$remiseACImportInfoList;

                                // 配列のキー名から不要文字列を削除
                                $arrayRemiseACImportInfoList = array();
                                foreach ($tempArrayRemiseACImportInfoList as $key => $value) {
                                    $arrayRemiseACImportInfoList[str_replace("\x00Plugin\RemisePayment42\Entity\RemiseACImportInfo\x00","",$key)] = $value;
                                }

                                return $this->forwardToRoute('remise_payment4_ac_import_result', [] , $arrayRemiseACImportInfoList);
                            }
                        }
                    }
                    // ページ番号をセッションに保持.
                    $this->session->set('remise_payment4.ac.admin_management_index.search.page_no', $page_no);
                } else {
                    //エラー
                    return [
                        'importForm' => $importForm->createView(),
                        'pagination' => [],
                        'pageMaxis' => $pageMaxis,
                        'page_no' => $page_no,
                        'page_count' => $page_count,
                        'has_errors' => true,
                        'useKeizokuResultExtend' => $useKeizokuResultExtend
                    ];
                }
            } else {
                if (null !== $page_no || $request->get('resume')) {
                    /*
                     * ページ送りの場合または、他画面から戻ってきた場合は, セッションから検索条件を復旧する.
                     */
                    if ($page_no) {
                        // ページ送りで遷移した場合.
                        $this->session->set('remise_payment4.ac.admin_import_index.search.page_no', (int) $page_no);
                    } else {
                        // 他画面から遷移した場合.
                        $page_no = $this->session->get('remise_payment4.ac.admin_import_index.search.page_no', 1);
                    }
                } else {
                    /**
                     * 初期表示の場合.
                     */
                    $page_no = 1;

                    // セッション中のページ番号を初期化.
                    $this->session->set('remise_payment4.ac.admin_import_index.search.page_no', $page_no);
                }
            }

            $qb = $this->remiseACImportRepository->getQueryBuilderBySearchDataForAdmin();

            $pagination = $paginator->paginate($qb, $page_no, $page_count);

            $this->logService->logInfo('Ac Import Index -- Done');

            return [
                'importForm' => $importForm->createView(),
                'pagination' => $pagination,
                'pageMaxis' => $pageMaxis,
                'page_no' => $page_no,
                'page_count' => $page_count,
                'has_errors' => false,
                'useKeizokuResultExtend' => $useKeizokuResultExtend
            ];
        } catch (\Exception $e) {
            // ログ出力
            $this->logService->logError('Ac Import Index', Array(
                trans('admin.common.system_error'),
                'ErrCode:' . $e->getCode(),
                'ErrMessage:' . $e->getMessage(),
                'ErrTrace:' . $e->getTraceAsString()
            ));

            $this->addError(trans('admin.common.system_error'), 'admin');

            return [
                'importForm' => $importForm->createView(),
                'pagination' => [],
                'pageMaxis' => $pageMaxis,
                'page_no' => $page_no,
                'page_count' => $page_count,
                'has_errors' => false,
                'useKeizokuResultExtend' => $useKeizokuResultExtend
            ];
        }
    }

    /**
     * 定期購買取込結果画面
     *
     * @param
     *            $request
     *
     * @Route("/%eccube_admin_route%/remise_payment4_ac_import_result", name="remise_payment4_ac_import_result")
     * @Template("@RemisePayment42/admin/ac_import_result.twig")
     */
    public function result(Request $request)
    {
        $this->logService->logInfo('Ac Import Result');
        $queryParameters = $request->query;
        $this->addSuccess(trans('remise_payment4.ac.admin_import_result.text.success'), 'admin');
        $this->logService->logInfo('Ac Import Result -- Done');
        return [
            'remiseACImportInfoList'  => $queryParameters,
        ];
    }
}
