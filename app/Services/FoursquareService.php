<?php

namespace App\Services;

use App\Contracts\Services\ExternalPlaceSearchInterface;
use App\Enums\ErrorCode;
use App\Support\Result;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FoursquareService implements ExternalPlaceSearchInterface
{
    protected function prepareRequest(): PendingRequest
    {
        $apiKey = config('foursquare.api_key');
        if (empty($apiKey)) {
            throw new \RuntimeException('Foursquare API key is not configured.');
        }

        $apiKey = config('foursquare.api_key');
        $baseUrl = config('foursquare.base_url');
        return Http::timeout(10)
            ->baseUrl($baseUrl)
            ->withHeaders([
                'Authorization' => $apiKey,
                'Accept' => 'application/json',
            ]);
    }

    public function searchPlaces(
        float $latitude,
        float $longitude
    ): Result {
        $limit = config('foursquare.search_limit');
        $fields = 'fsq_id,name,categories,location,photos';

        $queryParams = [
            'll' => $latitude.','.$longitude,
            'limit' => $limit,
            'fields' => $fields,
        ];
        ksort($queryParams);
        $cacheKey = 'foursquare_search_'.md5(json_encode($queryParams));
        $endpoint = 'search';

        $ttl = config('foursquare.cache_ttl');
        return Cache::remember($cacheKey, $ttl, function () use ($endpoint, $queryParams) {
            try {
                $response = $this->prepareRequest()->get($endpoint, $queryParams);

                if (!$response->successful()) {
                    return $this->handleApiError($response, 'searchPlaces', $queryParams);
                }

                $results = $response->json('results', []);
                return Result::success($results);
            } catch (ConnectionException $e) {
                $context = ['error' => $e->getMessage()];
                Log::error('FoursquareService@searchPlaces: ConnectionException caught.', [
                    'query' => $queryParams,
                    'exception' => $e
                ]);
                return Result::failure(ErrorCode::FOURSQUARE_CONNECTION_ERROR, 'Failed to connect to Foursquare API.', $context);
            } catch (Throwable $e) {
                $context = ['error' => $e->getMessage()];
                Log::error('FoursquareService@searchPlaces request failed unexpectedly.', [
                    'query' => $queryParams,
                    'exception' => $e
                ]);
                return Result::failure(ErrorCode::UNEXPECTED_ERROR, 'An unexpected error occurred while communicating with Foursquare.', $context);
            }
        });
    }

    public function getPlaceDetails(string $fsqId): Result
    {
        $fields = 'fsq_id,name,categories,location,photos';
        if (empty($fsqId)) {
            return Result::failure(ErrorCode::BAD_REQUEST_ERROR, 'Foursquare ID cannot be empty.');
        }

        $queryParams = ['fields' => $fields];
        $endpoint = $fsqId;
        $cacheKey = 'foursquare_details_'.md5($fsqId.'_'.$fields);
        $ttl = config('foursquare.cache_ttl');

        return Cache::remember($cacheKey, $ttl, function () use ($endpoint, $queryParams, $fsqId) {
            try {
                $response = $this->prepareRequest()->get($endpoint, $queryParams);

                if (!$response->successful()) {
                    return $this->handleApiError($response, 'getPlaceDetails', ['fsq_id' => $fsqId] + $queryParams);
                }

                $placeData = $response->json();
                if ($placeData) {
                    return Result::success($placeData);
                }

                Log::warning('FoursquareService@getPlaceDetails received successful response but invalid/empty body.', [
                    'fsq_id' => $fsqId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return Result::failure(ErrorCode::FOURSQUARE_API_ERROR, 'Received invalid data format from Foursquare.', ['fsq_id' => $fsqId]);
            } catch (ConnectionException $e) {
                $context = ['error' => $e->getMessage()];
                Log::error('FoursquareService@getPlaceDetails: ConnectionException caught.', [
                    'fsq_id' => $fsqId,
                    'query' => $queryParams,
                    'exception' => $e
                ]);
                return Result::failure(ErrorCode::FOURSQUARE_CONNECTION_ERROR, 'Failed to connect to Foursquare API.', $context);
            } catch (Throwable $e) {
                $context = ['error' => $e->getMessage()];
                Log::error('FoursquareService@getPlaceDetails request failed unexpectedly.', [
                    'fsq_id' => $fsqId,
                    'query' => $queryParams,
                    'exception' => $e
                ]);
                return Result::failure(ErrorCode::UNEXPECTED_ERROR, 'An unexpected error occurred while communicating with Foursquare.', $context);
            }
        });
    }

    protected function handleApiError(Response $response, string $methodContext, array $requestData): Result
    {
        $status = $response->status();
        $body = $response->json() ?? $response->body();

        $logContext = [
            'status' => $status,
            'response_body' => $body,
            'request_data' => $requestData,
        ];
        Log::error("FoursquareService@{$methodContext} API request failed.", $logContext);

        $errorMessage = 'Foursquare API request failed.';
        if (is_array($body) && isset($body['message'])) {
            $errorMessage = $body['message'];
        }

        $errorCode = ErrorCode::FOURSQUARE_API_ERROR;
        if ($status >= 500) {
            $errorCode = ErrorCode::FOURSQUARE_API_UNAVAILABLE;
        }

        $context = ['api_status' => $status, 'api_response' => $body];

        Log::debug('FoursquareService::handleApiError returning failure.', [
            'method' => $methodContext,
            'error_code' => $errorCode->value,
            'message' => $errorMessage,
            'context' => $context
        ]);

        return Result::failure($errorCode, $errorMessage, $context);
    }
}
