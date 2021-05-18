<?php

namespace Prinx\Payswitch\Contracts;

interface MobileMoneyInterface
{
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
     * @return MobileMoneyStatusInterface
     */
    public function pay(
        $amount,
        $msisdn,
        $network,
        $voucherCode = null,
        $curlOptions = []
    ): MobileMoneyStatusInterface;
}
