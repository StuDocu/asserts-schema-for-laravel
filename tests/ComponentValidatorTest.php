<?php

namespace Studocu\AssertsSchemaForLaravel\Tests;

use Studocu\AssertsSchemaForLaravel\Exceptions\OpenAPIComponentMismatchException;
use Studocu\AssertsSchemaForLaravel\Exceptions\OpenAPIComponentNotFoundException;

class ComponentValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configureSchema(
            yamlFullPath: __DIR__.'/Stubs/api.yaml',
            apiPrefix: '/api/v2',
        );
    }

    public function testItWillThrowExceptionIfComponentIsNotFound(): void
    {
        $this->expectException(OpenAPIComponentNotFoundException::class);

        $this->validateComponent('NotFound', []);
    }

    public function testItWillThrowExceptionWhenDataDoesNotValidateAgainstComponent(): void
    {
        $this->expectException(OpenAPIComponentMismatchException::class);

        $this->validateComponent('Test', '123');
    }

    public function testItCanValidateDataAgainstComponent(): void
    {
        $this->validateComponent('Test', ['id' => 123]);

        $this->addToAssertionCount(1);
    }
}
