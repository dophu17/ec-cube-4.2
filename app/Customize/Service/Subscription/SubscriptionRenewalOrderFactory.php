<?php

namespace Customize\Service\Subscription;

use Customize\Entity\Subscription;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Shipping;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Util\StringUtil;
use RuntimeException;

/**
 * Tạo đơn gia hạn từ snapshot + bản gốc (copy cấu trúc giao hàng & dòng hàng).
 */
final class SubscriptionRenewalOrderFactory
{
    private EntityManagerInterface $entityManager;
    private OrderRepository $orderRepository;
    private OrderStatusRepository $orderStatusRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        OrderRepository $orderRepository,
        OrderStatusRepository $orderStatusRepository
    ) {
        $this->entityManager = $entityManager;
        $this->orderRepository = $orderRepository;
        $this->orderStatusRepository = $orderStatusRepository;
    }

    public function createRenewalOrder(Subscription $subscription): Order
    {
        $template = $this->orderRepository->find($subscription->getBaseOrderId());
        if (!$template instanceof Order) {
            throw new RuntimeException('subscription: không tìm thấy base order #'.$subscription->getBaseOrderId());
        }

        $processing = $this->orderStatusRepository->find(OrderStatus::PROCESSING);
        if (!$processing instanceof OrderStatus) {
            throw new RuntimeException('subscription: trạng thái PROCESSING không tồn tại trong master.');
        }

        $newOrder = new Order($processing);
        $newOrder->copyProperties($template, ['id', 'pre_order_id', 'order_no', 'order_date', 'payment_date',
            'order_status', 'OrderItems', 'Shippings', 'Customer', 'MailHistories', ]);

        $newOrder->setPreOrderId($this->createPreOrderId());
        $newOrder->setOrderStatus($processing);
        $newOrder->setCustomer($template->getCustomer());

        if (null !== $template->getPayment()) {
            $newOrder->setPayment($template->getPayment());
        }
        if (null !== $template->getDeviceType()) {
            $newOrder->setDeviceType($template->getDeviceType());
        }
        $newOrder->setPaymentDate(null);
        $newOrder->setMessage(null);
        $newOrder->setOrderDate(new \DateTime());

        $str = static function ($n): string {
            return (string) ($n ?? '0');
        };

        $newOrder->setSubtotal($str($subscription->getSubtotalAmount()));
        $newOrder->setDiscount($str($subscription->getDiscountAmount()));
        $newOrder->setDeliveryFeeTotal($str($subscription->getShippingFee()));
        $newOrder->setTax($str($subscription->getTaxAmount()));
        $newOrder->setTotal($str($subscription->getTotalAmount()));
        $newOrder->setPaymentTotal($str($subscription->getTotalAmount()));

        $this->entityManager->persist($newOrder);

        foreach ($template->getShippings() as $oldShipping) {
            /** @var Shipping $oldShipping */
            $ship = new Shipping();
            $ship->copyProperties($oldShipping, ['id', 'Order', 'sort_no', 'OrderItems']);
            $ship->setOrder($newOrder);
            $ship->setSortNo($oldShipping->getSortNo());
            $this->entityManager->persist($ship);

            foreach ($oldShipping->getOrderItems() as $line) {
                /** @var OrderItem $line */
                $ni = new OrderItem();
                $ni->copyProperties($line, ['id', 'Order', 'Shipping']);
                $ni->setOrder($newOrder);
                $ni->setShipping($ship);
                $newOrder->addOrderItem($ni);
                $ship->addOrderItem($ni);
                $this->entityManager->persist($ni);
            }
            $newOrder->addShipping($ship);
        }

        $this->entityManager->flush();
        $this->entityManager->refresh($newOrder);

        return $newOrder;
    }

    private function createPreOrderId(): string
    {
        do {
            $preOrderId = sha1(StringUtil::random(32));
        } while ($this->orderRepository->findOneBy(['pre_order_id' => $preOrderId]));

        return $preOrderId;
    }
}
