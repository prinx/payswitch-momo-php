<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Base Test case.
 */
abstract class TestCase extends BaseTestCase
{
    public function __construct()
    {
        parent::__construct();
    }
}
