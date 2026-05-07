<?php

namespace Plugin\RemisePayment42\Controller;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Customer;

use Symfony\Component\HttpFoundation\RequestStack;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use Plugin\RemisePayment42\Entity\Payquick;
use Plugin\RemisePayment42\Service\LogService;
use Plugin\RemisePayment42\Service\UtilService;

/**
 * 会員処理用
 */
class CustomerController extends AbstractController
{
    /**
     * @var RequestStack
     */
    protected $session;

    /**
     * @var UtilService
     */
    protected $utilService;

    /**
     * @var LogService
     */
    protected $logService;

    /**
     * コンストラクタ
     *
     * @param RequestStack $session
     * @param UtilService $utilService
     * @param LogService $logService
     */
    public function __construct(
        RequestStack $session,
        UtilService $utilService,
        LogService $logService
    ) {
        $this->RequestStack = $session;
        $this->utilService = $utilService;
        $this->logService = $logService;
    }

    /**
     * 管理画面　ペイクイック削除
     *
     * @param  $request
     * @param  $cid  会員ID
     * @param  $pid  ペイクイックID
     *
     * @Route("/%eccube_admin_route%/customer/{cid}/delete_payquick/{pid}", requirements={"cid"="\d+", "pid"="\d+"}, name="remise_payment4_admin_delete_payquick")
     */
    public function deletePayquickForAdmin(Request $request, $cid = null, $pid = null)
    {
        $this->logService->logInfo('Customer Delete Payquick (cid:' . $cid . ', pid:' . $pid . ')');

        if (empty($cid) || empty($pid))
        {
            $this->logService->logError('Customer Delete Payquick -- Empty Id');
            return $this->redirectToRoute('admin_customer_edit', ['id' => $cid]);
        }

        // 会員情報の取得
        $Customer = $this->utilService->getCustomer($cid);

        if (!$Customer)
        {
            $this->logService->logError('Customer Delete Payquick -- Not Found Customer (' . $cid . ')');
            return $this->redirectToRoute('admin_customer_edit', ['id' => $cid]);
        }

        // ペイクイック情報の取得
        $Payquick = $this->utilService->getPayquickById($pid, $cid);

        if (!$Payquick)
        {
            $this->logService->logError('Customer Delete Payquick -- Not Found Payquick (cid:' . $cid . ', pid:' . $pid . ')');
            return $this->redirectToRoute('admin_customer_edit', ['id' => $cid]);
        }

        // ペイクイック情報の削除
        $this->utilService->deletePayquick($Payquick);
        $this->entityManager->flush();

        $this->logService->logInfo('Customer Delete Payquick -- Done');

        $this->addSuccess(trans('remise_payment4.admin_customer_edit.text.payquick_delete.completed'), 'admin');
        return $this->redirectToRoute('admin_customer_edit', ['id' => $cid]);
    }

    /**
     * ペイクイック削除
     *
     * @param  $request
     * @param  $id  ペイクイックID
     *
     * @Route("/mypage/change/delete_payquick/{id}", requirements={"id"="\d+"}, name="remise_payment4_delete_payquick")
     */
    public function deletePayquick(Request $request, $id = null)
    {
        $this->logService->logInfo('Customer Delete Payquick (' . $id . ')');

        if (empty($id))
        {
            $this->logService->logError('Customer Delete Payquick -- Empty Id');
            return $this->redirectToRoute('mypage_change');
        }

        // 会員情報の取得
        $Customer = $this->getUser();

        if (!$Customer)
        {
            $this->logService->logError('Customer Delete Payquick -- Not Login Customer');
            return $this->redirectToRoute('mypage_change');
        }

        // ペイクイック情報の取得
        $Payquick = $this->utilService->getPayquickById($id, $Customer->getId());

        if (!$Payquick)
        {
            $this->logService->logError('Customer Delete Payquick -- Not Found Payquick (id:' . $id . ', cid:' . $Customer->getId() . ')');
            return $this->redirectToRoute('mypage_change');
        }

        // ペイクイック情報の削除
        $this->utilService->deletePayquick($Payquick);
        $this->entityManager->flush();

        $this->logService->logInfo('Customer Delete Payquick -- Done');

        $this->session->set('remise_payment4.delete_payquick', '1');
        return $this->redirectToRoute('mypage_change');
    }
}
