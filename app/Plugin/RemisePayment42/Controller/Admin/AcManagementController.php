<?php
namespace Plugin\RemisePayment42\Controller\Admin;
//use Knp\Component\Pager\Paginator;
use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Entity\ExportCsvRow;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Util\FormUtil;
use Eccube\Service\CsvExportService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Eccube\Repository\Master\PageMaxRepository;
use Knp\Component\Pager\PaginatorInterface;
use Plugin\RemisePayment42\Entity\Config;
use Plugin\RemisePayment42\Entity\ConfigInfo;
use Plugin\RemisePayment42\Entity\RemiseACType;
use Plugin\RemisePayment42\Entity\RemiseACMember;
use Plugin\RemisePayment42\Repository\RemiseACTypeRepository;
use Plugin\RemisePayment42\Repository\RemiseACMemberRepository;
use Plugin\RemisePayment42\Service\LogService;
use Plugin\RemisePayment42\Service\UtilService;
use Plugin\RemisePayment42\Service\AcService;
use Plugin\RemisePayment42\Service\AcApiService;
use Plugin\RemisePayment42\Form\Type\Admin\SearchAcManagementType;
use Plugin\RemisePayment42\Form\Type\Admin\AcManagementEditType;

/**
 * 定期購買管理
 */
class AcManagementController extends AbstractController
{
    /**
     * @var CsvExportService
     */
    protected $csvExportService;

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
     * @var RemiseACMemberRepository
     */
    protected $remiseACMemberRepository;

    /**
     *
     * @var PageMaxRepository
     */
    protected $pageMaxRepository;

    /**
     * コンストラクタ
     *
     * @param CsvExportService $csvExportService
     * @param UtilService $utilService
     * @param LogService $logService
     * @param AcService $acService
     * @param AcApiService $acApiService
     * @param RemiseACTypeRepository $remiseACTypeRepository
     * @param RemiseACMemberRepository $remiseACMemberRepository
     * @param PageMaxRepository $pageMaxRepository
     */
    public function __construct(CsvExportService $csvExportService, UtilService $utilService, LogService $logService, RemiseACTypeRepository $remiseACTypeRepository, RemiseACMemberRepository $remiseACMemberRepository, AcService $acService, AcApiService $acApiService, PageMaxRepository $pageMaxRepository)
    {
        $this->csvExportService = $csvExportService;
        $this->utilService = $utilService;
        $this->logService = $logService;
        $this->remiseACTypeRepository = $remiseACTypeRepository;
        $this->remiseACMemberRepository = $remiseACMemberRepository;
        $this->acService = $acService;
        $this->acApiService = $acApiService;
        $this->pageMaxRepository = $pageMaxRepository;
    }

