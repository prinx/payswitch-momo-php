<?php

namespace Tests\Unit;

use Prinx\Payswitch\MobileMoneyResponse;
use Prinx\Payswitch\MobileMoneyResponseCheck;
use Prinx\Payswitch\MobileMoneyResponseCheckCallbackHandler;
use Tests\TestCase;
use function Prinx\Dotenv\addEnv;
use function Prinx\Dotenv\env;
use function Prinx\Dotenv\loadEnv;

class MobileMoneyResponseCheckTest extends TestCase
{
    public function testRequest()
    {
        $check = (new MobileMoneyResponseCheck)->check([env('TEST_TRANSACTION_ID')]);

        $this->assertInstanceOf(MobileMoneyResponseCheckCallbackHandler::class, $check);
    }

    public function testFormatUrl()
    {
        $expected = 'https://test.theteller.net/v1.1/users/transactions/123456/status';

        addEnv('PAYSWITCH_MOMO_API_ENV', 'test');
        $transactionId = 123456;
        $actual = (new MobileMoneyResponseCheck)->formatUrl($transactionId);

        $this->assertEquals($expected, $actual);
    }
}
