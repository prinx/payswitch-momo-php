<?php

namespace Prinx\Payswitch\Traits;

trait GetResponseAttributes
{
    public function getCode()
    {
        return $this->getResponse('code');
    }

    public function getPhone()
    {
        return $this->getResponse('subscriber_number');
    }

    public function getTransactionId()
    {
        return $this->getResponse('transaction_id');
    }

    public function getNetwork()
    {
        return $this->getResponse('r_switch');
    }

    public function getStatusDescription()
    {
        return $this->getResponse('status');
    }

    public function getReason()
    {
        return $this->getResponse('reason');
    }

    public function getAmount()
    {
        return $this->getResponse('amount');
    }
}
