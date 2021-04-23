<?php

namespace Prinx\Payswitch;

use Exception;

class MobileMoneyResponseCheck
{
    const MOMO_RESPONSE_CHECK_ENDPOINT = 'https://[env].theteller.net/v1.1/users/transactions/[transaction_id]/status';

    /**
     * @var MobileMoneyResponseCheckCallbackHandler
     */
    protected $callbackHandler = null;

    protected $curlError = null;

    /**
     * Check mobile money request response.
     *
     * @param string|array $ids
     *
     * @return MobileMoneyResponseCheckCallbackHandler
     */
    public function check($ids)
    {
        if (!$ids || (!is_array($ids) && !is_string($ids))) {
            throw new Exception('$ids parameter must be an array or a string.');
        }

        $handles = [];
        $multiHandle = curl_multi_init();
        $curlOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Cache-Control: no-cache',
                'Content-Type: application/json',
                'Merchant-Id: '.env('PAYSWITCH_MOMO_API_MERCHANT_ID'),
            ],
        ];

        foreach ($ids as $transactionId) {
            if (!$transactionId) {
                continue;
            }

            $url = str_replace('[env]', env('PAYSWITCH_MOMO_API_ENV', 'prod'), self::MOMO_RESPONSE_CHECK_ENDPOINT);
            $url = str_replace('[transction_id]', $transactionId, $url);

            $handles[$transactionId] = curl_init($url);
            curl_setopt_array($handles[$transactionId], $curlOptions);

            curl_multi_add_handle($multiHandle, $handles[$transactionId]);
        }

        $stillRunning = null;

        do {
            $mrc = curl_multi_exec($multiHandle, $stillRunning);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($stillRunning && $mrc == CURLM_OK) {
            // Wait for activity on any curl connection
            if (curl_multi_select($multiHandle) === -1) {
                usleep(100);
            }

            while (curl_multi_exec($multiHandle, $stillRunning) == CURLM_CALL_MULTI_PERFORM);
        }

        $errno = curl_multi_errno($multiHandle);
        $error = $errno === CURLM_OK ? null : curl_multi_strerror($errno);

        if ($error) {
            $responses = [
                'success' => false,
                'error' => $error,
            ];
        } else {
            $responses = ['success' => true];
        }

        foreach ($handles as $transactionId => $curlHandle) {
            if (!$error) {
                $response = curl_multi_getcontent($curlHandle);
                $error = curl_error($curlHandle);
                $responses['data'][$transactionId] = new MobileMoneyResponse(
                    $response,
                    $error,
                    $transactionId
                );
            }

            curl_multi_remove_handle($multiHandle, $curlHandle);
        }

        curl_multi_close($multiHandle);

        return new MobileMoneyResponseCheckCallbackHandler($responses);
    }
}
