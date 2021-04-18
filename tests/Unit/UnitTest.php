<?php

namespace Tests\Unit;

use Prinx\Payswitch\MobileMoney;
use Tests\TestCase;

class UnitTest extends TestCase
{
    public function testExample()
    {
        $momo = new MobileMoney();

        $payment = $momo->pay(0.2, '233545466795', 'MTN');

        if (!$payment->isBeingProcessed()) {
            echo $payment->getError();
        }

        $this->assertTrue($payment->isBeingProcessed());
    }
}
