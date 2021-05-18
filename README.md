# PAYSWITCH MOBILE MONEY PHP SDK

<p align="center">
<a href="https://travis-ci.com/prinx/payswitch-momo-php"><img src="https://travis-ci.com/prinx/payswitch-momo-php.svg?branch=main" alt="Build Status"></a>
<a href="https://packagist.org/packages/prinx/payswitch-momo"><img src="https://poser.pugx.org/prinx/payswitch-momo/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/prinx/payswitch-momo"><img src="https://poser.pugx.org/prinx/payswitch-momo/license.svg" alt="License"></a>
<a href="https://github.styleci.io/repos/359152941?branch=main"><img src="https://github.styleci.io/repos/359152941/shield?style=flat&branch=main" alt="StyleCI"></a>

## Work In Progress

## Installation

```shell
composer require prinx/payswitch-momo
```

## Usage

### Configuration in `.env` file (in the project root folder)

```ini
# .env file

PAYSWITCH_MOMO_API_ENV=test|prod
PAYSWITCH_MOMO_API_USER=
PAYSWITCH_MOMO_API_KEY=
PAYSWITCH_MOMO_API_MERCHANT_ID=
PAYSWITCH_MOMO_API_PROCESSING_CODE="000200"
PAYSWITCH_MOMO_API_DESCRIPTION="At least 10 characters"

PAYSWITCH_MOMO_LOG_ENABLED=true|false
PAYSWITCH_MOMO_LOCAL_LOG_ENABLED=true|false
```

> Make sure the value for `PAYSWITCH_MOMO_API_PROCESSING_CODE` and `PAYSWITCH_MOMO_API_KEY` are enclosed with double quotes.

### Making a payment request

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

## License

[MIT](LICENSE)
