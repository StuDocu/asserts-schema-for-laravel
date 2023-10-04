<?php

namespace Studocu\AssertsSchemaForLaravel\Tests;

use Generator;
use Illuminate\Support\Facades\Route;
use Studocu\AssertsSchemaForLaravel\Concerns\AssertsSchema;
use Studocu\AssertsSchemaForLaravel\Exceptions\OpenAPIResponseMismatchException;

class ResponseValidatorTest extends \Orchestra\Testbench\TestCase
{
    use AssertsSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureSchema(
            yamlFullPath: __DIR__.'/Stubs/response.yaml',
            apiPrefix: '/api/v2',
        );
    }

    /**
     * @dataProvider validResponseDataProvider
     *
     * @param  array<array-key, mixed>  $data
     */
    public function testShouldPassWithCorrectResponseParameters(array $data): void
    {
        Route::prefix('api/v2')->group(function () use ($data) {
            Route::get('test', fn () => $data);
        });

        $this->getJson('/api/v2/test')->assertOk();
    }

    public static function validResponseDataProvider(): Generator
    {
        yield [['message' => 'success', 'data' => ['id' => 1, 'name' => 'string', 'optional' => true]]];
        yield [['message' => 'success', 'data' => ['id' => 1, 'name' => 'string'], 'meta' => ['total' => 1, 'page' => 10, 'perPage' => 1]]];
    }

    /**
     * @param  array{response: array<array-key, mixed>, exception: string}  $data
     *
     * @dataProvider invalidResponseDataProvider
     */
    public function testShouldNotPassWithIncorrectResponseParameters(array $data): void
    {
        Route::prefix('api/v2')->group(function () use ($data) {
            Route::get('test', fn () => $data['response']);
        });

        $this->expectException(OpenAPIResponseMismatchException::class);
        $this->expectExceptionMessageMatches($data['exception']);

        $this->getJson('/api/v2/test')->assertOk();
    }

    /**
     * @return Generator<array{0: array{'response': array<array-key, mixed>, 'exception': string}}>
     */
    public static function invalidResponseDataProvider(): Generator
    {
        yield [[
            'response' => ['message' => 'string', 'data' => ['id' => 'string', 'name' => 'string', 'optional' => true]],
            'exception' => '/Error in: "data\.id"\.(.)* expected to be \'integer\', but \'string\' given/',
        ]];
        yield [[
            'response' => ['data' => ['id' => 'string', 'name' => 'string', 'optional' => true]],
            'exception' => '/Error in: "message"\.(.)*Required property \'message\' must be present in the object/',
        ]];
        yield [[
            'response' => [
                'message' => 'string',
                'data' => ['id' => 1, 'name' => 'string', 'optional' => 1],
            ],
            'exception' => '/Error in: "data\.optional"\.(.)* expected to be \'boolean\', but \'integer\' given/',
        ]];
        yield [[
            'response' => [
                'message' => 'string',
                'data' => ['id' => 1, 'name' => 'string'],
                'meta' => ['total' => 1, 'page' => 1],
            ],
            'exception' => '/Error in: "meta\.perPage"\.(.)*Required property \'perPage\' must be present/',
        ]];
    }

    public function testShouldPassForNoContentResponse(): void
    {
        Route::prefix('api/v2')->group(function () {
            Route::post('test', fn () => response()->noContent());
        });

        $this->postJson('/api/v2/test')->assertNoContent();
    }

    public function testShouldNotPassForUndocumentedNoContentResponse(): void
    {
        $this->expectException(OpenAPIResponseMismatchException::class);
        $this->expectExceptionMessage('[Response Schema Mismatch] Response code 204 for [POST] /test-missing-no-content was not found.');

        Route::prefix('api/v2')->group(function () {
            Route::post('test-missing-no-content', fn () => response()->noContent());
        });

        $this->postJson('/api/v2/test-missing-no-content')->assertNoContent();
    }
}
