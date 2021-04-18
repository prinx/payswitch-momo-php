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
     * The voucher code is only for some vodafone users. Pass null for other
     * networks. (Note that this can change in the future.)
     *
     * @param string           $msisdn       The number of the user
     * @param string           $network      The mnc of the network
     * @param string|int|float $amount       The amount the user is paying
     * @param string|int|null  $voucher_code
     * @param array            $options      Curl options to pass to the request
     *
     * @return \StdClass|array Response
     */
    public static function pay(
        $msisdn,
        $network,
        $amount,
        $voucher_code = null,
        $options = []
    ): MobileMoneyResponseInterface;
}
