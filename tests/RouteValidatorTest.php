<?php

namespace Studocu\AssertsSchemaForLaravel\Tests;

use Illuminate\Support\Facades\Route;
use Studocu\AssertsSchemaForLaravel\APISchemaValidator;
use Studocu\AssertsSchemaForLaravel\Concerns\AssertsSchema;
use Studocu\AssertsSchemaForLaravel\Exceptions\OpenAPIMismatchException;

class RouteValidatorTest extends \Orchestra\Testbench\TestCase
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
            apiPrefix: '/api/v1',
        );
    }

    public function testShouldFindRouteWithQueryParams(): void
    {
        Route::prefix('api/v1')->group(function () {
            Route::get('/my-url/{id}', fn (int $id) => ['message' => 'ok', 'id' => $id]);
        });

        $response = $this->getJson('/api/v1/my-url/1?foo=bar');

        $response->assertOk();
    }

    public function testShouldFindPostRoutesToo(): void
    {
        Route::prefix('api/v1')->group(function () {
            Route::post('/my-url/{id}', fn (int $id) => response(['message' => 'ok', 'extra' => true], 200));
        });

        $response = $this->postJson('/api/v1/my-url/1', ['foo' => 'bar']);

        $response->assertSuccessful();
    }

    public function testShouldBreakWithUndefinedRoutesOnSchema(): void
    {
        Route::prefix('api/v1')->group(function () {
            Route::get('/my-url/{id}', fn (int $id) => ['message' => 'ok', 'id' => $id]);
            Route::get('/my-url-new/{id}', fn (int $id) => ['message' => 'ok', 'id' => $id]);
        });

        $this->expectException(OpenAPIMismatchException::class);
        // Error: "[Route Schema Mismatch] Route path [/my-url-new/{id}] was not found on Schema"
        $this->expectExceptionMessageMatches('/\[OpenAPI Request Mismatch\] Route path \[\/my-url-new\/\{id\}\] was not found/');

        $response = $this->getJson('/api/v1/my-url-new/1');
    }

    public function testShouldBreakWhenCallingAnUndefinedMethod(): void
    {
        Route::prefix('api/v1')->group(function () {
            Route::get('/my-url/{id}', fn (int $id) => ['message' => 'ok', 'id' => $id]);
            Route::post('/my-url/{id}', fn (int $id) => ['message' => 'ok', 'id' => $id]);

            // The next route exists here but not in the schema:
            Route::put('/my-url/{id}', fn (int $id) => ['message' => 'ok', 'id' => $id]);
        });

        $this->expectException(OpenAPIMismatchException::class);
        // Error: [Schema Mismatch] Route path [PUT: /my-url/{id}] was not found on Schema
        $this->expectExceptionMessageMatches('/Route path \[PUT\: \/my-url\/\{id\}\] was not found/');

        $this->putJson('/api/v1/my-url/1');
    }

    public function testShouldBreakWhenRouteParametersAreNotValid(): void
    {
        Route::prefix('api/v1')->group(function () {
            Route::get('/my-url/{id}', fn (int $id) => ['message' => 'ok', 'id' => 1]);
        });

        $this->expectException(OpenAPIMismatchException::class);
        // Error: [Schema Mismatch] Value "asd" for parameter "id" is invalid for Request [get /my-url/{id}]\nValue expected to be 'integer', but 'string' given.
        $this->expectExceptionMessageMatches('/Value "asd" for parameter "id" is invalid for Request \[get \/my-url\/\{id\}\]\nValue expected to be \'integer\', but \'string\' given./');

        $this->getJson('/api/v1/my-url/asd');
    }
}
