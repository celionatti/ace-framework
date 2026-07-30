<?php

namespace Ace;

use Exception;

class PaystackService
{
    private string $secretKey;
    private string $publicKey;
    private string $baseUrl = 'https://api.paystack.co';

    public function __construct()
    {
        $this->secretKey = $_ENV['PAYSTACK_SECRET_KEY'] ?? '';
        $this->publicKey = $_ENV['PAYSTACK_PUBLIC_KEY'] ?? '';

        if (empty($this->secretKey)) {
            // Log warning or throw exception if secret key is empty in production
            if (($_ENV['APP_ENV'] ?? 'development') === 'production') {
                throw new Exception("Paystack Secret Key is missing in .env config", 500);
            }
        }
    }

    /**
     * Check if Paystack secret key is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->secretKey);
    }

    /**
     * Initialize a Paystack transaction
     * 
     * @param string $email Customer email address
     * @param float $amount Amount in local currency (Naira, GHS, etc.) - will be converted to kobo
     * @param string $callbackUrl URL to redirect to after successful payment
     * @param string|null $reference Optional unique transaction reference
     * @return array Contains 'authorization_url', 'access_code', and 'reference'
     */
    public function initializeTransaction(string $email, float $amount, string $callbackUrl, ?string $reference = null): array
    {
        $url = $this->baseUrl . "/transaction/initialize";

        // Paystack expects amount in Kobo (or minor units)
        $amountInKobo = (int)round($amount * 100);

        if (!$reference) {
            $reference = 'PAY_' . uniqid() . '_' . time();
        }

        $fields = [
            'email' => $email,
            'amount' => $amountInKobo,
            'callback_url' => $callbackUrl,
            'reference' => $reference
        ];

        $response = $this->makeRequest('POST', $url, $fields);

        if (isset($response['status']) && $response['status'] === true) {
            return $response['data'];
        }

        throw new Exception("Paystack Initialization Failed: " . ($response['message'] ?? 'Unknown Error'), 400);
    }

    /**
     * Verify a Paystack transaction reference
     */
    public function verifyTransaction(string $reference): array
    {
        $url = $this->baseUrl . "/transaction/verify/" . rawurlencode($reference);
        $response = $this->makeRequest('GET', $url);

        if (isset($response['status']) && $response['status'] === true) {
            return $response['data'];
        }

        throw new Exception("Paystack Verification Failed: " . ($response['message'] ?? 'Unknown Error'), 400);
    }

    /**
     * Initiate a refund for a transaction via Paystack Refund API
     */
    public function refundTransaction(string $reference, ?float $amount = null, string $merchantNote = 'Host refund request'): array
    {
        $url = $this->baseUrl . "/refund";
        $fields = [
            'transaction' => $reference,
            'merchant_note' => $merchantNote
        ];

        if ($amount !== null && $amount > 0) {
            $fields['amount'] = (int)round($amount * 100);
        }

        $response = $this->makeRequest('POST', $url, $fields);
        if (isset($response['status']) && $response['status'] === true) {
            return $response['data'];
        }

        throw new Exception("Paystack Refund Error: " . ($response['message'] ?? 'Unknown Paystack error'), 400);
    }

    /**
     * Validate a Paystack Webhook Signature to ensure request authenticity
     */
    public function validateWebhook(string $payload, string $signatureHeader): bool
    {
        $calculatedSignature = hash_hmac('sha512', $payload, $this->secretKey);
        return hash_equals($calculatedSignature, $signatureHeader);
    }

    /**
     * Get a standardized list of Paystack supported banks, microfinance banks, and fintechs.
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
     * Fetch list of supported banks from Paystack.
     */
    public function getBanks(string $country = 'nigeria'): array
    {
        $url = $this->baseUrl . "/bank?country=" . rawurlencode($country);
        $response = $this->makeRequest('GET', $url);

        if (isset($response['status']) && $response['status'] === true) {
            return $response['data'] ?? [];
        }

        return [];
    }

