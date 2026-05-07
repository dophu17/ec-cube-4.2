<?php
namespace Plugin\RemisePayment42\Controller\Admin;

use Eccube\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Plugin\RemisePayment42\Entity\Config;
use Plugin\RemisePayment42\Entity\ConfigInfo;
use Plugin\RemisePayment42\Entity\RemiseACType;
use Plugin\RemisePayment42\Repository\RemiseACTypeRepository;
use Plugin\RemisePayment42\Service\LogService;
use Plugin\RemisePayment42\Service\UtilService;
use Plugin\RemisePayment42\Service\AcService;
use Plugin\RemisePayment42\Form\Type\Admin\AcTypeEditType;

/**
 * 定期購買設定
 */
class AcTypeEditController extends AbstractController
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
     * @var RemiseACTypeRepository
     */
    protected $remiseACTypeRepository;

    private $nextDateString = "";

    private $stopDateString = "";

    /**
     * コンストラクタ
     *
     * @param UtilService $utilService
     * @param LogService $logService
     * @param AcService $acService
     * @param RemiseACTypeRepository $remiseACTypeRepository
     */
    public function __construct(UtilService $utilService, LogService $logService, RemiseACTypeRepository $remiseACTypeRepository, AcService $acService)
    {
        $this->utilService = $utilService;
        $this->logService = $logService;
        $this->remiseACTypeRepository = $remiseACTypeRepository;
        $this->acService = $acService;
    }

    /**
     * 定期購買設定一覧画面
     *
     * @param
     *            $request
     *
     * @Route("/%eccube_admin_route%/remise_payment4_ac_type_edit_index", name="remise_payment4_ac_type_edit_index")
     * @Template("@RemisePayment42/admin/ac_type_edit_index.twig")
     */
    public function index(Request $request)
    {
        try {
            $this->logService->logInfo('Ac Type Edit Index');

            $remiseACTypes = $this->remiseACTypeRepository->findBy([], [
                'sort_no' => 'DESC'
            ]);

            $this->logService->logInfo('Ac Type Edit Index -- Done');

            return [
                'remiseACTypes' => $remiseACTypes
            ];
        } catch (\Exception $e) {
            // ログ出力
            $this->logService->logError('Ac Type Edit Index', Array(
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
     * 定期購買設定登録・編集画面
     *
     * @param
     *            $request
     *
     * @Route("/%eccube_admin_route%/remise_payment4_ac_type_edit/new", name="remise_payment4_ac_type_edit_new")
     * @Route("/%eccube_admin_route%/remise_payment4_ac_type_edit/{id}/edit", requirements={"id" = "\d+"}, name="remise_payment4_ac_type_edit")
     * @Template("@RemisePayment42/admin/ac_type_edit.twig")
     */
    public function edit(Request $request, $mode = null, $id = null)
    {
        try {
            $this->logService->logInfo('Ac Type Edit');

            // モードを取得
            $mode = "";
            $requestData = $request->request->all();
            if (array_key_exists('mode', $requestData)) {
                $mode = $requestData['mode'];
            }

            if ($id == null) {
                $remiseACType = $this->remiseACTypeRepository->findOneBy([], [
                    'sort_no' => 'DESC'
                ]);
                $sortNo = 1;
                if ($remiseACType) {
                    $sortNo = $remiseACType->getSortNo() + 1;
                }

                $remiseACType = new RemiseACType();
                $remiseACType->setLimitless(true)
                    ->setSortNo($sortNo)
                    ->setVisible(true)
                    ->setCreateDate(new \DateTime())
                    ->setUpdateDate(new \DateTime());
            } else {
                $remiseACType = $this->remiseACTypeRepository->findOneBy([
                    'id' => $id
                ]);
                if (! $remiseACType) {
                    // ログ出力
                    $this->logService->logWarning('Ac Type Edit -- Not Found', Array(
                        trans('remise_payment4.ac.admin_type_edit.text.warning.notfound'),
                        'id:' . $id
                    ));
                    $this->addWarning(trans('remise_payment4.ac.admin_type_edit.text.warning.notfound'), 'admin');
                    return $this->redirectToRoute('remise_payment4_ac_type_edit_index');
                }
            }

            // 入力フォーム
            $form = $this->createForm(AcTypeEditType::class, $remiseACType);
            $form->handleRequest($request);

            // シミュレーションモード
            if ($form->isSubmitted() && $form->isValid() & strcmp($mode, "simulation") == 0) {

                $remiseACType = $form->getData();

                $errorMsg = $this->simulation($requestData, $remiseACType);

                return [
                    'form' => $form->createView(),
                    'remiseACType' => $remiseACType,
                    'nextDate' => $this->nextDateString,
                    'stopDate' => $this->stopDateString,
                    'errorMsg' => $errorMsg
                ];
            } else if ($form->isSubmitted() && $form->isValid()) {
                // 登録ボタン押下

                $remiseACType = $form->getData();

                if ($id != null) {
                    // 更新日付の更新
                    $remiseACType->setUpdateDate(new \DateTime());
                }

                // DB登録時に未設定の場合、nullではなく空文字が渡されSQLエラーになってしまうための対応
                if (!array_key_exists('day_of_month', $requestData['ac_type_edit']) || empty( $requestData['ac_type_edit']["day_of_month"])) {
                    $remiseACType->setDayOfMonth(null);
                }

                $this->entityManager->persist($remiseACType);
                $this->entityManager->flush();

                $this->addSuccess('admin.common.save_complete', 'admin');

                return $this->redirectToRoute('remise_payment4_ac_type_edit', [
                    'id' => $remiseACType->getId()
                ]);
            }

            $this->logService->logInfo('Ac Type Edit -- Done');

            return [
                'form' => $form->createView(),
                'remiseACType' => $remiseACType
            ];
        } catch (\Exception $e) {
            // ログ出力
            $this->logService->logError('Ac Type Edit', Array(
                trans('admin.common.system_error'),
                'ErrCode:' . $e->getCode(),
                'ErrMessage:' . $e->getMessage(),
                'ErrTrace:' . $e->getTraceAsString()
            ));
            $this->addError(trans('admin.common.system_error'), 'admin');
            return $this->redirectToRoute('remise_payment4_ac_type_edit_index');
        }
    }

    /**
     * 初回課金日、最終課金予定日を取得
     *
     * @param Array $requestData
     * @param RemiseACType $remiseACType
     *
     */
    private function simulation(Array $requestData, RemiseACType $remiseACType)
    {
        $nextDate = false;
        $stopDate = false;

        // 課金日を取得
        $orderDateString = "";
        if (array_key_exists('ac_type_edit', $requestData)) {
            if (array_key_exists('order_date', $requestData['ac_type_edit'])) {
                $orderDateString = $requestData['ac_type_edit']['order_date'];
            } else {
                return trans('remise_payment4.ac.admin_type_edit.text.block.simulation.purchase date.notformat');
            }
        } else {
            return trans('remise_payment4.ac.admin_type_edit.text.block.simulation.purchase date.notformat');
        }
        // 入力チェック
        if (empty($orderDateString)) {
            return trans('remise_payment4.ac.admin_type_edit.text.block.simulation.purchase date.notformat');
        } else {
            list ($Y, $m, $d) = explode('-', $orderDateString);
            if (checkdate($m, $d, $Y) === false) {
                return trans('remise_payment4.ac.admin_type_edit.text.block.simulation.purchase date.notformat');
            } else {

                $orderDate = new \DateTime($orderDateString);
                $nextDate = $this->acService->getFirstDate($remiseACType, $orderDate);
                if (! $remiseACType->isLimitless()) {
                    $stopDate = $this->acService->getStopDate($remiseACType, $nextDate);
                }
            }
        }

        if ($nextDate) {
            $this->nextDateString = $nextDate->format('Y/m/d');
        } else {
            $this->nextDateString = trans('remise_payment4.ac.admin_type_edit.text.block.simulation.default_date');
        }

        if ($stopDate) {
            $this->stopDateString = $stopDate->format('Y/m/d');
        } else {
            $this->stopDateString = trans('remise_payment4.ac.admin_type_edit.text.block.simulation.default_date');
        }
        return;
    }

    /**
     * @Route("/%eccube_admin_route%/remise_payment4_ac_type_edit/{id}/visible", requirements={"id" = "\d+"}, name="remise_payment4_ac_type_edit_visible", methods={"PUT"})
     */
    public function visible(RemiseACType $remiseACType)
    {
        $this->isTokenValid();

        $remiseACType->setVisible(!$remiseACType->isVisible());

        $this->entityManager->flush();

        if ($remiseACType->isVisible()) {
            $this->addSuccess(trans('admin.common.to_show_complete', ['%name%' => $remiseACType->getName()]), 'admin');
        } else {
            $this->addSuccess(trans('admin.common.to_hide_complete', ['%name%' => $remiseACType->getName()]), 'admin');
        }

        return $this->redirectToRoute('remise_payment4_ac_type_edit_index');
    }

    /**
     * @Route("/%eccube_admin_route%/remise_payment4_ac_type_edit/move", name="remise_payment4_ac_type_edit_move", methods={"POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function moveSortNo(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }

        if ($this->isTokenValid()) {
            $sortNos = $request->request->all();
            foreach ($sortNos as $id => $sortNo) {
                /** @var RemiseACType $remiseACType */
                $remiseACType = $this->remiseACTypeRepository
                ->find($id);
                $remiseACType->setSortNo($sortNo);
                $this->entityManager->persist($remiseACType);
            }
            $this->entityManager->flush();

            return new Response();
        }
    }

    /**
     * @Route("/%eccube_admin_route%/remise_payment4_ac_type_edit/{id}/delete", requirements={"id" = "\d+"}, name="remise_payment4_ac_type_edit_delete", methods={"DELETE"})
     *
     * @param Request $request
     * @param Payment $TargetPayment
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function delete(Request $request, RemiseACType $TargetRemiseACType)
    {
        $this->isTokenValid();

        try {

            // 定期購買設定を他のテーブルで参照しているものがある場合、エラーを返却する。
            $ProductClasses = $this->acService->getProductClassListByRemisePayment4AcActypeId($TargetRemiseACType->getId());
            if($ProductClasses){
                $message = trans('remise_payment4.ac.admin_type_edit_index.text.error.delete');
                $this->addError($message, 'admin');
                return $this->redirectToRoute('remise_payment4_ac_type_edit_index');
            }

            $this->remiseACTypeRepository->delete($TargetRemiseACType);
            $this->entityManager->flush();

            $sortNo = 1;
            $remiseACTypes = $this->remiseACTypeRepository->findBy([], ['sort_no' => 'ASC']);
            foreach ($remiseACTypes as $remiseACType) {
                $remiseACType->setSortNo($sortNo++);
                $this->entityManager->persist($remiseACType);
            }
            $this->entityManager->flush();

            $this->addSuccess('admin.common.delete_complete', 'admin');
        } catch (ForeignKeyConstraintViolationException $e) {
            $this->entityManager->rollback();

            $message = trans('admin.common.delete_error_foreign_key', ['%name%' => $TargetRemiseACType->getName()]);
            $this->addError($message, 'admin');
        }

        return $this->redirectToRoute('remise_payment4_ac_type_edit_index');
    }

}