    /**
     * 定期購買管理一覧画面
     *
     * @param
     *            $request
     *
     * @Route("/%eccube_admin_route%/remise_payment4_ac_management_index", name="remise_payment4_ac_management_index")
     * @Route("/%eccube_admin_route%/remise_payment4_ac_management_index/page/{page_no}", requirements={"page_no" = "\d+"}, name="remise_payment4_ac_management_index_page")
     * @Template("@RemisePayment42/admin/ac_management_index.twig")
     */
    public function index(Request $request, PaginatorInterface $paginator, $page_no = null)
    {
        try {
            $this->logService->logInfo('Ac Management Index');

            $builder = $this->formFactory->createBuilder(SearchAcManagementType::class);

            $searchForm = $builder->getForm();

            $remiseCsvType = $this->acService->getRemiseCsvType();

            /**
             * ページの表示件数は, 以下の順に優先される.
             * - リクエストパラメータ
             * - セッション
             * - デフォルト値
             * また, セッションに保存する際は mtb_page_maxと照合し, 一致した場合のみ保存する.
             */
            $page_count = $this->session->get('remise_payment4.ac.admin_management_index.search.page_count', $this->eccubeConfig->get('eccube_default_page_count'));

            $page_count_param = (int) $request->get('page_count');
            $pageMaxis = $this->pageMaxRepository->findAll();

            if ($page_count_param) {
                foreach ($pageMaxis as $pageMax) {
                    if ($page_count_param == $pageMax->getName()) {
                        $page_count = $pageMax->getName();
                        $this->session->set('remise_payment4.ac.admin_management_index.search.page_count', $page_count);
                        break;
                    }
                }
            }

            if ('POST' === $request->getMethod()) {
                $searchForm->handleRequest($request);

                if ($searchForm->isSubmitted() && $searchForm->isValid()) {
                    /**
                     * 検索が実行された場合は, セッションに検索条件を保存する.
                     * ページ番号は最初のページ番号に初期化する.
                     */
                    $page_no = 1;
                    $searchData = $searchForm->getData();

                    // 検索条件, ページ番号をセッションに保持.
                    $this->session->set('remise_payment4.ac.admin_management_index.search', FormUtil::getViewData($searchForm));
                    $this->session->set('remise_payment4.ac.admin_management_index.search.page_no', $page_no);
                } else {
                    // 検索エラーの際は, 詳細検索枠を開いてエラー表示する.
                    return [
                        'searchForm' => $searchForm->createView(),
                        'pagination' => [],
                        'pageMaxis' => $pageMaxis,
                        'page_no' => $page_no,
                        'page_count' => $page_count,
                        'has_errors' => true,
                        'remiseCsvType' => $remiseCsvType
                    ];
                }
            } else {
                if (null !== $page_no || $request->get('resume')) {
                    /*
                     * ページ送りの場合または、他画面から戻ってきた場合は, セッションから検索条件を復旧する.
                     */
                    if ($page_no) {
                        // ページ送りで遷移した場合.
                        $this->session->set('remise_payment4.ac.admin_management_index.search.page_no', (int) $page_no);
                    } else {
                        // 他画面から遷移した場合.
                        $page_no = $this->session->get('remise_payment4.ac.admin_management_index.search.page_no', 1);
                    }
                    $viewData = $this->session->get('remise_payment4.ac.admin_management_index.search', []);
                    $searchData = FormUtil::submitAndGetData($searchForm, $viewData);
                } else {
                    /**
                     * 初期表示の場合.
                     */
                    $page_no = 1;
                    // submit default value
                    $viewData = FormUtil::getViewData($searchForm);
                    $searchData = FormUtil::submitAndGetData($searchForm, $viewData);

                    // セッション中の検索条件, ページ番号を初期化.
                    $this->session->set('remise_payment4.ac.admin_management_index.search', $searchData);
                    $this->session->set('remise_payment4.ac.admin_management_index.search.page_no', $page_no);
                }
            }

            $qb = $this->remiseACMemberRepository->getQueryBuilderBySearchDataForAdmin($searchData);

            $pagination = $paginator->paginate($qb, $page_no, $page_count);
            // 定期購買メンバー情報に紐づく受注情報をセットする（リレーションを使わない）
            foreach ($pagination as $RemiseACMember){
                $this->acService->setRemiseAcMemberRelatedOrder($RemiseACMember);
            }

            $this->logService->logInfo('Ac Management Index -- Done');

            return [
                'searchForm' => $searchForm->createView(),
                'pagination' => $pagination,
                'pageMaxis' => $pageMaxis,
                'page_no' => $page_no,
                'page_count' => $page_count,
                'has_errors' => false,
                'remiseCsvType' => $remiseCsvType
            ];
        } catch (\Exception $e) {
            // ログ出力
            $this->logService->logError('Ac Management Index', Array(
                trans('admin.common.system_error'),
                'ErrCode:' . $e->getCode(),
                'ErrMessage:' . $e->getMessage(),
                'ErrTrace:' . $e->getTraceAsString()
            ));
            $this->addError(trans('admin.common.system_error'), 'admin');
            return;
        }
    }

    /**
     * 定期購買CSVの出力.
     *
     * @Route("/%eccube_admin_route%/remise_payment4_ac_management_csvexport", name="remise_payment4_ac_management_csvexport")
     *
     * @param Request $request
     *
     * @return StreamedResponse
     */
    public function exportAC(Request $request)
    {
        $remiseCsvType = $this->acService->getRemiseCsvType();
        $filename = 'autocharge_'.(new \DateTime())->format('YmdHis').'.csv';
        $response = $this->exportCsv($request, $remiseCsvType->getId(), $filename);
        log_info('受注CSV出力ファイル名', [$filename]);

        return $response;
    }

