<?php

namespace Customize\Controller\Admin;

use Customize\Entity\Subscription;
use Customize\Repository\SubscriptionEventLogRepository;
use Customize\Repository\SubscriptionItemRepository;
use Customize\Repository\SubscriptionOrderRepository;
use Customize\Repository\SubscriptionRepository;
use Customize\Service\Subscription\SubscriptionAdminService;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Customer;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class SubscriptionController extends AbstractController
{
    private SubscriptionRepository $subscriptionRepository;
    private SubscriptionItemRepository $subscriptionItemRepository;
    private SubscriptionOrderRepository $subscriptionOrderRepository;
    private SubscriptionEventLogRepository $subscriptionEventLogRepository;
    private SubscriptionAdminService $subscriptionAdminService;

    public function __construct(
        SubscriptionRepository $subscriptionRepository,
        SubscriptionItemRepository $subscriptionItemRepository,
        SubscriptionOrderRepository $subscriptionOrderRepository,
        SubscriptionEventLogRepository $subscriptionEventLogRepository,
        SubscriptionAdminService $subscriptionAdminService
    ) {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->subscriptionItemRepository = $subscriptionItemRepository;
        $this->subscriptionOrderRepository = $subscriptionOrderRepository;
        $this->subscriptionEventLogRepository = $subscriptionEventLogRepository;
        $this->subscriptionAdminService = $subscriptionAdminService;
    }

    /**
     * @Route("/%eccube_admin_route%/subscription", name="admin_subscription_index", methods={"GET"})
     * @Route("/%eccube_admin_route%/subscription/page/{page_no}", requirements={"page_no" = "\d+"}, name="admin_subscription_index_page", methods={"GET"})
     * @Template("@admin/Subscription/index.twig")
     */
    public function index(Request $request, PaginatorInterface $paginator, $page_no = null)
    {
        $page_no = (int) ($page_no ?: 1);
        $status = (string) $request->query->get('status', '');
        $retryRaw = $request->query->get('retry_count');
        $retryExact = null;
        if (null !== $retryRaw && '' !== $retryRaw) {
            $retryExact = (int) $retryRaw;
        }

        $nextFrom = $this->parseDateBoundary($request->query->get('next_from'), false);
        $nextTo = $this->parseDateBoundary($request->query->get('next_to'), true);

        $qb = $this->subscriptionRepository->getAdminListQueryBuilder(
            '' !== $status ? $status : null,
            $nextFrom,
            $nextTo,
            $retryExact
        );

        $pagination = $paginator->paginate(
            $qb,
            $page_no,
            $this->eccubeConfig->get('eccube_default_page_count')
        );

        return [
            'pagination' => $pagination,
            'filters' => [
                'status' => $status,
                'next_from' => $request->query->get('next_from'),
                'next_to' => $request->query->get('next_to'),
                'retry_count' => null !== $retryExact ? (string) $retryExact : '',
            ],
            'statusChoices' => $this->getStatusChoices(),
            'subscriptionEnabled' => $this->subscriptionAdminService->isSubscriptionEnabled(),
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/subscription/{id}/retry-now", requirements={"id" = "\d+"}, name="admin_subscription_retry", methods={"POST"})
     */
    public function retryNow(Request $request, int $id): Response
    {
        $this->isTokenValid();

        return $this->runBillAction($id, 'admin.subscription.flash.retry');
    }

    /**
     * @Route("/%eccube_admin_route%/subscription/{id}/force-bill", requirements={"id" = "\d+"}, name="admin_subscription_force_bill", methods={"POST"})
     */
    public function forceBill(Request $request, int $id): Response
    {
        $this->isTokenValid();

        return $this->runBillAction($id, 'admin.subscription.flash.force_bill');
    }

    /**
     * @Route("/%eccube_admin_route%/subscription/{id}/cancel", requirements={"id" = "\d+"}, name="admin_subscription_cancel", methods={"POST"})
     */
    public function cancel(Request $request, int $id): Response
    {
        $this->isTokenValid();

        $subscription = $this->subscriptionRepository->find($id);
        if (!$subscription instanceof Subscription) {
            throw new NotFoundHttpException();
        }

        try {
            $mode = $this->subscriptionAdminService->cancelSubscription($subscription);
            if ('force_cancelled' === $mode) {
                $this->addSuccess($this->translator->trans('admin.subscription.flash.cancel_force'), 'admin');
            } else {
                $this->addSuccess($this->translator->trans('admin.subscription.flash.cancel'), 'admin');
            }
        } catch (\Throwable $e) {
            $this->addDanger($e->getMessage(), 'admin');
        }

        return $this->redirectToRoute('admin_subscription_detail', ['id' => $id]);
    }

    /**
     * @Route("/%eccube_admin_route%/subscription/{id}", requirements={"id" = "\d+"}, name="admin_subscription_detail", methods={"GET"})
     * @Template("@admin/Subscription/detail.twig")
     */
    public function detail(int $id)
    {
        $subscription = $this->subscriptionRepository->find($id);
        if (!$subscription instanceof Subscription) {
            throw new NotFoundHttpException();
        }

        /** @var Customer|null $Customer */
        $Customer = $subscription->getCustomer();

        return [
            'Subscription' => $subscription,
            'Customer' => $Customer,
            'Items' => $this->subscriptionItemRepository->findBySubscriptionOrdered($subscription),
            'SubscriptionOrders' => $this->subscriptionOrderRepository->findRecentBySubscription($subscription),
            'EventLogs' => $this->subscriptionEventLogRepository->findRecentBySubscription($subscription),
            'subscriptionEnabled' => $this->subscriptionAdminService->isSubscriptionEnabled(),
        ];
    }

    private function runBillAction(int $id, string $successTransKey): Response
    {
        $subscription = $this->subscriptionRepository->find($id);
        if (!$subscription instanceof Subscription) {
            throw new NotFoundHttpException();
        }

        try {
            $this->subscriptionAdminService->billNow($subscription);
            $this->addSuccess($this->translator->trans($successTransKey), 'admin');
        } catch (\Throwable $e) {
            $this->addDanger($e->getMessage(), 'admin');
        }

        return $this->redirectToRoute('admin_subscription_detail', ['id' => $id]);
    }

    private function parseDateBoundary($value, bool $endOfDay): ?\DateTimeInterface
    {
        if (!\is_string($value) || '' === $value) {
            return null;
        }
        $tzName = $this->eccubeConfig->get('timezone') ?: 'UTC';
        $tz = new \DateTimeZone($tzName);
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $value, $tz);
        if (!$dt instanceof \DateTimeImmutable) {
            return null;
        }
        if ($endOfDay) {
            return $dt->setTime(23, 59, 59);
        }

        return $dt->setTime(0, 0, 0);
    }

    /**
     * @return array<string, string>
     */
    private function getStatusChoices(): array
    {
        return [
            '' => $this->translator->trans('admin.subscription.filter.status_all'),
            Subscription::STATUS_PENDING_ACTIVATION => Subscription::STATUS_PENDING_ACTIVATION,
            Subscription::STATUS_ACTIVE => Subscription::STATUS_ACTIVE,
            Subscription::STATUS_PAUSED => Subscription::STATUS_PAUSED,
            Subscription::STATUS_PAST_DUE => Subscription::STATUS_PAST_DUE,
            Subscription::STATUS_CANCELLED => Subscription::STATUS_CANCELLED,
            Subscription::STATUS_EXPIRED => Subscription::STATUS_EXPIRED,
        ];
    }
}
