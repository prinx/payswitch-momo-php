# PAYSWITCH MOBILE MONEY PHP SDK

## Work In Progress

## Installation

```shell
composer require prinx/payswitch-momo
```

## Usage

```php

use Prinx\Payswitch\MobileMoney;

$momo = new MobileMoney;

$amount = 1; // 1 cedi
$phone = '233...';
$network = ''; // Must be one of MTN|VODAFONE|AIRTEL

$response = $momo->pay($amount, $phone, $network);

if ($response->isSuccessful()) {
    // User successfully payed
} else {
    $error = $response->getError();
}
```

### Voucher code for Vodafone users

_Vodafone_ users always generate a voucher code to be able to process their mobile money transactions. After getting the voucher code from the user, you can easily pass it argument of the `pay` method:

```php
// ...

$response = $momo->pay($amount, $phone, $network, $voucherCode);
```

