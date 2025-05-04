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
    private const DEFAULT_FIELDS = 'fsq_id,name,categories,location,photos';
    private const DEFAULT_LIMIT = 6;
    private int $cacheTtl = 604800; // 1 week in seconds

    protected string $apiKey;
    protected string $baseUrl = 'https://api.foursquare.com/v3/places';

    public function __construct()
    {
        $this->apiKey = config('services.foursquare.key');
    }

    protected function prepareRequest(): PendingRequest
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('Foursquare API key is not configured.');
        }

        return Http::timeout(10)
            ->baseUrl($this->baseUrl)
            ->withHeaders([
                'Authorization' => $this->apiKey,
                'Accept' => 'application/json',
            ]);
    }

    public function searchPlaces(
        float $latitude,
        float $longitude,
        int $limit = self::DEFAULT_LIMIT,
        string $fields = self::DEFAULT_FIELDS,
        ?int $radius = null
    ): Result {
        $queryParams = [
            'll' => $latitude.','.$longitude,
            'limit' => $limit,
            'fields' => $fields,
        ];
        if ($radius !== null) {
            $queryParams['radius'] = $radius;
        }
        ksort($queryParams);
        $cacheKey = 'foursquare_search_' . md5(json_encode($queryParams));
        $endpoint = 'search';

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($endpoint, $queryParams) {
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

    public function getPlaceDetails(string $fsqId, string $fields = self::DEFAULT_FIELDS): Result
    {
        if (empty($fsqId)) {
            return Result::failure(ErrorCode::BAD_REQUEST_ERROR, 'Foursquare ID cannot be empty.');
        }

        $queryParams = ['fields' => $fields];
        $endpoint = $fsqId;
        $cacheKey = 'foursquare_details_' . md5($fsqId . '_' . $fields);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($endpoint, $queryParams, $fsqId) {
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
