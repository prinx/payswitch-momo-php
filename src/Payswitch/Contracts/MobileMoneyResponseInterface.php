<?php

namespace Prinx\Payswitch\Contracts;

interface MobileMoneyResponseInterface
{
    public function isBeingProcessed(): bool;
}
