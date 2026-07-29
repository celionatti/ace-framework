<?php

namespace Ace;

use Exception;

class FlutterwaveService
{
    private string $secretKey;
    private string $publicKey;
    private string $baseUrl = 'https://api.flutterwave.com/v3';

    public function __construct()
    {
        $this->secretKey = $_ENV['FLUTTERWAVE_SECRET_KEY'] ?? '';
        $this->publicKey = $_ENV['FLUTTERWAVE_PUBLIC_KEY'] ?? '';

        if (empty($this->secretKey) && ($_ENV['APP_ENV'] ?? 'development') === 'production') {
            throw new Exception("Flutterwave Secret Key is missing in .env config", 500);
        }
    }

    /**
     * Check if Flutterwave secret key is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->secretKey);
    }

    /**
     * Initialize a Flutterwave Standard Payment
     * 
     * @param string $email Customer email address
     * @param float $amount Amount to charge
     * @param string $redirectUrl Redirect URL on completion
     * @param string $reference Local unique transaction reference
     * @return string Redirect checkout URL returned from Flutterwave
     */
    public function initializePayment(string $email, float $amount, string $redirectUrl, string $reference): string
    {
        $url = $this->baseUrl . "/payments";

        $fields = [
            'tx_ref' => $reference,
            'amount' => $amount,
            'currency' => 'NGN', // Default to NGN
            'redirect_url' => $redirectUrl,
            'customer' => [
                'email' => $email,
                'name' => 'Customer (' . $email . ')'
            ],
            'customizations' => [
                'title' => 'Event Ticket Purchase',
                'description' => 'Evently Ticket Checkout'
            ]
        ];

        $response = $this->makeRequest('POST', $url, $fields);

        if (isset($response['status']) && $response['status'] === 'success') {
            return $response['data']['link'];
        }

        throw new Exception("Flutterwave Initialization Failed: " . ($response['message'] ?? 'Unknown Error'), 400);
    }

    /**
     * Verify a Flutterwave Transaction ID
     */
    public function verifyTransaction(string $transactionId): array
    {
        $url = $this->baseUrl . "/transactions/" . rawurlencode($transactionId) . "/verify";
        $response = $this->makeRequest('GET', $url);

        if (isset($response['status']) && $response['status'] === 'success') {
            return $response['data'];
        }

        throw new Exception("Flutterwave Verification Failed: " . ($response['message'] ?? 'Unknown Error'), 400);
    }

    // =========================================================================
    // FLUTTERWAVE TRANSFERS & PAYOUTS API
    // =========================================================================

    /**
     * Get a standardized list of Flutterwave supported banks, microfinance banks, and fintechs.
     */
    public static function getSupportedBanks(): array
    {
        return [
            // Commercial Banks
            ['code' => '044', 'name' => 'Access Bank'],
            ['code' => '063', 'name' => 'Access Bank (Diamond)'],
            ['code' => '050', 'name' => 'Ecobank Nigeria'],
            ['code' => '070', 'name' => 'Fidelity Bank'],
            ['code' => '011', 'name' => 'First Bank of Nigeria'],
            ['code' => '214', 'name' => 'First City Monument Bank (FCMB)'],
            ['code' => '00103', 'name' => 'Globus Bank'],
            ['code' => '058', 'name' => 'Guaranty Trust Bank (GTBank)'],
            ['code' => '030', 'name' => 'Heritage Bank'],
            ['code' => '301', 'name' => 'Jaiz Bank'],
            ['code' => '082', 'name' => 'Keystone Bank'],
            ['code' => '076', 'name' => 'Polaris Bank'],
            ['code' => '101', 'name' => 'Providus Bank'],
            ['code' => '221', 'name' => 'Stanbic IBTC Bank'],
            ['code' => '068', 'name' => 'Standard Chartered Bank'],
            ['code' => '232', 'name' => 'Sterling Bank'],
            ['code' => '100', 'name' => 'Suntrust Bank'],
            ['code' => '000004', 'name' => 'Taj Bank'],
            ['code' => '102', 'name' => 'Titan Trust Bank'],
            ['code' => '032', 'name' => 'Union Bank of Nigeria'],
            ['code' => '033', 'name' => 'United Bank for Africa (UBA)'],
            ['code' => '215', 'name' => 'Unity Bank'],
            ['code' => '035', 'name' => 'Wema Bank'],
            ['code' => '057', 'name' => 'Zenith Bank'],

            // Microfinance Banks & Digital FinTech Banks
            ['code' => '120004', 'name' => 'Airtel Smartcash PSB'],
            ['code' => '565', 'name' => 'Carbon'],
            ['code' => '50162', 'name' => 'Dot Microfinance Bank'],
            ['code' => '51318', 'name' => 'FairMoney Microfinance Bank'],
            ['code' => '51241', 'name' => 'FCMB Microfinance Bank'],
            ['code' => '090567', 'name' => 'Flutterwave MFB'],
            ['code' => '100022', 'name' => 'GoMoney'],
            ['code' => '50211', 'name' => 'Kuda Microfinance Bank'],
            ['code' => '50515', 'name' => 'Moniepoint Microfinance Bank'],
            ['code' => '120003', 'name' => 'MTN MoMo PSB'],
            ['code' => '999992', 'name' => 'OPay Digital Services (OPay)'],
            ['code' => '100002', 'name' => 'Paga'],
            ['code' => '999991', 'name' => 'PalmPay'],
            ['code' => '51457', 'name' => 'Paystack MFB'],
            ['code' => '125', 'name' => 'Rubies MFB'],
            ['code' => '51113', 'name' => 'Safe Haven MFB'],
            ['code' => '51310', 'name' => 'Sparkle Microfinance Bank'],
            ['code' => '566', 'name' => 'VFD Microfinance Bank'],
        ];
    }

