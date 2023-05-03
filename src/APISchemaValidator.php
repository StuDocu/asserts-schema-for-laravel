<?php

namespace Studocu\AssertsSchemaForLaravel;

use Illuminate\Support\Str;
use League\OpenAPIValidation\PSR7\Exception\MultipleOperationsMismatchForRequest;
use League\OpenAPIValidation\PSR7\Exception\NoOperation;
use League\OpenAPIValidation\PSR7\Exception\NoPath;
use League\OpenAPIValidation\PSR7\Exception\Validation\InvalidBody;
use League\OpenAPIValidation\PSR7\Exception\Validation\InvalidPath;
use League\OpenAPIValidation\PSR7\Exception\Validation\InvalidQueryArgs;
use League\OpenAPIValidation\PSR7\OperationAddress;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Studocu\AssertsSchemaForLaravel\Exceptions\OpenAPIRequestMismatchException;
use Studocu\AssertsSchemaForLaravel\Exceptions\OpenAPIResponseMismatchException;
use Studocu\AssertsSchemaForLaravel\Exceptions\OpenAPIRouteMismatchException;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class APISchemaValidator
{
    private string $apiPrefix;

    private ValidatorBuilder $validatorBuilder;

    private string $routeUri;

    public function __construct(
        string $yamlFile,
        string $apiPrefix,
        private string $method,
        private string $uri,
        string $routeUri,
    ) {
        $this->apiPrefix = ltrim($apiPrefix, characters: '/');
        $this->routeUri = Str::replaceFirst($this->apiPrefix, '', subject: $routeUri);

        $this->validatorBuilder = (new ValidatorBuilder())->fromYamlFile($yamlFile);
    }

    /**
     * @param  array<array-key, mixed>  $parameters
     * @param  array<array-key, mixed>  $cookies
     * @param  array<array-key, mixed>  $files
     * @param  array<array-key, mixed>  $server
     * @param  string|null  $content
     */
    public function validateRequest(array $parameters = [], array $cookies = [], array $files = [], array $server = [], $content = null): void
    {
        $routedRequestValidator = $this->validatorBuilder->getRoutedRequestValidator();

        $address = new OperationAddress($this->routeUri, mb_strtolower($this->method));

        $request = $this->createRequest($parameters, $cookies, $files, $server, $content);

        try {
            $routedRequestValidator->validate($address, $request);
        }
        // Route Exceptions
        catch (MultipleOperationsMismatchForRequest $e) {
            throw OpenAPIRouteMismatchException::fromMultipleOperationsMismatchForRequest($e);
        } catch (NoOperation $e) {
            throw OpenAPIRouteMismatchException::fromNoOperation($e);
        } catch (NoPath $e) {
            throw OpenAPIRouteMismatchException::fromNoPath($e);
        } catch (InvalidPath $e) {
            throw OpenAPIRouteMismatchException::fromInvalidPath($e);
        }
        // Request Exceptions
        catch (InvalidQueryArgs $e) {
            throw OpenAPIRequestMismatchException::fromInvalidQueryArgs($e);
        }

        // Generic Exceptions

        // Response Exceptions
    }

    public function validateResponse(Response $response): void
    {
        $psrResponse = $this->createPsrResponse($response);

        $address = new OperationAddress($this->routeUri, mb_strtolower($this->method));

        $responseValidator = $this->validatorBuilder->getResponseValidator();

        try {
            $responseValidator->validate($address, $psrResponse);
        } catch (InvalidBody $exception) {
            throw OpenAPIResponseMismatchException::fromInvalidBody($exception);
        }
    }

    /**
     * @param  array<array-key, mixed>  $parameters
     * @param  array<array-key, mixed>  $cookies
     * @param  array<array-key, mixed>  $files
     * @param  array<array-key, mixed>  $server
     * @param  string|null  $content
     */
    private function createRequest(array $parameters, array $cookies, array $files, array $server, $content): ServerRequestInterface
    {
        $request = Request::create($this->uri, $this->method, $parameters, $cookies, $files, $server, $content);

        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);

        return $psrHttpFactory->createRequest($request);
    }

    private function createPsrResponse(Response $response): ResponseInterface
    {
        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);

        return $psrHttpFactory->createResponse($response);
    }
}
