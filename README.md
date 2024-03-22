# Asserts Schema <small>for Laravel</small>

This is yet another package to assert that your test requests and responses matches an OpenAPI schema.
It works by comparing the request/response against a schema file, and it will fail if the request/response does not match the schema.

## Installation

```bash
composer require --dev studocu/asserts-schema-for-laravel
```

## Setup

Add the `AssertsSchema` trait to your Test Class:

```php
<?php

use Studocu\AssertsSchemaForLaravel\Concerns\AssertsSchema;
use Illuminate\Foundation\Testing\TestCase;

class PostShowTest extends TestCase
{
    use AssertsSchema;
```

Configure the trait to use the right API file and prefix:

```php
    protected function setUp(): void
    {
        parent::setUp();

        $this->configureSchema(
            yamlFullPath: resource_path('/api/api.yaml'),
            apiPrefix: '/api/v2',
        );
    }
```

- `yamlFullPath` is the full path to your OpenAPI file.
- `apiPrefix` is the prefix of your API, e.g. `/api/v2`. This is needed only if your API path are defined without the prefix. For example, OpenAPI's path is `/posts` but the full URL is `/api/v2/posts`.

## Usage

### Asserting a request

```php
    public function test_it_can_show_a_post(): void
    {
        $response = $this->getJson('/api/v2/posts/1');

        $response->assertOk();
    }
```

Every endpoint request that follows the `configureSchema` will be asserted against the OpenAPI schema.

### Skipping

Let's say you are testing the validation rules of an endpoint. In tha case, you'll be testing a 422 response, with a certain structure.
Since the request itself might be invalid (for example, not sending a required parameter), we need to disable the schema checks for the request, but enable it for the response.

```php
    public function test_it_validates_the_body(): void
    {
        $this->expectsSchemaExceptionMessage(
            '[OpenAPI Request Mismatch] Body does not match schema for content-type "application/json" for Request [post /posts]. Error in: "content". '
            .'Keyword validation failed: Required property "content" must be present in the object.'
        );
    
        $response = $this->postJson('/api/v2/posts/', [
            'title' => 'My title',
        ]);

        $response->assertStatus(422);
    }
```

## Test

```bash
composer test
```
