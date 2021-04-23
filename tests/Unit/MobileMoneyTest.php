<?php

namespace Tests\Unit;

use Prinx\Payswitch\MobileMoney;
use Tests\TestCase;
use function Prinx\Dotenv\env;

class MobileMoneyTest extends TestCase
{
    public function testRequestSuccessfulySent()
    {
        $momo = new MobileMoney();

        $payment = $momo->pay(0.2, env('TEST_PHONE'), env('TEST_PHONE_NETWORK'));

        if (!$payment->isBeingProcessed()) {
            echo $payment->getError();
        }

        $this->assertTrue($payment->isBeingProcessed());
    }
}
