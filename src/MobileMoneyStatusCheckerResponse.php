<?php

namespace Prinx\Payswitch;

use Closure;
use Prinx\Notify\Log;
use Prinx\Payswitch\Exceptions\InvalidCurrentResponseKeyException;
use Txtpay\Support\SlackLog;

class MobileMoneyStatusCheckerResponse
{
    protected $customConditions = [
        'success',
        'pending',
        'failure',
        'transactionNotFound',
        'always',
        'curlError',
    ];

    /**
     * @var Log
     */
    protected $logger;

    protected $logFolder;

    /**
     * @var MobileMoneyStatus
     */
    protected $currentResponse;

    protected $canLog = true;

    protected $jsonPrettyPrint = true;

    protected $requiredParameters = [
        'code',
        'status',
        'reason',
        'transaction_id',
        'r_switch',
        'subscriber_number',
        'amount',
    ];

    protected $customCurrentResponseNames = [
        'code'              => 'code',
        'status'            => 'status',
        'reason'            => 'details',
        'transaction_id'    => 'id',
        'r_switch'          => 'network',
        'subscriber_number' => 'phone',
        'amount'            => 'amount',
    ];

    protected $conditionToCheckForSuccess = 'code';

    protected $conditionToCheckForPending = 'code';

    protected $conditionToCheckForTransactionNotFound = 'code';

    /**
     * Codes of the request payload that determine that the transaction was successful.
     *
     * @var array
     */
    protected $successValues = ['000'];

    /**
     * Codes of the request payload that determine that the transaction is pending.
     *
     * @var array
     */
    protected $pendingValues = ['111'];

    /**
     * Codes of the request payload that determine that the transaction is not found.
     *
     * @var array
     */
    protected $transactionNotFoundValues = ['999'];

    /**
     * Codes of the request payload that determine that the transaction failed.
     *
     * @var array
     */
    protected $failureValues = ['101', '102', '103', '104', '105', '114', '600', 'default'];

    protected $responses = [];

    /**
     * @var callable[]
     */
    protected $callbacks = [];

    protected $appended = [];

    public function __construct($responses)
    {
        $this->responses = $responses;
    }

    /**
     * Run the callback if conditions match the request parameters.
     *
     * @param string|array $condition String or associative array matching the request parameters.
     *                                If string, the parameter is either one of the custom conditions
     *                                specified or the conditionToCheckForSuccess.
     * @param callable     $callback  Closure or name of the method in the callback handler class.
     *
     * @return $this
     */
    public function on($condition, $callback)
    {
        if ($condition === 'curlError' && $this->isCurlError()) {
            return $this->runCallback($callback, $this->responses);
        }

        if ($this->isCurlError()) {
            return $this;
        }

        $isCustomCondition = $this->isCustomCondition($condition);

        foreach ($this->responses['data'] as $transactionId => $response) {
            $this->setCurrentResponseData($response->getResponse());
            $this->setCurrentAppendedData($transactionId, $response);

            if ($isCustomCondition) {
                $matchesCondition = $this->{'is'.ucfirst($condition)}();
                $this->runCallbackIf($matchesCondition, $callback, $response);
            } else {
                $this->runCallbackIf($this->matches($condition), $callback, $response);
            }
        }

        return $this;
    }

    public function isCustomCondition($condition)
    {
        return in_array($condition, $this->customConditions);
    }

