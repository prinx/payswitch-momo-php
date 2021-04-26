<?php

namespace Prinx\Payswitch\Contracts;

interface MobileMoneyStatusInterface
{
    /**
     * Has user successfully payed?
     *
     * @return boolean
     */
    public function isSuccessful(): bool;

    /**
     * Is request being processed or a cURL error happened or a package-related error happened?
     *
     * @return boolean
     */
    public function isBeingProcessed(): bool;

    /**
     * Transaction ID.
     *
     * @return string|null
     */
    public function getTransactionId();

    /**
     * Error.
     *
     * @return string|null
     */
    public function getError();

    /**
     * Json-decoded response.
     *
     * @return array|null
     */
    public function getResponse();

    /**
     * HTTP resquest status code.
     *
     * Currently will return a status only if the request is successful (200).
     *
     * @return int|null
     */
    public function getStatus();

    /**
     * Get transation raw response.
     *
     * @return string|null
     */
    public function getRawResponse();

    /**
     * Status code of the transaction.
     *
     * @return int
     */
    public function getCode();

    /**
     * Phone number of the momo user.
     *
     * @return string|null
     */
    public function getPhone();

    /**
     * Network of the momo user.
     *
     * @return string|null
     */
    public function getNetwork();

    /**
     * Short word describing the transaction.
     *
     * @return string|null
     */
    public function getStatusDescription();

    /**
     * Details explanation of the status of the request.
     *
     * @return void
     */
    public function getReason();

    /**
     * Amount of the transaction.
     *
     * @return int|float
     */
    public function getAmount();

    /**
     * Data appended to the response by the developer (typically used in the callbacks).
     *
     * @return mixed
     */
    public function getAppended();
}
