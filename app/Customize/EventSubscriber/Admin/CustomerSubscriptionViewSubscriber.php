<?php

namespace Customize\EventSubscriber\Admin;

use Customize\Repository\SubscriptionRepository;
use Eccube\Entity\Customer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Enriches admin customer index / edit responses with Subscription data without modifying core controllers.
 */
final class CustomerSubscriptionViewSubscriber implements EventSubscriberInterface
{
    private SubscriptionRepository $subscriptionRepository;

    public function __construct(SubscriptionRepository $subscriptionRepository)
    {
        $this->subscriptionRepository = $subscriptionRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::VIEW => ['onKernelView', 0]];
    }

    public function onKernelView(ViewEvent $event): void
    {
        $route = $event->getRequest()->attributes->get('_route');
        if (!\is_string($route)) {
            return;
        }

        /** @var array<string, mixed>|null $payload */
        $payload = $event->getControllerResult();
        if (!\is_array($payload)) {
            return;
        }

        if (\in_array($route, ['admin_customer', 'admin_customer_page'], true)) {
            $event->setControllerResult($this->buildCustomerIndexPayload($payload));

            return;
        }

        if (\in_array($route, ['admin_customer_edit', 'admin_customer_new'], true)) {
            $event->setControllerResult($this->buildCustomerEditPayload($payload));
        }
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function buildCustomerEditPayload(array $payload): array
    {
        if (!isset($payload['Customer']) || !$payload['Customer'] instanceof Customer) {
            return $payload;
        }
        /** @var Customer $Customer */
        $Customer = $payload['Customer'];
        if (null === $Customer->getId()) {
            $payload['CustomerSubscriptions'] = [];

            return $payload;
        }

        $payload['CustomerSubscriptions'] = $this->subscriptionRepository->findByCustomerIdForAdmin((int) $Customer->getId());

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function buildCustomerIndexPayload(array $payload): array
    {
        $pagination = $payload['pagination'] ?? null;
        if (!\is_object($pagination) || !method_exists($pagination, 'getItems')) {
            $payload['SubscriptionCountByCustomerId'] = [];

            return $payload;
        }

        $items = $pagination->getItems();
        if (!\is_array($items)) {
            $items = iterator_to_array($items);
        }

        $ids = [];
        foreach ($items as $Customer) {
            if ($Customer instanceof Customer && null !== $Customer->getId()) {
                $ids[] = (int) $Customer->getId();
            }
        }

        $payload['SubscriptionCountByCustomerId'] = [] === $ids ? [] : $this->subscriptionRepository->countGroupedByCustomerIds(array_values(array_unique($ids)));

        return $payload;
    }
}
