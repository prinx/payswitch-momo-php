<?php

namespace Tests\Unit;

use Prinx\Payswitch\MobileMoneyResponse;
use Prinx\Payswitch\MobileMoneyResponseCheck;
use Prinx\Payswitch\MobileMoneyResponseCheckCallbackHandler;
use Tests\TestCase;

class MobileMoneyResponseCheckTest extends TestCase
{
    public function testRequest()
    {
        $check = (new MobileMoneyResponseCheck)->check([env('TEST_TRANSACTION_ID')]);

        $this->assertInstanceOf(MobileMoneyResponseCheckCallbackHandler::class, $check);

        // var_dump($check->getResponses());
    }
}
