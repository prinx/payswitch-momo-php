<?php

namespace Prinx\Payswitch\Contracts;

interface MobileMoneyResponseInterface
{
    public function isSuccessful(): bool;

    public function isBeingProcessed(): bool;

    public function getTransactionId();

    public function getPhone();

    public function getError();

    public function getResponse();

    public function getStatus();

    public function getRawResponse();
}
