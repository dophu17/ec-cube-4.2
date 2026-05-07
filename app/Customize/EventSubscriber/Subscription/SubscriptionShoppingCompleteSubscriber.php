<?php

namespace Customize\EventSubscriber\Subscription;

use Customize\Service\Subscription\SubscriptionActivator;
use Eccube\Entity\Order;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class SubscriptionShoppingCompleteSubscriber implements EventSubscriberInterface
{
    public const SESSION_SUBSCRIPTION_BAG = 'eccube.front.subscription.checkout_payload';

    private RequestStack $requestStack;
    private SubscriptionActivator $subscriptionActivator;
    private bool $subscriptionEnabled;
    private bool $allowTestInterval;
    private int $maxRetry;

    public function __construct(
        RequestStack $requestStack,
        SubscriptionActivator $subscriptionActivator,
        bool $subscriptionEnabled,
        bool $allowTestInterval,
        int $subscriptionMaxRetry
    ) {
        $this->requestStack = $requestStack;
        $this->subscriptionActivator = $subscriptionActivator;
        $this->subscriptionEnabled = $subscriptionEnabled;
        $this->allowTestInterval = $allowTestInterval;
        $this->maxRetry = $subscriptionMaxRetry;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EccubeEvents::FRONT_SHOPPING_COMPLETE_INITIALIZE => ['onShoppingCompleteInitialize', 10],
        ];
    }

    public function onShoppingCompleteInitialize(EventArgs $event): void
    {
        if (!$this->subscriptionEnabled) {
            return;
        }

        /** @var Order|null $Order */
        $Order = $event->getArgument('Order');
        if (!$Order instanceof Order) {
            return;
        }

        if (!$Order->getPaymentDate()) {
            return;
        }

        $Customer = $Order->getCustomer();
        if (!$Customer || !$Customer->getId()) {
            return;
        }

        $session = $this->getSession();
        if (!$session instanceof SessionInterface) {
            return;
        }

        $bag = (array) $session->get(self::SESSION_SUBSCRIPTION_BAG);
        $pre = (string) $Order->getPreOrderId();

        $payload = $bag[$pre] ?? null;

        unset($bag[$pre]);
        $session->set(self::SESSION_SUBSCRIPTION_BAG, $bag);

        if (!$payload || empty($payload['enabled']) || empty($payload['cycle'])) {
            return;
        }

        try {
            $this->subscriptionActivator->createFromPaidOrder($Order, (string) $payload['cycle'], $this->allowTestInterval, $this->maxRetry);
        } catch (\Throwable $e) {
            log_error('[subscription] Không tạo được subscription: '.$e->getMessage());
        }
    }

    private function getSession(): ?SessionInterface
    {
        $req = $this->requestStack->getCurrentRequest();

        return $req && $req->hasSession() ? $req->getSession() : null;
    }
}
