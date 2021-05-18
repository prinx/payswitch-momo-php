<?php

namespace Tests\Unit;

use function Prinx\Dotenv\addEnv;
use function Prinx\Dotenv\env;
use Prinx\Payswitch\MobileMoneyStatusChecker;
use Prinx\Payswitch\MobileMoneyStatusCheckerResponse;
use Tests\TestCase;

class MobileMoneyStatusCheckerTest extends TestCase
{
    public function testRequest()
    {
        $check = (new MobileMoneyStatusChecker())->check([env('TEST_TRANSACTION_ID')]);

        $this->assertInstanceOf(MobileMoneyStatusCheckerResponse::class, $check);
    }

    public function testFormatUrl()
    {
        $expected = 'https://test.theteller.net/v1.1/users/transactions/123456/status';

        addEnv('PAYSWITCH_MOMO_API_ENV', 'test');
        $transactionId = 123456;
        $actual = (new MobileMoneyStatusChecker())->formatUrl($transactionId);

        $this->assertEquals($expected, $actual);
    }
}