    /**
     * @param Request $request
     * @param $csvTypeId
     * @param string $fileName
     *
     * @return StreamedResponse
     */
    private function exportCsv(Request $request, $csvTypeId, $fileName)
    {
        // タイムアウトを無効にする.
        set_time_limit(0);

        // sql loggerを無効にする.
        $em = $this->entityManager;
        $em->getConfiguration()->setSQLLogger(null);

        $response = new StreamedResponse();
        $response->setCallback(function () use ($request, $csvTypeId) {
            // CSV種別を元に初期化.
            $this->csvExportService->initCsvType($csvTypeId);

            // ヘッダ行の出力.
            $this->csvExportService->exportHeader();

            // 受注データ検索用のクエリビルダを取得.
            $qb = $this->getRemiseACMemberQueryBuilder($request);

            // データ行の出力.
            $this->csvExportService->setExportQueryBuilder($qb);
            $this->csvExportService->exportData(function ($entity, $csvService) use ($request) {
                $Csvs = $csvService->getCsvs();

                $remiseACMember = $entity;

                $ExportCsvRow = new ExportCsvRow();

                // 定期購買メンバー情報に紐づく受注情報をセットする（リレーションを使わない）
                $this->acService->setRemiseAcMemberRelatedOrder($remiseACMember);
                $orderResultCards = $remiseACMember->getOrderResultCards();
                $latestOrderResultCard = $orderResultCards[0];
                $oldestOrderResultCard = $orderResultCards[count($orderResultCards)-1];
                $latestOrderResult = $latestOrderResultCard->getOrderResult();
                $oldestOrderResult = $oldestOrderResultCard->getOrderResult();
                $latestOrder = $latestOrderResult->getOrder();
                $oldestOrder = $oldestOrderResult->getOrder();
                $latestOrderItems = $latestOrder->getOrderItems();
                $customer = $latestOrder->getCustomer();
                $latestOrderItem = new OrderItem();
                foreach ($latestOrderItems as $tempOrderItem) {
                    if ($tempOrderItem->isProduct()) {
                        $latestOrderItem = $tempOrderItem;
                        break;
                    }
                }

                // CSV出力項目と合致するデータを取得.
                foreach ($Csvs as $Csv) {
                    switch ($Csv->getFieldName()) {
                        case "customer_id": // 会員番号
                            if($customer){
                                $ExportCsvRow->setData($customer->getId());
                            }else{
                                $ExportCsvRow->setData(trans('remise_payment4.ac.common.text.nonmember'));
                            }
                            break;
                        case "name01": // お名前(姓)
                            if($customer){
                                $ExportCsvRow->setData($customer->getName01());
                            }else{
                                $ExportCsvRow->setData($latestOrder->getName01());
                            }
                            break;
                        case "name02": // お名前(名)
                            if($customer){
                                $ExportCsvRow->setData($customer->getName02());
                            }else{
                                $ExportCsvRow->setData($latestOrder->getName02());
                            }
                            break;
                        case "product_name": // 商品名
                            $productName = $latestOrderItem->getProductName();
                            if($latestOrderItem->getClassCategoryName1()){
                                $productName = $productName.$latestOrderItem->getClassCategoryName1();
                            }
                            if($latestOrderItem->getClassCategoryName2()){
                                $productName = $productName." / ".$latestOrderItem->getClassCategoryName2();
                            }
                            $ExportCsvRow->setData($productName);
                            break;
                        case "first_order_id": // 初回注文番号
                            $ExportCsvRow->setData($oldestOrder->getOrderNo());
                            break;
                        case "latest_order_id": // 最新注文番号
                            $ExportCsvRow->setData($latestOrder->getOrderNo());
                            break;
                        case "total": // 定期購買金額
                            $ExportCsvRow->setData($remiseACMember->getTotal());
                            break;
                        case "next_date": // 次回課金日
                            $ExportCsvRow->setData($remiseACMember->getNextDateStr("Y/m/d"));
                            break;
                        case "status": // 状態
                            if($remiseACMember->isStatus()){
                                $ExportCsvRow->setData(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_member.status.1'));
                            }else{
                                $ExportCsvRow->setData(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_member.status.0'));
                            }
                            break;
                        case "member_id": // メンバーID
                            $ExportCsvRow->setData($remiseACMember->getId());
                            break;
                        case "note": // 備考
                            $ExportCsvRow->setData($remiseACMember->getNote());
                            break;
                        case "kana01": // お名前(セイ)
                            if($customer){
                                $ExportCsvRow->setData($customer->getKana01());
                            }else{
                                $ExportCsvRow->setData($latestOrder->getKana01());
                            }
                            break;
                        case "kana02": // お名前(メイ)
                            if($customer){
                                $ExportCsvRow->setData($customer->getKana02());
                            }else{
                                $ExportCsvRow->setData($latestOrder->getKana02());
                            }
                            break;
                        case "company_name": // 会社名
                            if($customer){
                                $ExportCsvRow->setData($customer->getCompanyName());
                            }else{
                                $ExportCsvRow->setData($latestOrder->getCompanyName());
                            }
                            break;
                        case "postal_code": // 郵便番号
                            if($customer){
                                $ExportCsvRow->setData($customer->getPostalCode());
                            }else{
                                $ExportCsvRow->setData($latestOrder->getPostalCode());
                            }
                            break;
                        case "pref": // 都道府県
                            if($customer){
                                $ExportCsvRow->setData($customer->getPref());
                            }else{
                                $ExportCsvRow->setData($latestOrder->getPref());
                            }
                            break;
                        case "addr01": // 住所1
                            if($customer){
                                $ExportCsvRow->setData($customer->getAddr01());
                            }else{
                                $ExportCsvRow->setData($latestOrder->getAddr01());
                            }
                            break;
                        case "addr02": // 住所2
                            if($customer){
                                $ExportCsvRow->setData($customer->getAddr02());
                            }else{
                                $ExportCsvRow->setData($latestOrder->getAddr02());
                            }
                            break;
                        case "email": // メールアドレス
                            if($customer){
                                $ExportCsvRow->setData($customer->getEmail());
                            }else{
                                $ExportCsvRow->setData($latestOrder->getEmail());
                            }
                            break;
                        case "phone_number": // TEL
                            if($customer){
                                $ExportCsvRow->setData($customer->getPhoneNumber());
                            }else{
                                $ExportCsvRow->setData($latestOrder->getPhoneNumber());
                            }
                            break;
                        case "count": // 課金回数
                            if(!$remiseACMember->isLimitless()){
                                $ExportCsvRow->setData($remiseACMember->getCount());
                            }
                            break;
                        case "interval": // 課金間隔
                            if(strcmp($remiseACMember->getIntervalMark(), trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.interval_marks.m.key')) == 0){
                                $ExportCsvRow->setData($remiseACMember->getIntervalValue().trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.interval_marks.m.value'));
                            } else if (strcmp($remiseACMember->getIntervalMark(), trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.interval_marks.d.key')) == 0) {
                                $ExportCsvRow->setData($remiseACMember->getIntervalValue().trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.interval_marks.d.value'));
                            }
                            break;
                        case "day_of_month": // 課金日
                            if(strcmp($remiseACMember->getIntervalMark(), trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.interval_marks.m.key')) == 0){
                                if ($remiseACMember->getDayOfMonth()){
                                    if($remiseACMember->getDayOfMonth() == '99'){
                                        $ExportCsvRow->setData(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.day_of_month.99.value').trans('remise_payment4.ac.admin_type_edit.label.block.edit.day_of_month.caption3'));
                                    }else{
                                        $ExportCsvRow->setData(trans('remise_payment4.ac.admin_type_edit.label.block.edit.day_of_month.caption2').$remiseACMember->getDayOfMonth().trans('remise_payment4.ac.admin_type_edit.label.block.edit.day_of_month.caption3'));
                                    }
                                }
                            }
                            break;
                        case "after": // 開始時期
                            if(strcmp(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.after_marks.m.key'), $remiseACMember->getAfterMark()) == 0){
                                $ExportCsvRow->setData($remiseACMember->getAfterValue().trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.after_marks.m.value'));
                            } else if (strcmp(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.after_marks.d.key'), $remiseACMember->getAfterMark()) == 0){
                                $ExportCsvRow->setData($remiseACMember->getAfterValue().trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.after_marks.d.value'));
                            }
                            break;
                        case "skip_flg": // スキップ
                            if($remiseACMember->getSkip() == 1){
                                $ExportCsvRow->setData(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.skip.true.value'));
                            } else {
                                $ExportCsvRow->setData(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.skip.false.value'));
                            }
                            break;
                        case "stop_flg": // 解約
                            if(strcmp(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.stop.true.key'), $remiseACMember->getStop()) == 0){
                                $ExportCsvRow->setData(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.stop.true.value'));
                            } else if (strcmp(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.stop.false.key'), $remiseACMember->getStop()) == 0){
                                $ExportCsvRow->setData(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.stop.false.value'));
                            } else if (strcmp(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.stop.usage.key'), $remiseACMember->getStop()) == 0){
                                $ExportCsvRow->setData(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.stop.usage.value'));
                            }
                            break;
                        case "usage": // 最低利用期間
                            if (strcmp(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.stop.usage.key'), $remiseACMember->getStop()) == 0){
                                if(strcmp(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.usage_mark.m.key'), $remiseACMember->getUsageMark()) == 0){
                                    $ExportCsvRow->setData($remiseACMember->getUsageValue().trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.usage_mark.m.value'));
                                } else if (strcmp(trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.usage_mark.d.key'), $remiseACMember->getUsageMark()) == 0){
                                    $ExportCsvRow->setData($remiseACMember->getUsageValue().trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.usage_mark.d.value'));
                                }
                            }
                            break;
                        case "start_date": // 課金開始日
                            $ExportCsvRow->setData($remiseACMember->getStartDateStr("Y/m/d"));
                            break;
                        case "stop_date": // 課金停止予定日
                            $ExportCsvRow->setData($remiseACMember->getStopDateStr("Y/m/d"));
                            break;
                        case "create_date": // 作成日時
                            $createDate = $remiseACMember->getCreateDate();
                            if ($createDate instanceof \DateTime) {
                                $createDate->setTimeZone(new \DateTimeZone('Asia/Tokyo'));
                                $ExportCsvRow->setData($createDate->format("Y/m/d H:i:s"));
                            }
                            break;
                        case "update_date": // 更新日時
                            $updateDate = $remiseACMember->getUpdateDate();
                            if ($updateDate instanceof \DateTime) {
                                $updateDate->setTimeZone(new \DateTimeZone('Asia/Tokyo'));
                                $ExportCsvRow->setData($updateDate->format("Y/m/d H:i:s"));
                            }
                            break;
                        default :
                            $ExportCsvRow->setData(null);
                            break;
                    }
                    $ExportCsvRow->pushData();
                }

                // 出力.
                $csvService->fputcsv($ExportCsvRow->getRow());

            });
        });

        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename='.$fileName);
        $response->send();

        return $response;
    }

    /**
     * 定期購買検索用のクエリビルダを返す.
     *
     * @param Request $request
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getRemiseACMemberQueryBuilder(Request $request)
    {
        $session = $request->getSession();
        $builder = $this->formFactory
        ->createBuilder(SearchAcManagementType::class);
        $searchForm = $builder->getForm();

        $viewData = $session->get('remise_payment4.ac.admin_management_index.search', []);
        $searchData = FormUtil::submitAndGetData($searchForm, $viewData);

        // 定期購買のクエリビルダを構築.
        $qb = $this->remiseACMemberRepository->getQueryBuilderBySearchDataForAdmin($searchData);
        return $qb;
    }

    /**
     * 定期購買編集画面
     *
     * @param
     *            $request
     *
     * @Route("/%eccube_admin_route%/remise_payment4_ac_management_edit/{id}/edit", requirements={"id" = "\w+"}, name="remise_payment4_ac_management_edit")
     * @Template("@RemisePayment42/admin/ac_management_edit.twig")
     */
    public function edit(Request $request, $id = null)
    {
        try {
            $this->logService->logInfo('Ac Management');

            $requestData = $request->request->all();

            // 定期購買メンバー情報を取得
            $remiseACMember = $this->remiseACMemberRepository->findOneBy(['id' => $id]);
            $this->acService->setRemiseAcMemberRelatedOrder($remiseACMember);

            $useKeizokuEditExtend = $this->acApiService->useKeizokuEditExtend();

            // 入力フォームをセット
            $form = $this->createForm(AcManagementEditType::class, $remiseACMember);
            $form->handleRequest($request);

            $startDate = $remiseACMember->getStartDate();
            $nextDate = $remiseACMember->getNextDate();

            if ($form->isSubmitted() && $form->isValid()) {

                $mode = "";
                if(array_key_exists('ac_management_edit',$requestData))
                {
                    if(array_key_exists('mode',$requestData['ac_management_edit'])){
                        $mode = $requestData['ac_management_edit']['mode'];
                    }
                }

                // メモ保存
                if($mode == "note")
                {
                    // リクエストデータを定期購買メンバー情報にセット
                    $remiseACMemberTmp = $form->getData();
                    $remiseACMember->setNote($remiseACMemberTmp->getNote());

                    // 定期購買メンバー情報を更新
                    $this->entityManager->persist($remiseACMember);
                    $this->entityManager->flush();

                    $this->addSuccess('remise_payment4.ac.admin_management_edit.text.success.note', 'admin');
                }
                else
              {

                    // 更新または再開
                    if($mode == "update" || $mode == "restart")
                    {
                        // リクエストデータを定期購買メンバー情報にセット
                        $remiseACMember = $form->getData();

                        // 初回課金日と次回課金日が同じ場合、初回課金日を次回課金日で上書きする
                        if($startDate == $nextDate){
                            $remiseACMember->setStartDate($remiseACMember->getNextDate());
                        }

                        // 課金終了予定日を算出する
                        $stopDate = null;
                        if (! $remiseACMember->isLimitless()) {

                            // 課金間隔が月ごとの場合、課金日に次回課金日の日の部分を設定
                            if(strcmp($remiseACMember->getIntervalMark(), trans('remise_payment4.ac.plg_remise_payment4_remise_ac_type.interval_marks.m.key')) == 0){
                                $remiseACMember->setDayOfMonth($remiseACMember->getNextDate()->format('d'));
                            }else{
                                $remiseACMember->setDayOfMonth(null);
                            }

                            $stopDate = $this->acService->getStopDate($remiseACMember, $remiseACMember->getNextDate());
                        }
                        $remiseACMember->setStopDate($stopDate);

                        // スキップ済みフラグを初期化する
                        $remiseACMember->setSkipped(false);
                    }

                    if($mode == "stop") // 停止
                    {
                        $remiseACMember->setStatus(false);
                    }else if($mode == "restart"){ // 再開
                        $remiseACMember->setStatus(true);
                    }

                    // 更新日を設定
                    $remiseACMember->setUpdateDate(new \DateTime());

                    if($useKeizokuEditExtend)
                    { // 自動継続課金API有効の場合

                        // 定期購買のルミーズ連携フラグを取得
                        $useACAPI = false;
                        if(array_key_exists('ac_management_edit',$requestData))
                        {
                            if($mode == "update")
                            {
                                if(array_key_exists('update_option',$requestData['ac_management_edit'])){
                                    if($requestData['ac_management_edit']['update_option'] == 1){
                                        $useACAPI = true;
                                    }
                                }
                            }else if($mode == "restart"){
                                if(array_key_exists('restart_option',$requestData['ac_management_edit'])){
                                    if($requestData['ac_management_edit']['restart_option'] == 1){
                                        $useACAPI = true;
                                    }
                                }
                            }else if($mode == "stop"){
                                if(array_key_exists('stop_option',$requestData['ac_management_edit'])){
                                    if($requestData['ac_management_edit']['stop_option'] == 1){
                                        $useACAPI = true;
                                    }
                                }
                            }
                        }

                        // 定期購買のルミーズ連携（自動継続課金API呼出）
                        if($useACAPI){

                            // 定期購買結果取込用URLを呼び出す
                            $remiseACApiResult = $this->acApiService->requestKeizokuEditExtend(1, $remiseACMember);

                            // 定期購買結果取込のエラー判定
                            if(!$remiseACApiResult->isResult())
                            {
                                if($remiseACApiResult->getErrorLevel() == 1){
                                    $this->addWarning($remiseACApiResult->getErrorMessage(),'admin');
                                }else{
                                    $this->addError($remiseACApiResult->getErrorMessage(),'admin');
                                }

                                return [
                                    'form' => $form->createView(),
                                    'remiseACMember' => $remiseACMember,
                                ];
                            }

                        }

                    }
                    else if($mode == "stop")
                    { // 自動継続課金API無効の場合（停止時）

                        // 定期購買のルミーズ連携フラグを取得
                        $useRemiseRedirect = false;
                        if(array_key_exists('stop_option',$requestData['ac_management_edit'])){
                            if($requestData['ac_management_edit']['stop_option'] == 1){
                                $useRemiseRedirect = true;
                            }
                        }

                        // 定期購買のルミーズ連携（リンク式継続課金停止画面呼出）
                        if($useRemiseRedirect){

                            // オブジェクトを配列に変換
                            $tempArrayRemiseACMember = (array)$remiseACMember;

                            // 配列のキー名から不要文字列を削除
                            $arrayRemiseACMember = array();
                            foreach ($tempArrayRemiseACMember as $key => $value)
                            {
                                $arrayRemiseACMember[str_replace("\x00Plugin\RemisePayment42\Entity\RemiseACMember\x00","",$key)] = $value;
                            }

                            // リダイレクト処理
                            return $this->forwardToRoute('remise_payment4_ac_management_edit_stop_redirect', [] , $arrayRemiseACMember);
                        }
                    }

                    // 定期購買メンバー情報を更新
                    $this->entityManager->persist($remiseACMember);
                    $this->entityManager->flush();

                    if($mode == "update")
                    {
                        $this->addSuccess('remise_payment4.ac.admin_management_edit.text.success.update', 'admin');
                    }
                    else if($mode == "restart")
                    {
                        $this->addSuccess('remise_payment4.ac.admin_management_edit.text.success.restart', 'admin');
                    }
                    else if($mode == "stop")
                    {
                        $this->addSuccess('remise_payment4.ac.admin_management_edit.text.success.stop', 'admin');
                    }
                }
            }

            $this->logService->logInfo('Ac Management Edit -- Done');

            return [
                'form' => $form->createView(),
                'remiseACMember' => $remiseACMember,
                'useKeizokuEditExtend' => $useKeizokuEditExtend
            ];

        } catch (\Exception $e) {
            // ログ出力
            $this->entityManager->rollback();
            $this->logService->logError('Ac Management Edit', Array(
                trans('admin.common.system_error'),
                'ErrCode:' . $e->getCode(),
                'ErrMessage:' . $e->getMessage(),
                'ErrTrace:' . $e->getTraceAsString()
                ));
            $this->addError(trans('admin.common.system_error'), 'admin');
            return;
        }
    }

    /**
     * 定期購買停止画面リダイレクト処理
     *
     * @param
     *            $request
     *
     * @Route("/%eccube_admin_route%/remise_payment4_ac_management_edit/stop_redirect", name="remise_payment4_ac_management_edit_stop_redirect")
     * @Template("@RemisePayment42/admin/redirect.twig")
     */
    public function stopRedirect(Request $request)
    {
        $this->logService->logInfo('Ac Management Stop Redirect');

        $queryParameters = $request->query;

        // プラグイン基本情報の取得
        $Plugin = $this->utilService->getPlugin();
        if(!$Plugin)
        {
            $this->logService->logWarning('Ac Management Stop Redirect',
                Array(trans('remise_payment4.ac.admin_management_edit.text.stop.warning.plugin')));
            $this->addWarning(trans('remise_payment4.ac.admin_management_edit.text.stop.warning.plugin'),'admin');
            return false;
        }

        // プラグイン設定情報の取得
        $Config = $this->utilService->getConfig();
        if(!$Config)
        {
            $this->logService->logWarning('Ac Management Stop Redirect',
                Array(trans('remise_payment4.ac.admin_management_edit.text.stop.warning.plugin')));
            $this->addWarning(trans('remise_payment4.ac.admin_management_edit.text.stop.warning.plugin'),'admin');
            return $this->forwardToRoute('remise_payment4_ac_management_edit', ['id' => $queryParameters->get("id")]);
        }

        // プラグイン設定詳細情報の取得
        $ConfigInfo = $Config->getUnserializeInfo();
        if(!$ConfigInfo)
        {
            $this->logService->logWarning('Ac Management Stop Redirect',
                Array(trans('remise_payment4.ac.admin_management_edit.text.stop.warning.plugin')));
            $this->addWarning(trans('remise_payment4.ac.admin_management_edit.text.stop.warning.plugin'),'admin');
            return $this->forwardToRoute('remise_payment4_ac_management_edit', ['id' => $queryParameters->get("id")]);
        }

        // カード更新URL取得
        $sendUrl = $ConfigInfo->getCardUrl();
        if(!$sendUrl or empty($sendUrl)){
            $this->logService->logWarning('Ac Management Stop Redirect',
                Array(trans('remise_payment4.ac.admin_management_edit.text.stop.warning.plugin.card_url')));
            $this->addWarning(trans('remise_payment4.ac.admin_management_edit.text.stop.warning.plugin.card_url'),'admin');
            return $this->forwardToRoute('remise_payment4_ac_management_edit', ['id' => $queryParameters->get("id")]);
        }

        $exitUrl = $this->generateUrl('remise_payment4_ac_management_edit_stop_return', ['id' => $queryParameters->get("id")], UrlGeneratorInterface::ABSOLUTE_URL);
        $retUrl = $this->generateUrl('remise_payment4_ac_management_edit_stop_return', ['id' => $queryParameters->get("id")], UrlGeneratorInterface::ABSOLUTE_URL);
        $ngRetUrl = $this->generateUrl('remise_payment4_ac_management_edit_stop_return', ['id' => $queryParameters->get("id")], UrlGeneratorInterface::ABSOLUTE_URL);

        $orderResultCards = $queryParameters->get("OrderResultCards");
        $orderResultCard = $orderResultCards[0];
        $orderResult = $orderResultCard->getOrderResult();
        $order = $orderResult->getOrder();
        $customer = $order->getCustomer();

        $ac_mail = "";
        if ($customer) {
            $ac_mail = $customer->getEmail();
        } else {
            $ac_mail = $order->getEmail();
        }

        $sendData = array();
        // 基本情報部
        $sendData['ECCUBE_VER']     = Constant::VERSION;            // EC-CUBEバージョン番号
        if (!$Plugin)
        {
            $sendData[$Plugin->getCode() . '_PLG_VER'] = $Plugin->getVersion(); // ルミーズ決済プラグインバージョン番号
        }
        $sendData['SHOPCO']         = $ConfigInfo->getShopco();     // 加盟店コード
        $sendData['HOSTID']         = $ConfigInfo->getHostid();     // ホストID
        $sendData['MAIL']           = $ac_mail;                     // e-mail

        // 設定情報部
        $sendData['EXITURL']        = $exitUrl;                     // 中止URL
        $sendData['RETURL']         = $retUrl;                      // 完了通知URL
        $sendData['DIRECT']         = 'OFF';                        // ダイレクトモード
        $sendData['OPT']            = '';                           // オプション
        $sendData['NG_RETURL']      = $ngRetUrl;                    // NG完了通知URL

        // 継続課金情報部
        $sendData['AUTOCHARGE']     = trans('remise_payment4.ac.front.payment.senddata.autocharge.stop'); // 自動継続課金
        $sendData['AC_MEMBERID']    = $queryParameters->get("id"); // メンバーID

        $this->logService->logInfo('Ac Management Stop Redirect -- Done');
        return [
            'send_url'  => $sendUrl,
            'send_data' => $sendData,
        ];
    }

    /**
     * 定期購買停止完了通知処理
     *
     * @param
     *            $request
     *
     * @Route("/%eccube_admin_route%/remise_payment4_ac_management_edit/{id}/edit/stop_return", requirements={"id" = "\w+"}, name="remise_payment4_ac_management_edit_stop_return")
     */
    public function stopReturn(Request $request, $id = null)
    {
        $this->logService->logInfo('Ac Management Stop Return');

        $requestData = $request->request->all();

        $this->logService->logInfo('Ac Management Stop Return -- Request', $requestData);

        $memberid = $this->utilService->getValue($requestData, 'X-AC_MEMBERID');
        $r_code = $this->utilService->getValue($requestData, 'X-R_CODE');

        if($r_code == "0:0000"){
            // 定期購買メンバー情報を更新
            $remiseACMember = $this->remiseACMemberRepository->findOneBy(['id' => $memberid]);
            $remiseACMember->setStatus(false);
            $remiseACMember->setUpdateDate(new \DateTime());
            $this->entityManager->persist($remiseACMember);
            $this->entityManager->flush();

            $this->addSuccess('remise_payment4.ac.admin_management_edit.text.success.stop', 'admin');

        }

        $this->logService->logInfo('Ac Management Stop Return -- Done');

        return $this->forwardToRoute('remise_payment4_ac_management_edit', ['id' => $id]);
    }

    /**
     * 課金失敗の解除
     *
     * @param  $request
     * @param  $id  受注ID
     *
     * @Route("/%eccube_admin_route%/remise_payment4_ac_management_edit/{id}/state_change_success", requirements={"id"="\w+"}, name="remise_payment4_admin_ac_state_change_success")
     */
    public function stateChangeToSuccess(Request $request, $id = null)
    {
        $this->logService->logInfo('Ac Management Status Change Success');

        $this->isTokenValid();

        if (empty($id))
        {
            $this->logService->logError('Ac Management Status Change Success -- Empty Id');
            return $this->redirectToRoute('admin_order_edit', ['id' => $id]);
        }

        // 受注情報の取得
        $Order = $this->utilService->getOrder($id);

        if (!$Order)
        {
            $this->logService->logError('Ac Management Status Change Success -- Not Found Order (' . $id . ')');
            return $this->redirectToRoute('admin_order_edit', ['id' => $id]);
        }

        // 決済履歴詳細の取得
        $OrderResultCard = $this->utilService->getOrderResultCard($Order->getId());

        if (!$OrderResultCard)
        {
            $this->logService->logError('Ac Management Status Change Success -- Not Found OrderResultCard (' . $id . ')');
            return $this->redirectToRoute('admin_order_edit', ['id' => $id]);
        }

        // 受注情報のステータスが新規受付の場合、「入金済み」に変更
        if($Order->getOrderStatus()->getId() == OrderStatus::NEW)
        {
            $this->utilService->setOrderStatusByPaid($Order);
        }
        $this->entityManager->persist($Order);
        $this->entityManager->flush();

        // カード決済履歴詳細の設定
        $OrderResultCard->setState(trans('remise_payment4.common.label.card.state.result.ac.success'));
        $OrderResultCard->setUpdateDate(new \DateTime());

        // カード決済履歴詳細の登録
        $this->logService->logInfo('Ac Management Status Change Success -- Save OrderResultCard (' . $id . ')');
        $this->entityManager->persist($OrderResultCard);
        $this->entityManager->flush($OrderResultCard);

        $this->logService->logInfo('Ac Management Status Change Success');
        $this->addSuccess(trans('remise_payment4.admin_order_edit.text.card.state.result.ac.success'), 'admin');

        return $this->redirectToRoute('admin_order_edit', ['id' => $id]);
    }

}