    /**
     * Fetch list of supported banks from Flutterwave.
     */
    public function getBanks(string $country = 'NG'): array
    {
        $url = $this->baseUrl . "/banks/" . rawurlencode($country);
        $response = $this->makeRequest('GET', $url);

        if (isset($response['status']) && $response['status'] === 'success') {
            return $response['data'] ?? [];
        }

        return [];
    }

    /**
     * Resolve a bank name into a Flutterwave NUBAN bank code.
     */
    public function resolveBankCode(string $bankNameOrCode): string
    {
        $clean = trim($bankNameOrCode);
        
        if (preg_match('/^\d{3,6}$/', $clean)) {
            return $clean;
        }

        $staticMap = [
            'access' => '044',
            'access bank' => '044',
            'gtb' => '058',
            'gtbank' => '058',
            'guaranty trust bank' => '058',
            'zenith' => '057',
            'zenith bank' => '057',
            'first bank' => '011',
            'firstbank' => '011',
            'uba' => '033',
            'united bank for africa' => '033',
            'fidelity' => '070',
            'fidelity bank' => '070',
            'fcmb' => '214',
            'first city monument bank' => '214',
            'sterling' => '232',
            'sterling bank' => '232',
            'stanbic' => '221',
            'stanbic ibtc' => '221',
            'union' => '032',
            'union bank' => '032',
            'wema' => '035',
            'wema bank' => '035',
            'polaris' => '076',
            'polaris bank' => '076',
            'ecobank' => '050',
            'keystone' => '082',
            'keystone bank' => '082',
            'kuda' => '50211',
            'kuda bank' => '50211',
            'opay' => '999992',
            'moniepoint' => '50515',
            'moniepoint microfinance bank' => '50515',
            'palmpay' => '999991',
            'rubies' => '125',
            'vfd' => '566',
        ];

        $lower = strtolower($clean);
        if (isset($staticMap[$lower])) {
            return $staticMap[$lower];
        }

        try {
            $banks = $this->getBanks('NG');
            foreach ($banks as $b) {
                if (stripos($b['name'], $clean) !== false || stripos($clean, $b['name']) !== false) {
                    return (string)$b['code'];
                }
            }
        } catch (\Throwable $e) {
            // Ignore API fetch error and fall back
        }

        return '058';
    }

    /**
     * Initiate a Direct Bank Transfer / Payout via Flutterwave.
     * 
     * @param float $amount Amount to transfer
     * @param string $accountNumber 10-digit NUBAN Account Number
     * @param string $bankCode Flutterwave Bank Code (e.g. '058')
     * @param string $narration Payout description
     * @param string|null $reference Optional unique reference
     * @param string $currency Currency code (default 'NGN')
     * @return array Flutterwave transfer response data
     */
    public function initiateTransfer(float $amount, string $accountNumber, string $bankCode, string $narration = 'Evently Host Payout', ?string $reference = null, string $currency = 'NGN'): array
    {
        $url = $this->baseUrl . "/transfers";

        if (!$reference) {
            $reference = 'FLW_TRF_' . uniqid() . '_' . time();
        }

        $fields = [
            'account_bank' => $bankCode,
            'account_number' => $accountNumber,
            'amount' => $amount,
            'narration' => $narration,
            'currency' => $currency,
            'reference' => $reference,
            'debit_currency' => $currency
        ];

        $response = $this->makeRequest('POST', $url, $fields);

        if (isset($response['status']) && $response['status'] === 'success') {
            return $response['data'];
        }

        throw new Exception("Flutterwave Transfer Failed: " . ($response['message'] ?? 'Unknown Error'), 400);
    }

    /**
     * Verify status of a Flutterwave Transfer.
     */
    public function verifyTransfer(string $transferId): array
    {
        $url = $this->baseUrl . "/transfers/" . rawurlencode($transferId);
        $response = $this->makeRequest('GET', $url);

        if (isset($response['status']) && $response['status'] === 'success') {
            return $response['data'];
        }

        throw new Exception("Flutterwave Transfer Verification Failed: " . ($response['message'] ?? 'Unknown Error'), 400);
    }

    /**
     * Helper to perform HTTP request via cURL with Bearer Token Authorization
     */
    private function makeRequest(string $method, string $url, array $fields = []): array
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $headers = [
            "Authorization: Bearer " . $this->secretKey,
            "Content-Type: application/json"
        ];

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $result = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL Error: " . $error_msg, 500);
        }

        curl_close($ch);

        $decoded = json_decode($result, true);
        if (!is_array($decoded)) {
            throw new Exception("Invalid response received from Flutterwave API.", 500);
        }

        return $decoded;
    }
}
