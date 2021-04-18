<?php

namespace Prinx\Payswitch;

use Prinx\Payswitch\Contracts\MobileMoneyResponseInterface;

class MobileMoneyResponse implements MobileMoneyResponseInterface
{
    protected $rawResponse = null;
    protected $response = null;
    protected $error = null;
    protected $data = null;
    protected $isSuccessful = false;
    protected $isBeingProcessed = false;
    protected $status = 0;

    public function __construct($response, $error, $data)
    {
        $this->rawResponse = $response;
        $this->response = $response;
        $this->data = $data;

        if ($error) {
            $this->error = $error;

            return;
        }

        $this->isBeingProcessed = true;

        $responseData = $response ? json_decode($response, true) : [];
        $isValidJson = json_last_error() === JSON_ERROR_NONE;

        if (empty($responseData) || !is_array($responseData)) {
            $this->error = 'Cannot parse the response. Kindly check the getRawData for the error.';

            return;
        }

        $this->response = $responseData;

        if (isset($responseData['code'])) {
            switch ((string) $responseData['code']) {
                case '000':
                    $this->isSuccessful = true;
                    $this->status = 200;
                    break;
                case '101':
                    $this->error = "You don't have enough balance to process this request.";
                    break;
                case '105':
                    $this->error = "You don't have enough balance to process this request.";
                    break;
                case '102':
                    $this->error = 'This number is not registered for mobile money.';
                    break;
                case '103':
                    $this->error = 'Wrong PIN or transaction timed out.';
                    break;
                case '104':
                    $this->error = 'Transaction declined.';
                    break;
                case '114':
                    $this->error = 'Invalid Voucher code';
                    break;
                default:
                    $this->error = $responseData['reason'] ?? 'An error happened when processing your request.';
                    break;
            }
        } elseif ($isValidJson && isset($responseData['voucher_code'])) {
            $this->error = 'Voucher code error. Make sure the voucher code is passed when network is vodafone';
        } elseif (
            $isValidJson &&
            isset($responseData['status']) &&
            strtolower($responseData['status']) === 'declined'
        ) {
            $this->error = $responseData['reason'] ?? 'Request declined. Can be from many reasons.';
        } elseif (
            $isValidJson &&
            isset($responseData['desc'])
        ) {
            $this->error = $responseData['reason'] ?? 'Request declined. Can be from many reasons.';
        } elseif (
            $isValidJson &&
            isset($responseData['voucher_code'])
        ) {
            $this->error = $responseData['voucher_code'];
        } elseif ($isValidJson) {
            $this->error = $response;
        } else {
            $this->error = 'Kindly check the getRawData for the error.';
        }
    }

    public function isSuccessful(): bool
    {
        return $this->isSuccessful;
    }

    public function isBeingProcessed(): bool
    {
        return $this->isBeingProcessed;
    }

    public function getTransactionId()
    {
        return $this->data['transaction_id'];
    }

    public function getPhone()
    {
        return $this->data['subscriber_number'];
    }

    public function getError()
    {
        return $this->error;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getRawResponse()
    {
        return $this->rawResponse;
    }
}
