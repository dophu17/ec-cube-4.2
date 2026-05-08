<?php

namespace Customize\Service\Subscription;

use Customize\Entity\Subscription;
use Eccube\Entity\Order;
use Eccube\Repository\BaseInfoRepository;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class SubscriptionMailNotifier
{
    private MailerInterface $mailer;
    private BaseInfoRepository $baseInfoRepository;
    private string $fromEmail;
    private string $fromName;

    public function __construct(
        MailerInterface $mailer,
        BaseInfoRepository $baseInfoRepository,
        string $fromEmail,
        string $fromName
    ) {
        $this->mailer = $mailer;
        $this->baseInfoRepository = $baseInfoRepository;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
    }

    public function notifyRenewalSuccess(Subscription $subscription, Order $order): void
    {
        $customer = $subscription->getCustomer();
        if (!$customer || !$customer->getEmail()) {
            return;
        }

        $this->send(
            (string) $customer->getEmail(),
            '[Balocco] Gia hạn subscription thành công',
            implode("\n", [
                'Xin chào '.$customer->getName01().' '.$customer->getName02().',',
                '',
                'Subscription #'.$subscription->getId().' đã được gia hạn thành công.',
                'Mã đơn mới: #'.$order->getId(),
                'Số tiền: '.(string) $order->getPaymentTotal(),
                'Ngày fulfillment dự kiến: '.$subscription->getNextFulfillmentAt()->format('Y-m-d H:i:s').' UTC',
                '',
                'Cảm ơn bạn đã sử dụng dịch vụ của Balocco.',
            ])
        );
    }

    public function notifyRenewalFailed(Subscription $subscription, string $errorMessage, bool $finalFailure): void
    {
        $customer = $subscription->getCustomer();
        if (!$customer || !$customer->getEmail()) {
            return;
        }

        $subject = $finalFailure
            ? '[Balocco] Subscription tạm dừng do thanh toán thất bại'
            : '[Balocco] Gia hạn subscription thất bại';

        $body = [
            'Xin chào '.$customer->getName01().' '.$customer->getName02().',',
            '',
            'Subscription #'.$subscription->getId().' chưa thể gia hạn.',
            'Lý do: '.$errorMessage,
        ];

        if ($finalFailure) {
            $body[] = 'Subscription hiện đang ở trạng thái tạm dừng do đã vượt quá số lần thử lại.';
        } else {
            $body[] = 'Hệ thống sẽ tự động thử lại theo lịch 1/3/7 ngày.';
        }

        $body[] = '';
        $body[] = 'Vui lòng kiểm tra phương thức thanh toán hoặc liên hệ Balocco để được hỗ trợ.';

        $this->send((string) $customer->getEmail(), $subject, implode("\n", $body));
    }

    private function send(string $to, string $subject, string $body): void
    {
        $baseInfo = $this->baseInfoRepository->get();
        $fromEmail = '' !== trim($this->fromEmail) ? $this->fromEmail : (string) $baseInfo->getEmail01();
        $fromName = '' !== trim($this->fromName) ? $this->fromName : (string) $baseInfo->getShopName();

        $message = (new Email())
            ->from(new Address($fromEmail, $fromName))
            ->to($to)
            ->subject($subject)
            ->text($body);

        try {
            $this->mailer->send($message);
        } catch (TransportExceptionInterface $e) {
            log_error('[subscription] gửi email thất bại: '.$e->getMessage());
        }
    }
}
