<?php

namespace Prinx\Payswitch\Contracts;

interface MobileMoneyResponseInterface
{
    public function isBeingProcessed(): bool;

    public function getTransactionId();

    public function tel();

    public function msisdn();

    public function getError();

    public function getResponse();
    public function getStatus();

    public function getRawResponse();
}
