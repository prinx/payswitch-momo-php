<?php

namespace Tests\Unit;

use Prinx\Payswitch\MobileMoneyStatus;
use Prinx\Payswitch\MobileMoneyStatusCheckCallbackHandler;
use Prinx\Payswitch\MobileMoneyStatusCheckerResponse;
use Tests\TestCase;

class MobileMoneyStatusCheckerResponseTest extends TestCase
{
    public function testCallbackCalledWhenSuccess()
    {
        $responses = [
            'success' => true,
            'data' => [
                new MobileMoneyStatus(json_encode([
                    'code' => '000',
                    'status' => 'approved',
                    'reason' => 'Transaction Successful',
                    'transaction_id' => 0,
                    'r_switch' => 'VIS',
                    'subscriber_number' => '************1999',
                    'amount' => 1,
                ]), '', 0),
            ],
        ];

        $this->callbackCalledOn('onSuccess', $responses, true);
    }

    public function testCallbackCalledOnSuccessCode()
    {
        $responses = [
            'success' => true,
            'data' => [
                new MobileMoneyStatus(json_encode([
                    'code' => '000',
                    'status' => 'approved',
                    'reason' => 'Transaction Successful',
                    'transaction_id' => 0,
                    'r_switch' => 'VIS',
                    'subscriber_number' => '************1999',
                    'amount' => 1,
                ]), '', 0),
            ],
        ];

        $this->callbackCalledOn('000', $responses);
    }

    public function testCallbackCalledWhenFailure()
    {
        $responses = [
            'success' => true,
            'data' => [
                new MobileMoneyStatus(json_encode([
                    'code' => '105',
                    'status' => '',
                    'reason' => 'Transaction',
                    'transaction_id' => 0,
                    'r_switch' => 'VIS',
                    'subscriber_number' => '************1999',
                    'amount' => 1,
                ]), '', 0),
            ],
        ];

        $this->callbackCalledOn('onFailure', $responses, true);
    }

    public function testCallbackCalledOnFailureCodes()
    {
        $failureCodes = (new MobileMoneyStatusCheckerResponse([]))->getFailureValues();

        foreach ($failureCodes as  $code) {
            $responses = [
            'success' => true,
            'data' => [
                new MobileMoneyStatus(json_encode([
                    'code' => $code,
                    'status' => '',
                    'reason' => 'Transaction',
                    'transaction_id' => 0,
                    'r_switch' => 'VIS',
                    'subscriber_number' => '************1999',
                    'amount' => 1,
                ]), '', 0),
            ],
        ];

            $this->callbackCalledOn($code, $responses);
        }
    }

    public function testCallbackCalledAlways()
    {
        $responses = [
            'success' => true,
            'data' => [
                new MobileMoneyStatus(json_encode([
                    'code' => '000',
                    'status' => 'approved',
                    'reason' => 'Transaction Successful',
                    'transaction_id' => 0,
                    'r_switch' => 'VIS',
                    'subscriber_number' => '************1999',
                    'amount' => 1,
                ]), '', 0),
                new MobileMoneyStatus(json_encode([
                    'code' => '114',
                    'status' => '',
                    'reason' => 'Transaction',
                    'transaction_id' => 1,
                    'r_switch' => 'VIS',
                    'subscriber_number' => '************1999',
                    'amount' => 1,
                ]), '', 1),
                new MobileMoneyStatus(json_encode([
                    'code' => '104',
                    'status' => '',
                    'reason' => 'Transaction',
                    'transaction_id' => 2,
                    'r_switch' => 'VIS',
                    'subscriber_number' => '************1999',
                    'amount' => 1,
                ]), '', 2),
            ],
        ];

        $this->callbackCalledOn('onSuccess', $responses, true, count($responses['data']));
    }

    public function testCallbackCalledWhenCurlError()
    {
        $responses = [
            'success' => false,
            'error' => 'Error',
        ];

        $this->callbackCalledOn('onCurlError', $responses, true);
    }

    public function callbackCalledOn($condition, $responses, $isCustomCondition = false, $expectedCalls = 1)
    {
        /**
         * @var \PHPUnit\Framework\MockObject\MockObject|MobileMoneyStatusCheckerResponse
         */
        $callbackHandler = $this->getMockBuilder(MobileMoneyStatusCheckCallbackHandler::class)
            ->setConstructorArgs([$responses])
            ->onlyMethods(['runClosure'])
            ->getMock();

        $callbackHandler->expects($this->exactly($expectedCalls))
            ->method('runClosure');

        if ($isCustomCondition) {
            $callbackHandler->{$condition}(function () {});
        } else {
            $callbackHandler->on($condition, function () {});
        }
    }
}
