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
     * The voucher code is only for some vodafone users. Pass null for other
     * networks. (Note that this can change in the future.)
     *
     * @param string|int|float $amount      The amount the user is paying (in cedi)
     * @param string           $msisdn      The number of the user
     * @param string           $network     (MTN|VODAFONE|AIRTEL|TIGO)
     * @param string|int|null  $voucherCode
     * @param array            $options     Curl options to pass to the request
     *
     * @return MobileMoneyResponseInterface
     */
    public static function pay(
        $amount,
        $msisdn,
        $network,
        $voucherCode = null,
        $options = []
    ): MobileMoneyResponseInterface {
        if (isset(self::$networks[$network])) {
            $network = self::$networks[$network];
            $data = self::prepareData($msisdn, $network, $amount, $voucherCode);
            $defaultOptions = array_merge($options, ['params' => $data]);

            $ch = curl_init();
            curl_setopt_array($ch, self::curlOptions($defaultOptions));
            $response = curl_exec($ch);
            $err = curl_error($ch);

            curl_close($ch);
        } else {
            $err = 'Unsupported network "'.$network.'"';
            $data = [];
            $response = null;
        }

        return new MobileMoneyResponse($response, $err, $data);
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
    private static function prepareData($msisdn, $network, $amount, $voucherCode)
    {
        $data = [
            'r-switch' => $network,
            'subscriber_number' => trim($msisdn, '+'),
            'transaction_id' => self::transactionId(),
            'amount' => self::convertAmount($amount),
            'processing_code' => self::credentials('processing_code'),
            'merchant_id' => self::credentials('merchant_id'),
            'desc' => self::credentials('desc'),
        ];

        if ($network === self::VODAFONE_ABBR) {
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
    private static function curlOptions($defaultOptions)
    {
        return [
            CURLOPT_URL => self::credentials('endpoint'),
            CURLOPT_RETURNTRANSFER => $defaultOptions['return-transfer'] ?? true,
            CURLOPT_ENCODING => $defaultOptions['encoding'] ?? 'UTF-8',
            CURLOPT_MAXREDIRS => $defaultOptions['maxredirs'] ?? 10,
            CURLOPT_CONNECTTIMEOUT => $defaultOptions['connect-timeout'] ?? 60,
            CURLOPT_TIMEOUT => $defaultOptions['timeout'] ?? 120,
            CURLOPT_HTTP_VERSION => $defaultOptions['version'] ?? CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $defaultOptions['method'] ?? 'POST',
            CURLOPT_POSTFIELDS => json_encode($defaultOptions['params'] ?? []),
            CURLOPT_HTTPHEADER => self::headers(),
        ];
    }

    /**
     * Headers to send with the request.
     *
     * @return array
     */
    private static function headers()
    {
        return [
            'Cache-Control: no-cache',
            'Content-Type: application/json',
            'Authorization: Basic '.base64_encode(self::credentials('token')),
        ];
    }

    /**
     * Convert amount to format required by payswitch.
     *
     * @param int|float|string $amount Must be in Cedi
     *
     * @return string
     */
    public static function convertAmount($amount)
    {
        $amount = (float) $amount;

        return str_pad($amount * 100, 12, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new random tansaction ID.
     *
     * @return string
     */
    public static function transactionId()
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
    public static function credentials($name = '')
    {
        $data = [
            'endpoint' => env('PAYSWITCH_MOMO_API_ENDPOINT'),
            'token' => env('PAYSWITCH_MOMO_API_TOKEN'),
            'processing_code' => env('PAYSWITCH_MOMO_API_PROCESSING_CODE'),
            'desc' => env('PAYSWITCH_MOMO_API_DESCRIPTION'),
            'merchant_id' => env('PAYSWITCH_MOMO_API_MERCHANT_ID'),
        ];

        return $name ? $data[$name] : $data;
    }
}