    /**
     * Check if the request payload matches a condition.
     *
     * @param array|string $condition
     *
     * @return bool
     */
    public function matches($condition)
    {
        if (!is_array($condition)) {
            $condition = [$this->conditionToCheckForSuccess => $condition];
        }

        $currentResponse = $this->getCurrentResponse();

        foreach ($condition as $key => $value) {
            if (!isset($currentResponse[$key])) {
                throw new InvalidCurrentResponseKeyException('Unknown key '.$key.' in the conditions passed to the "on" method.');
            }

            if ($currentResponse[$key] != $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Register callback if the transaction is successful.
     *
     * The successful transaction is determined by the code of the request.
     *
     * @return $this
     */
    public function onSuccess(callable $callback)
    {
        $this->on('success', $callback);

        return $this;
    }

    public function isSuccess()
    {
        return in_array(
            $this->getCurrentResponse($this->conditionToCheckForSuccess),
            $this->successValues
        );
    }

    /**
     * Run callback if the transaction is pending.
     *
     * @return $this
     */
    public function onPending(callable $callback)
    {
        $this->on('pending', $callback);

        return $this;
    }

    public function isPending()
    {
        return in_array(
            $this->getCurrentResponse($this->conditionToCheckForPending),
            $this->pendingValues
        );
    }

    /**
     * Run callback if transaction not found.
     *
     * @return $this
     */
    public function onTransactionNotFound(callable $callback)
    {
        $this->on('transactionNotFound', $callback);

        return $this;
    }

    public function isTransactionNotFound()
    {
        return in_array(
            $this->getCurrentResponse($this->conditionToCheckForTransactionNotFound),
            $this->transactionNotFoundValues
        );
    }

    /**
     * Run callback if the transaction has failed.
     *
     * The failed request is determined by the code of the request.
     *
     * @return $this
     */
    public function onFailure(callable $callback)
    {
        $this->on('failure', $callback);

        return $this;
    }

    public function isFailure()
    {
        return !$this->isSuccess() && !$this->isPending() && !$this->isTransactionNotFound();
    }

    /**
     * Run callback whether the transaction is successful or not.
     *
     * @return $this
     */
    public function always(callable $callback)
    {
        $this->on('always', $callback);
    }

    public function isAlways()
    {
        return true;
    }

    public function onCurlError(callable $callback)
    {
        $this->on('curlError', $callback);

        return $this;
    }

    public function isCurlError()
    {
        return !$this->responses['success'];
    }

    /**
     * Run the callback if the condition is met.
     *
     * @param bool|callable   $condition
     * @param callable|string $callback
     *
     * @return void
     */
    public function runCallbackIf($condition, $callback, $response)
    {
        $mustBeRegistered = is_callable($condition) ? call_user_func($condition) : $condition;

        if ($mustBeRegistered) {
            $this->runCallback($callback, $response);
        }

        return $this;
    }

    public function runCallable($closure, $args)
    {
        return call_user_func_array($closure, $args);
    }

    public function runCallback($callback, $response)
    {
        if (is_callable($callback)) {
            $this->runCallable($callback, [$response]);

            return $this;
        }

        if (is_array($callback)) {
            foreach ($callback as $actualCallback) {
                if (is_callable($actualCallback)) {
                    $this->runCallable($actualCallback, [$response]);
                }
            }

            return $this;
        }

        return $this;
    }

    /**
     * Success codes.
     *
     * @return array
     */
    public function getSuccessValues()
    {
        return $this->successValues;
    }

    /**
     * Pending codes.
     *
     * @return array
     */
    public function getPendingValues()
    {
        return $this->pendingValues;
    }

    /**
     * Transaction not found codes.
     *
     * @return array
     */
    public function getTransactionNotFoundValues()
    {
        return $this->transactionNotFoundValues;
    }

    /**
     * Failure codes.
     *
     * @return array
     */
    public function getFailureValues()
    {
        return $this->failureValues;
    }

    public function setCurrentResponseData($currentResponse)
    {
        $this->originalCurrentResponse = $currentResponse;

        $this->populateCustomCurrentResponseDataNames();

        return $this;
    }

    public function populateCustomCurrentResponseDataNames()
    {
        if (!$this->originalCurrentResponse) {
            $this->currentResponse = null;

            return;
        }

        foreach ($this->customCurrentResponseNames as $original => $custom) {
            $this->currentResponse[$custom] = $this->originalCurrentResponse[$original] ?? null;
        }

        return $this->currentResponse;
    }

    public function setCurrentAppendedData($transactionId, $response)
    {
        if (array_key_exists($transactionId, $this->appended)) {
            $response->setAppended($this->appended[$transactionId]);
        }
    }

    public function getCurrentResponse($attribute = null, $default = null)
    {
        return $attribute ? $this->currentResponse[$attribute] ?? $default : $this->currentResponse;
    }

    public function get($attribute = null, $default = null)
    {
        return $this->getCurrentResponse($attribute, $default);
    }

    public function getResponses()
    {
        return $this->responses;
    }

    /**
     * Append extra data to the responses.
     *
     * $toAppend must be in form:
     *      [
     *          '012345678' => 'data_to_append_for_this_transaction_id',
     *          '112345678' => 'data_to_append_for_this_transaction_id',
     *          '212345678' => 'data_to_append_for_this_transaction_id',
     *      ]
     *
     * The indexes are the transaction ids.
     *
     * You can append extra data for only the transaction id that you need.
     *
     * @return $this
     */
    public function append(array $toAppend)
    {
        $this->appended = array_replace($this->appended, $toAppend);

        return $this;
    }

    public function setAppended(array $toAppend)
    {
        $this->appended = $toAppend;

        return $this;
    }

    /**
     * @return array
     */
    public function getAppended()
    {
        return $this->appended;
    }

    public function getLogger()
    {
        return $this->logger ?? $this->logger = new Log();
    }

    public function log(string $message, $file = '', $level = 'info')
    {
        if (!$this->canLog || env('PAYSWITCH_MOMO_LOG_ENABLED', null) === false) {
            return $this;
        }

        SlackLog::log($message, $level);

        if (env('PAYSWITCH_MOMO_LOCAL_LOG_ENABLED', true) === false || !$file || !$this->getLogger()) {
            return $this;
        }

        $this->getLogger()
            ->setFile($file)
            ->{$level}($message);

        return $this;
    }

    public function getMessages($code = null, $transactionId = null)
    {
        $messages = [
            '000' => 'Transaction successful. Your transaction ID is '.$transactionId,
            '111' => 'Payment request sent successfully. Awaiting response.',
            '101' => "You don't have enough balance to process this request.",
            '102' => 'This number is not registered for mobile money.',
            '103' => 'Wrong PIN or transaction timed out.',
            '104' => 'Transaction declined.',
            '105' => "You don't have enough balance to process this request.",
            '114' => 'Invalid Voucher code.',
            '999' => 'Transaction not found.',
            // '600' => $this->getCurrentResponse('details', 'An error happened when processing your request.'),  // Use 'default' for error code 600
            'default' => $this->getCurrentResponse('details', 'An error happened when processing your request'),
        ];

        return $code ? $messages[$code] ?? $messages['default'] : $messages;
    }

    public function getMessage()
    {
        return $this->getMessages($this->getCurrentResponse('code'), $this->getCurrentResponse('id'));
    }

    public function respond($message)
    {
        echo $message;
    }
}
