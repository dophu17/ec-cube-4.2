<?php

namespace Customize\Service\Gmo;

use Eccube\Entity\Order;

class GmoApiClient
{
    private string $apiBaseUrl;
    private string $shopId;
    private string $shopPass;
    private bool $mockMode;
    private string $jobCd;
    private string $testCardNo;
    private string $testCardExpire;
    private string $testCardSecurityCode;

    public function __construct(
        string $apiBaseUrl,
        string $shopId,
        string $shopPass,
        bool $mockMode,
        string $jobCd,
        string $testCardNo,
        string $testCardExpire,
        string $testCardSecurityCode
    ) {
        $this->apiBaseUrl = rtrim($apiBaseUrl, '/');
        $this->shopId = $shopId;
        $this->shopPass = $shopPass;
        $this->mockMode = $mockMode;
        $this->jobCd = $jobCd;
        $this->testCardNo = $testCardNo;
        $this->testCardExpire = $testCardExpire;
        $this->testCardSecurityCode = $testCardSecurityCode;
    }

    public function isMockMode(): bool
    {
        return $this->mockMode;
    }

    /**
     * @return array<string, string>
     */
    public function payWithTestCard(Order $Order): array
    {
        if ($this->mockMode) {
            return [
                'OrderID' => $this->createOrderId($Order),
                'TranID' => 'MOCK-'.(string) $Order->getId(),
                'Approve' => 'MOCK-APPROVED',
                'Status' => 'CAPTURE',
            ];
        }

        $orderId = $this->createOrderId($Order);
        $entry = $this->entryTran($orderId, (int) $Order->getPaymentTotal());

        return $this->execTran($orderId, $entry['AccessID'], $entry['AccessPass']);
    }

    /**
     * @return array<string, string>
     */
    public function payWithToken(Order $Order, string $token): array
    {
        $orderId = $this->createOrderId($Order);
        $entry = $this->entryTran($orderId, (int) $Order->getPaymentTotal());

        return $this->execTranWithToken($orderId, $entry['AccessID'], $entry['AccessPass'], $token);
    }

    /**
     * @return array<string, string>
     */
    public function payWithCard(Order $Order, string $cardNo, string $expire, string $securityCode): array
    {
        $orderId = $this->createOrderId($Order);
        $entry = $this->entryTran($orderId, (int) $Order->getPaymentTotal());

        return $this->request('ExecTran', [
            'ShopID' => $this->shopId,
            'ShopPass' => $this->shopPass,
            'OrderID' => $orderId,
            'AccessID' => $entry['AccessID'],
            'AccessPass' => $entry['AccessPass'],
            'Method' => '1',
            'PayTimes' => '1',
            'CardNo' => $cardNo,
            'Expire' => $expire,
            'SecurityCode' => $securityCode,
        ]);
    }

    private function createOrderId(Order $Order): string
    {
        return sprintf('EC%06d', (int) $Order->getId());
    }

    /**
     * @return array<string, string>
     */
    private function entryTran(string $orderId, int $amount): array
    {
        return $this->request('EntryTran', [
            'ShopID' => $this->shopId,
            'ShopPass' => $this->shopPass,
            'OrderID' => $orderId,
            'JobCd' => $this->jobCd,
            'Amount' => (string) $amount,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function execTran(string $orderId, string $accessId, string $accessPass): array
    {
        return $this->request('ExecTran', [
            'ShopID' => $this->shopId,
            'ShopPass' => $this->shopPass,
            'OrderID' => $orderId,
            'AccessID' => $accessId,
            'AccessPass' => $accessPass,
            'Method' => '1',
            'PayTimes' => '1',
            'CardNo' => $this->testCardNo,
            'Expire' => $this->testCardExpire,
            'SecurityCode' => $this->testCardSecurityCode,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function execTranWithToken(string $orderId, string $accessId, string $accessPass, string $token): array
    {
        return $this->request('ExecTran', [
            'ShopID' => $this->shopId,
            'ShopPass' => $this->shopPass,
            'OrderID' => $orderId,
            'AccessID' => $accessId,
            'AccessPass' => $accessPass,
            'Method' => '1',
            'PayTimes' => '1',
            'TokenType' => '1',
            'Token' => $token,
        ]);
    }

    /**
     * @param array<string, string> $params
     *
     * @return array<string, string>
     */
    private function request(string $endpoint, array $params): array
    {
        $url = sprintf('%s/%s.idPass', $this->apiBaseUrl, $endpoint);
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('Failed to initialize curl.');
        }
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params, '', '&'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        $raw = curl_exec($ch);
        if ($raw === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('GMO request failed: '.$error);
        }
        curl_close($ch);
        $raw = trim($raw);
        parse_str($raw, $parsed);
        $parsed = array_map(static function ($value) {
            return is_scalar($value) ? (string) $value : '';
        }, $parsed);

        if (!empty($parsed['ErrCode']) || !empty($parsed['ErrInfo'])) {
            $message = sprintf(
                'GMO API error endpoint=%s ErrCode=%s ErrInfo=%s',
                $endpoint,
                $parsed['ErrCode'] ?? '',
                $parsed['ErrInfo'] ?? ''
            );
            throw new \RuntimeException($message);
        }

        return $parsed;
    }
}
