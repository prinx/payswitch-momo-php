<?php

namespace Prinx\Payswitch;

class ResponseCode
{
    const TRANSACTION_SUCCESSFULL = '000';

    /**
     * Payment request sent successfully.
     */
    const PENDING = '111';

    const NOT_ENOUGH_BALANCE_1 = '101';

    const NUMBER_NOT_MOMO_REGISTERED = '102';

    const WRONG_PIN_OR_TIMEOUT = '103';

    const DECLINED_OR_TERMINATED = '104';

    const NOT_ENOUGH_BALANCE_2 = '105';

    const INVALID_VOUCHER_CODE = '114';
}
