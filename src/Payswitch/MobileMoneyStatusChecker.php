<?php

namespace Prinx\Payswitch;

use Exception;

class MobileMoneyStatusChecker
{
    const MOMO_RESPONSE_CHECK_ENDPOINT = 'https://[env].theteller.net/v1.1/users/transactions/[transaction_id]/status';

    /**
     * @var \Prinx\Payswitch\MobileMoneyStatusCheckerResponse
     */
    protected $callbackHandler = null;

    protected $curlError = null;

    /**
     * Check mobile money request response.
     *
     * @param string|array $ids
     *
     * @return \Prinx\Payswitch\MobileMoneyStatusCheckerResponse
     */
    public function check($ids)
    {
        if (!$ids || (!is_array($ids) && !is_string($ids))) {
            throw new Exception('Transaction ids passed to the check method must be an array or a string. Got '.gettype($ids));
        }

        if (is_string($ids)) {
            $ids = [$ids];
        }

        $handles = [];
        $multiHandle = curl_multi_init();
        $curlOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => [
                'Cache-Control: no-cache',
                'Content-Type: application/json',
                'Merchant-Id: '.env('PAYSWITCH_MOMO_API_MERCHANT_ID'),
            ],
        ];

        foreach ($ids as $transactionId) {
            if (!$transactionId) {
                continue;
            }

            $url = $this->formatUrl($transactionId);

            $handles[$transactionId] = curl_init($url);
            curl_setopt_array($handles[$transactionId], $curlOptions);

            curl_multi_add_handle($multiHandle, $handles[$transactionId]);
        }

        $stillRunning = null;

        do {
            curl_multi_exec($multiHandle, $stillRunning);
            curl_multi_select($multiHandle);
        } while ($stillRunning);

        $errno = curl_multi_errno($multiHandle);
        $error = $errno === CURLM_OK ? null : curl_multi_strerror($errno);

        if ($error) {
            $responses = [
                'success' => false,
                'error'   => $error,
            ];
        } else {
            $responses = ['success' => true];
        }

        foreach ($handles as $transactionId => $curlHandle) {
            if (!$error) {
                $response = curl_multi_getcontent($curlHandle);
                $error = curl_error($curlHandle);
                $responses['data'][$transactionId] = new MobileMoneyStatus(
                    $response,
                    $error,
                    $transactionId
                );
            }

            curl_multi_remove_handle($multiHandle, $curlHandle);
        }

        curl_multi_close($multiHandle);

        return new MobileMoneyStatusCheckerResponse($responses);
    }

    public function formatUrl($transactionId)
    {
        $url = str_replace('[env]', env('PAYSWITCH_MOMO_API_ENV', 'prod'), self::MOMO_RESPONSE_CHECK_ENDPOINT);
        $url = str_replace('[transaction_id]', $transactionId, $url);

        return $url;
    }
}
