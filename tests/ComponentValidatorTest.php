<?php

namespace Studocu\AssertsSchemaForLaravel\Tests;

use Studocu\AssertsSchemaForLaravel\Exceptions\OpenAPIMismatchException;

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
        $this->expectException(OpenAPIMismatchException::class);

        $this->validateComponent('NotFound', []);
    }

    public function testItWillThrowExceptionWhenDataDoesNotValidateAgainstComponent(): void
    {
        $this->expectException(OpenAPIMismatchException::class);

        $this->validateComponent('Test', '123');
    }

    public function testItCanValidateDataAgainstComponent(): void
    {
        $this->validateComponent('Test', ['id' => 123]);

        $this->addToAssertionCount(1);
    }
}