    /**
     * Resolve a bank name or routing code into a Paystack NUBAN bank code.
     */
    public function resolveBankCode(string $bankNameOrCode): string
    {
        $clean = trim($bankNameOrCode);
        
        // If numeric and 3-6 digits, assume it's already a bank code
        if (preg_match('/^\d{3,6}$/', $clean)) {
            return $clean;
        }

        // Fast static map for common Nigerian banks
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

        // Search live Paystack banks list if static match not found
        try {
            $banks = $this->getBanks('nigeria');
            foreach ($banks as $b) {
                if (stripos($b['name'], $clean) !== false || stripos($clean, $b['name']) !== false) {
                    return $b['code'];
                }
            }
        } catch (\Throwable $e) {
            // Ignore API fetch error and fall back
        }

        // Fallback default (Access Bank '044' or 058) if unresolvable
        return '058';
    }

    /**
     * Create a Transfer Recipient on Paystack.
     * 
     * @param string $name Account Holder Name
     * @param string $accountNumber 10-digit NUBAN Account Number
     * @param string $bankCode Paystack Bank Code (e.g. '058')
     * @param string $currency Local currency code (default 'NGN')
     * @return array Paystack recipient data containing 'recipient_code'
     */
    public function createTransferRecipient(string $name, string $accountNumber, string $bankCode, string $currency = 'NGN'): array
    {
        $url = $this->baseUrl . "/transferrecipient";

        $fields = [
            'type' => 'nuban',
            'name' => $name,
            'account_number' => $accountNumber,
            'bank_code' => $bankCode,
            'currency' => $currency
        ];

        $response = $this->makeRequest('POST', $url, $fields);

        if (isset($response['status']) && $response['status'] === true) {
            return $response['data'];
        }

        throw new Exception("Paystack Transfer Recipient Creation Failed: " . ($response['message'] ?? 'Unknown Error'), 400);
    }

    /**
     * Initiate a Direct Bank Transfer / Payout.
     * 
     * @param float $amount Amount in local currency (Naira)
     * @param string $recipientCode Paystack Recipient Code (e.g., 'RCP_xxxx')
     * @param string $reason Brief note describing payout
     * @param string|null $reference Optional unique payout reference
     * @return array Paystack transfer response data
     */
    public function initiateTransfer(float $amount, string $recipientCode, string $reason = 'Evently Host Payout', ?string $reference = null): array
    {
        $url = $this->baseUrl . "/transfer";

        $amountInKobo = (int)round($amount * 100);

        if (!$reference) {
            $reference = 'TRF_' . uniqid() . '_' . time();
        }

        $fields = [
            'source' => 'balance',
            'amount' => $amountInKobo,
            'recipient' => $recipientCode,
            'reason' => $reason,
            'reference' => $reference
        ];

        $response = $this->makeRequest('POST', $url, $fields);

        if (isset($response['status']) && $response['status'] === true) {
            return $response['data'];
        }

        throw new Exception("Paystack Transfer Failed: " . ($response['message'] ?? 'Unknown Error'), 400);
    }

    /**
     * Verify status of a Paystack Transfer.
     */
    public function verifyTransfer(string $transferCodeOrRef): array
    {
        $url = $this->baseUrl . "/transfer/" . rawurlencode($transferCodeOrRef);
        $response = $this->makeRequest('GET', $url);

        if (isset($response['status']) && $response['status'] === true) {
            return $response['data'];
        }

        throw new Exception("Paystack Transfer Verification Failed: " . ($response['message'] ?? 'Unknown Error'), 400);
    }

    /**
     * Helper to perform HTTP request via cURL
     */
    private function makeRequest(string $method, string $url, array $fields = []): array
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);

        // Environment-aware SSL verification for local XAMPP/Windows compatibility
        $verifySsl = (($_ENV['APP_ENV'] ?? 'development') === 'production');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifySsl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verifySsl ? 2 : 0);
        
        $headers = [
            "Authorization: Bearer " . trim($this->secretKey),
            "Cache-Control: no-cache",
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
            throw new Exception("Invalid response received from payment gateway.", 500);
        }

        return $decoded;
    }
}
