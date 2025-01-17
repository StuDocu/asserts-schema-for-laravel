<?php

namespace Studocu\AssertsSchemaForLaravel\Tests;

use Generator;
use Illuminate\Support\Facades\Route;
use Studocu\AssertsSchemaForLaravel\APISchemaValidator;
use Studocu\AssertsSchemaForLaravel\Concerns\AssertsSchema;
use Studocu\AssertsSchemaForLaravel\Exceptions\OpenAPIMismatchException;

class RequestValidatorTest extends \Orchestra\Testbench\TestCase
{
    use AssertsSchema;

    protected function setUp(): void
    {
        parent::setUp();

        // Empty cache
        APISchemaValidator::$validatorBuilderCache = [];
        APISchemaValidator::$yamlCache = [];

        $this->configureSchema(
            yamlFullPath: __DIR__.'/Stubs/api.yaml',
            apiPrefix: '/api/v2',
        );
    }

    /**
     * @dataProvider validQueryParametersDataProvider
     */
    public function testShouldPassWithQueryParameters(string $data): void
    {
        Route::prefix('api/v2')->group(function () {
            Route::get('query-params', fn () => ['message' => 'ok', 'id' => 1]);
        });

        $this->getJson('/api/v2/query-params?'.$data)->assertOk();
    }

    /**
     * @return Generator<array{0: string}>
     */
    public static function validQueryParametersDataProvider(): Generator
    {
        yield ['id=1'];
        yield ['id=5'];
        yield ['id=7'];
        yield ['id=1&name=string'];
    }

    /**
     * @dataProvider invalidQueryParametersDataProvider
     *
     * @param  array{params: string, exception: string}  $data
     */
    public function testShouldNotPassWithIncorrectQueryParameters(array $data): void
    {
        Route::prefix('api/v2')->group(function () {
            Route::get('query-params', fn () => ['message' => 'ok', 'id' => 1]);
        });

        $this->expectException(OpenAPIMismatchException::class);
        // Error: "Missing required argument "id" for Request [get /query-params]"
        $this->expectExceptionMessageMatches($data['exception']);

        $this->getJson('/api/v2/query-params?'.$data['params'])->assertOk();
    }

    /**
     * @return Generator<array{0: array{params: string, exception: string}}>
     */
    public static function invalidQueryParametersDataProvider(): Generator
    {
        yield [['params' => 'id=a', 'exception' => '/Value "a" for argument "id" is invalid for Request \[get \/query-params\]/']];
        yield [['params' => 'name=string', 'exception' => '/Missing required argument "id" for Request \[get \/query-params\]/']];
        yield [['params' => 'name=1', 'exception' => '/Missing required argument "id" for Request \[get \/query-params\]/']];
    }
}
