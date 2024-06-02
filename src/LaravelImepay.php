<?php

namespace Dipesh79\LaravelImePay;

use Dipesh79\LaravelImePay\Exception\ImePayException;
use Dipesh79\LaravelImePay\Exception\InvalidKeyException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Class LaravelImepay
 *
 * This class provides methods to interact with the IMEPay API.
 * It includes methods to generate a token, generate a checkout URL, handle callback responses, confirm payments, and recheck payment status.
 */
class LaravelImepay
{
    public string $apiUser;
    public string $apiPassword;
    public string $module;
    public string $merchantCode;
    public string $environment;
    public string $method;
    public string $callbackUrl;
    public string $cancelUrl;
    public string $baseUrl;

    /**
     * LaravelImepay constructor.
     *
     * Initializes the class with configuration values from the application's environment.
     *
     * @throws InvalidKeyException if any of the required configuration values are missing or invalid.
     */
    public function __construct()
    {
        $this->apiUser = config('imepay.apiUser');
        $this->apiPassword = config('imepay.apiPassword');
        $this->module = config('imepay.module');
        $this->merchantCode = config('imepay.merchantCode');
        $this->environment = config('imepay.env');
        $this->callbackUrl = config('imepay.callbackUrl');
        $this->cancelUrl = config('imepay.cancelUrl');
        $this->method = config('imepay.callbackMethod');
        $this->checkEnvData();
        if (strtolower($this->environment) == 'live') {
            $this->baseUrl = '';
        } else {
            $this->baseUrl = 'https://stg.imepay.com.np:7979/api/';
        }

    }

    /**
     * Checks the environment data before proceeding.
     *
     * @throws InvalidKeyException if any of the required configuration values are missing or invalid.
     */
    public function checkEnvData(): void
    {
        if (empty($this->apiUser)) {
            throw new InvalidKeyException('IMEPAY API User is missing');
        }
        if (empty($this->apiPassword)) {
            throw new InvalidKeyException('IMEPAY API Password is missing');
        }
        if (empty($this->module)) {
            throw new InvalidKeyException('IMEPAY Module is missing');
        }
        if (empty($this->merchantCode)) {
            throw new InvalidKeyException('IMEPAY Merchant Code is missing');
        }
        if (empty($this->environment)) {
            throw new InvalidKeyException('IMEPAY Environment is missing');
        }
        if (empty($this->callbackUrl)) {
            throw new InvalidKeyException('IMEPAY Callback URL is missing');
        }
        if (empty($this->cancelUrl)) {
            throw new InvalidKeyException('IMEPAY Cancel URL is missing');
        }
        if (empty($this->method)) {
            throw new InvalidKeyException('IMEPAY Callback Method is missing');
        }
        if (!in_array(strtolower($this->environment), ['live', 'sandbox'])) {
            throw new InvalidKeyException('IMEPAY Environment should be either Live or Sandbox');
        }
    }

    /**
     * Generates a token for IMEPay payment.
     *
     * @param double $amount - The amount for the payment.
     * @param string $refId - The reference ID for the payment.
     *
     * @return string The generated token.
     *
     * @throws ConnectionException if there is a problem with the HTTP connection.
     * @throws ImePayException if there is an error while generating the token.
     */
    public function generateToken(float $amount, string $refId): string
    {
        if (strtolower($this->environment) == 'live') {
            $url = '';
        } else {
            $url = $this->baseUrl . 'Web/GetToken';
        }

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode($this->apiUser . ':' . $this->apiPassword),
            'Module' => base64_encode($this->module),
        ])->post($url, [
            'MerchantCode' => $this->merchantCode,
            'Amount' => $amount,
            'RefId' => $refId,
        ]);

        $data = $response->json();

        if ($data) {
            return $data['TokenId'];
        } else {
            throw new ImePayException('Error while generating token. Please check the credentials.');
        }
    }

    /**
     * Generates a checkout URL.
     *
     * @param string $token  The token for the payment.
     * @param string $refId  The reference ID for the payment.
     * @param double $amount  The amount for the payment.
     *
     * @return string The generated checkout URL.
     */
    public function generateCheckoutUrl(string $token, string $refId, float $amount): string
    {
        if (strtolower($this->environment) == 'live') {
            $url = '';
        } else {
            $url = 'https://stg.imepay.com.np:7979/WebCheckout/Checkout';
        }

        $payload = $token . '|' . $this->merchantCode . '|' . $refId . '|' . $amount . '|' . $this->method . '|' . $this->callbackUrl . '|' . $this->cancelUrl;

        $data = base64_encode($payload);

        return $url . '?data=' . $data;
    }

    /**
     * Handles the callback response from IMEPay.
     *
     * @param Request $request The HTTP request containing the callback data.
     *
     * @return array|Request The decoded callback data.
     */
    public function callbackResponse(Request $request): array|Request
    {
        if ($this->method == 'GET') {
            $decoded = base64_decode($request->data);
            $split = explode('|', $decoded);
            $responseCode = $split[0];
            $responseDescription = $split[1];
            $msisdn = $split[2];
            $transactionId = $split[3];
            $refId = $split[4];
            $transactionAmount = $split[5];
            $tokenId = $split[6];
            return [
                'ResponseCode' => $responseCode,
                'ResponseDescription' => $responseDescription,
                'RefId' => $refId,
                'TranAmount' => $transactionAmount,
                'Msisdn' => $msisdn,
                'TransactionId' => $transactionId,
                'TokenId' => $tokenId,
            ];
        } else {
            return $request->all();
        }

    }

    /**
     * Confirms a payment using the reference ID, token ID, transaction ID, and MSISDN.
     *
     * @param string $refId The reference ID for the payment.
     * @param string $tokenId The token ID for the payment.
     * @param string $transactionId The transaction ID for the payment.
     * @param string $msisdn The MSISDN for the payment.
     *
     * @return array The response from the IMEPay API.
     *
     * @throws ConnectionException if there is a problem with the HTTP connection.
     */
    public function confirmPayment(string $refId, string $tokenId, string $transactionId, string $msisdn): array
    {
        if (strtolower($this->environment) == 'live') {
            $url = '';
        } else {
            $url = $this->baseUrl . 'Web/Confirm';
        }

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode($this->apiUser . ':' . $this->apiPassword),
            'Module' => base64_encode($this->module),
        ])->post($url, [
            'MerchantCode' => $this->merchantCode,
            'RefId' => $refId,
            'TokenId' => $tokenId,
            'TransactionId' => $transactionId,
            'Msisdn' => $msisdn,
        ]);

        return $response->json();

    }

    /**
     * Rechecks the status of a payment using the reference ID and token ID.
     *
     * @param string $refId The reference ID for the payment.
     * @param string $tokenId The token ID for the payment.
     *
     * @return array The response from the IMEPay API.
     *
     * @throws ConnectionException if there is a problem with the HTTP connection.
     */
    public function recheckPayment(string $refId, string $tokenId): array
    {
        if (strtolower($this->environment) == 'live') {
            $url = '';
        } else {
            $url = $this->baseUrl . 'Web/Recheck';
        }

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode($this->apiUser . ':' . $this->apiPassword),
            'Module' => base64_encode($this->module),
        ])->post($url, [
            'MerchantCode' => $this->merchantCode,
            'RefId' => $refId,
            'TokenId' => $tokenId
        ]);

        return $response->json();
    }


}
