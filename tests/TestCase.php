<?php

namespace Studocu\AssertsSchemaForLaravel\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Studocu\AssertsSchemaForLaravel\Concerns\AssertsSchema;

class TestCase extends BaseTestCase
{
    use AssertsSchema;
}
