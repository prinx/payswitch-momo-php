<?php

namespace Prinx\Payswitch;

use Prinx\Payswitch\Contracts\MobileMoneyStatusInterface;
use Prinx\Payswitch\Traits\GetStatusAttributes;

class MobileMoneyStatus implements MobileMoneyStatusInterface
{
    use GetStatusAttributes;

    protected $rawResponse = null;
    protected $response = null;
    protected $error = null;
    protected $transactionId = null;
    protected $isSuccessful = false;
    protected $isBeingProcessed = false;
    protected $status = 0;
    protected $appended = null;

    public function __construct($response, $error, $transactionId, $appended = null)
    {
        $this->rawResponse = $response;
        $this->response = $response;
        $this->transactionId = $transactionId;
        $this->appended = $appended;

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
                case '102':
                    $this->error = 'This number is not registered for mobile money.';
                    break;
                case '103':
                    $this->error = 'Wrong PIN or transaction timed out.';
                    break;
                case '104':
                    $this->error = 'Transaction declined.';
                    break;
                case '105':
                    $this->error = "You don't have enough balance to process this request.";
                    break;
                case '114':
                    $this->error = 'Invalid Voucher code.';
                    break;
                case '999':
                    $this->error = 'Transaction not found.'; // Probably the developer did not perform the transaction or used a payswitch account different from the one they are using to check the status.
                    break;
                case '600': // Fall through to default
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
        return $this->transactionId;
    }

    public function getError()
    {
        return $this->error;
    }

    /**
     * Get an attribute of the response or default value if attribute not found. Get full response if no attribute passed.
     *
     * @param string $attribute
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getResponse($attribute = null, $default = null)
    {
        if ($attribute) {
            return $this->response[$attribute] ?? $default;
        }

        return $this->response;
    }

    /**
     * Get an attribute of the response or default value if attribute not found.
     *
     * @param string $attribute
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($attribute = null, $default = null)
    {
        return $this->getResponse($attribute, $default);
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getRawResponse()
    {
        return $this->rawResponse;
    }

    public function getAppended()
    {
        return $this->appended;
    }

    public function setAppended($toAppend)
    {
        $this->appended = $toAppend;

        return $this;
    }
}
