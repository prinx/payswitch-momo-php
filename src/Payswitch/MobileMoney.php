<?php

namespace Prinx\Payswitch;

use Prinx\Payswitch\Contracts\MobileMoneyInterface;
use Prinx\Payswitch\Contracts\MobileMoneyResponseInterface;
use function Prinx\Dotenv\env;

class MobileMoney implements MobileMoneyInterface
{
    /**
     * MTN abbreviation code to send to PaySwitch.
     *
     * @var string MTN_ABBR
     */
    const MTN_ABBR = 'MTN';

    /**
     * AIRTEL abbreviation code to send to PaySwitch.
     *
     * @var string AIRTEL_ABBR
     */
    const AIRTEL_ABBR = 'ATL';

    /**
     * VODAFONE abbreviation code to send to PaySwitch.
     *
     * @var string VODAFONE_ABBR
     */
    const VODAFONE_ABBR = 'VDF';

    /**
     * Networks.
     *
     * @var array
     */
    public static $networks = [
        'MTN' => self::MTN_ABBR,
        'VODAFONE' => self::VODAFONE_ABBR,
        'AIRTEL' => self::AIRTEL_ABBR,
        'TIGO' => self::AIRTEL_ABBR,
        'AIRTEL-TIGO' => self::AIRTEL_ABBR,
        'TIGO-AIRTEL' => self::AIRTEL_ABBR,
        'AIRTELTIGO' => self::AIRTEL_ABBR,
        'TIGOAIRTEL' => self::AIRTEL_ABBR,
    ];

    /**
     * Send Payment request to user.
     *
     * The amount should be in cedi. For example 2 GHS will be passed like "2";
     * 10 pesewas will be passed like "0.1", etc.
     *
     * The voucher code is only for vodafone users.
     *
     * @param string|int|float $amount      The amount the user is paying (in cedi)
     * @param string           $msisdn      The number of the user
     * @param string           $network     (MTN|VODAFONE|AIRTEL-TIGO|TIGO-AIRTEL|AIRTELTIGO|TIGOAIRTEL|AIRTEL|TIGO)
     * @param string|int|null  $voucherCode
     * @param array            $curlOptions Additional Curl options to pass to the request
     *
     * @return MobileMoneyResponseInterface
     */
    public function pay(
        $amount,
        $msisdn,
        $network,
        $voucherCode = null,
        $curlOptions = []
    ): MobileMoneyResponseInterface {
        if (isset(self::$networks[$network])) {
            $network = self::$networks[$network];
            $data = $this->prepareData($msisdn, $network, $amount, $voucherCode);
            $defaultOptions = array_merge($curlOptions, ['params' => $data]);

            $ch = curl_init();
            curl_setopt_array($ch, $this->curlOptions($defaultOptions));
            $response = curl_exec($ch);
            $err = curl_error($ch);

            curl_close($ch);
        } else {
            $err = 'Unsupported network "'.$network.'"';
        }

        return new MobileMoneyResponse($response ?? null, $err, $data['transaction_id'] ?? null);
    }

    /**
     * Make data in the format required by PaySwitch.
     *
     * @param string $msisdn
     * @param string $amount
     * @param string $voucherCode
     *
     * @return array
     */
    private function prepareData($msisdn, $network, $amount, $voucherCode)
    {
        $data = [
            'r-switch' => $network,
            'subscriber_number' => trim($msisdn, '+'),
            'transaction_id' => $this->transactionId(),
            'amount' => $this->convertAmount($amount),
            'processing_code' => $this->credentials('processing_code'),
            'merchant_id' => $this->credentials('merchant_id'),
            'desc' => $this->credentials('desc'),
        ];

        if ($network === self::VODAFONE_ABBR && $voucherCode) {
            $data['voucher_code'] = $voucherCode;
        }

        return $data;
    }

    /**
     * Curl options.
     *
     * @param array $defaultOptions
     *
     * @return array
     */
    private function curlOptions($defaultOptions)
    {
        return [
            CURLOPT_URL => $this->credentials('endpoint'),
            CURLOPT_RETURNTRANSFER => $defaultOptions['return-transfer'] ?? true,
            CURLOPT_ENCODING => $defaultOptions['encoding'] ?? 'UTF-8',
            CURLOPT_MAXREDIRS => $defaultOptions['maxredirs'] ?? 10,
            CURLOPT_CONNECTTIMEOUT => $defaultOptions['connect-timeout'] ?? 60,
            CURLOPT_TIMEOUT => $defaultOptions['timeout'] ?? 120,
            CURLOPT_HTTP_VERSION => $defaultOptions['version'] ?? CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $defaultOptions['method'] ?? 'POST',
            CURLOPT_POSTFIELDS => json_encode($defaultOptions['params'] ?? []),
            CURLOPT_HTTPHEADER => $this->headers(),
        ];
    }

    /**
     * Headers to send with the request.
     *
     * @return array
     */
    public function headers()
    {
        return [
            'Cache-Control: no-cache',
            'Content-Type: application/json',
            'Authorization: Basic '.base64_encode($this->credentials('token')),
        ];
    }

    /**
     * Convert amount to format required by payswitch.
     *
     * @param int|float|string $amount Must be in Cedi
     *
     * @return string
     */
    public function convertAmount($amount)
    {
        $amount = (float) $amount;

        return str_pad($amount * 100, 12, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new random tansaction ID.
     *
     * @return string
     */
    public function transactionId()
    {
        return str_pad(rand(1, 999999999), 12, '0', STR_PAD_LEFT);
    }

    /**
     * Returns the credentials the payswitch account.
     *
     * @param string $name
     *
     * @return string|array
     */
    public function credentials($name = '')
    {
        $data = [
            'endpoint' => 'https://'.env('PAYSWITCH_MOMO_API_ENV').'.theteller.net/v1.1/transaction/process',
            'token' => env('PAYSWITCH_MOMO_API_USER').':'.env('PAYSWITCH_MOMO_API_KEY'),
            'processing_code' => env('PAYSWITCH_MOMO_API_PROCESSING_CODE'),
            'desc' => env('PAYSWITCH_MOMO_API_DESCRIPTION'),
            'merchant_id' => env('PAYSWITCH_MOMO_API_MERCHANT_ID'),
        ];

        return $name ? $data[$name] : $data;
    }
}
