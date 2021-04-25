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

    /**
     * Probably the developer did not perform the transaction or used a payswitch account different 
     * from the one they are using to check the status.
     */
    const TRANSACTION_NOT_FOUND = '999';
}
