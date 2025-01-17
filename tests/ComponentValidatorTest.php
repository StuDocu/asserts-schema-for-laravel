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

    public function test_it_will_throw_exception_if_component_is_not_found(): void
    {
        $this->expectException(OpenAPIMismatchException::class);

        $this->validateComponent('NotFound', []);
    }

    public function test_it_will_throw_exception_when_data_does_not_validate_against_component(): void
    {
        $this->expectException(OpenAPIMismatchException::class);

        $this->validateComponent('Test', '123');
    }

    public function test_it_can_validate_data_against_component(): void
    {
        $this->validateComponent('Test', ['id' => 123]);

        $this->addToAssertionCount(1);
    }
}
