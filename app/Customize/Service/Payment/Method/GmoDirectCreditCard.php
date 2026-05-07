<?php

namespace Customize\Service\Payment\Method;

use Customize\Service\Gmo\GmoApiClient;
use Eccube\Entity\Order;
use Eccube\Service\Payment\Method\CreditCard;
use Eccube\Service\Payment\PaymentResult;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Symfony\Component\Form\FormInterface;

class GmoDirectCreditCard extends CreditCard
{
    private PurchaseFlow $purchaseFlow;
    private GmoApiClient $gmoApiClient;
    private FormInterface $form;

    public function __construct(PurchaseFlow $shoppingPurchaseFlow, GmoApiClient $gmoApiClient)
    {
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->gmoApiClient = $gmoApiClient;
    }

    public function verify()
    {
        return false;
    }

    public function apply()
    {
        $this->purchaseFlow->prepare($this->Order, new PurchaseContext());

        return false;
    }

    public function checkout()
    {
        $result = new PaymentResult();

        try {
            $cardNo = preg_replace('/\D+/', '', (string) $this->form->get('gmo_card_no')->getData());
            $expire = preg_replace('/\D+/', '', (string) $this->form->get('gmo_card_expire')->getData());
            $securityCode = preg_replace('/\D+/', '', (string) $this->form->get('gmo_card_security_code')->getData());

            if ('' === trim((string) $cardNo) || '' === trim((string) $expire) || '' === trim((string) $securityCode)) {
                throw new \RuntimeException('Card input is incomplete.');
            }
            $response = $this->gmoApiClient->payWithCard($this->Order, (string) $cardNo, (string) $expire, (string) $securityCode);
            $this->Order->setPaymentDate(new \DateTime());
            $this->Order->setMessage($this->buildOrderMessage($this->Order->getMessage(), $response));

            $this->purchaseFlow->commit($this->Order, new PurchaseContext());
            $result->setSuccess(true);
        } catch (\Throwable $e) {
            log_error('[GMO Direct] checkout failed.', ['error' => $e->getMessage(), 'order_id' => $this->Order->getId()]);
            $result->setSuccess(false);
            $result->setErrors(['GMO決済に失敗しました。管理者へお問い合わせください。']);
        }

        return $result;
    }

    public function setFormType(FormInterface $form)
    {
        $this->form = $form;

        return $this;
    }

    private function buildOrderMessage(?string $current, array $response): string
    {
        $lines = [];
        if (!empty($current)) {
            $lines[] = trim($current);
        }
        $lines[] = '[GMO Direct] '.($this->gmoApiClient->isMockMode() ? 'MOCK MODE' : 'LIVE TEST MODE');
        if (!empty($response['TranID'])) {
            $lines[] = 'TranID: '.$response['TranID'];
        }
        if (!empty($response['Approve'])) {
            $lines[] = 'Approve: '.$response['Approve'];
        }

        return implode("\n", $lines);
    }
}
