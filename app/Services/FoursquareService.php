<?php

namespace App\Services;

use App\Enums\ErrorCode;
use App\Support\Result;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FoursquareService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.foursquare.com/v3/places';

    public function __construct()
    {
        $this->apiKey = config('services.foursquare.key');
    }

    protected function prepareRequest(): \Illuminate\Http\Client\PendingRequest
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
        int $limit = 6,
        string $fields = 'fsq_id,name,categories,location,photos',
        ?int $radius = null
    ): Result {
        if (empty($this->apiKey)) {
            return Result::failure(ErrorCode::API_KEY_MISSING, 'Foursquare API key is not configured.');
        }

        $query = [
            'll' => $latitude.','.$longitude,
            'limit' => $limit,
            'fields' => $fields,
        ];

        if ($radius !== null) {
            $query['radius'] = $radius;
        }

        $endpoint = 'search';

        try {
            $response = $this->prepareRequest()->get($endpoint, $query);

            if (!$response->successful()) {
                return $this->handleApiError($response, 'searchPlaces', $query);
            }

            $results = $response->json('results', []);
            return Result::success($results);
        } catch (ConnectionException $e) {
            $context = ['error' => $e->getMessage()];
            Log::debug('FoursquareService@searchPlaces: ConnectionException caught. Returning failure.', [
                'query' => $query,
                'error_code' => ErrorCode::FOURSQUARE_CONNECTION_ERROR->value,
                'message' => 'Failed to connect to Foursquare API.',
                'context' => $context
            ]);
            return Result::failure(ErrorCode::FOURSQUARE_CONNECTION_ERROR, 'Failed to connect to Foursquare API.', $context);
        } catch (Throwable $e) {
            $context = ['error' => $e->getMessage()];
            Log::error('FoursquareService@searchPlaces request failed unexpectedly.', [
                'query' => $query,
                'exception' => $e
            ]);
            Log::debug('FoursquareService@searchPlaces: Throwable caught. Returning failure.', [
                'query' => $query,
                'error_code' => ErrorCode::UNEXPECTED_ERROR->value,
                'message' => 'An unexpected error occurred while communicating with Foursquare.',
                'context' => $context
            ]);
            return Result::failure(ErrorCode::UNEXPECTED_ERROR, 'An unexpected error occurred while communicating with Foursquare.', $context);
        }
    }

    public function getPlaceDetails(string $fsqId, string $fields = 'fsq_id,name,categories,location,photos'): Result
    {
        if (empty($this->apiKey)) {
            return Result::failure(ErrorCode::API_KEY_MISSING, 'Foursquare API key is not configured.');
        }

        if (empty($fsqId)) {
            return Result::failure(ErrorCode::BAD_REQUEST_ERROR, 'Foursquare ID cannot be empty.');
        }

        $query = ['fields' => $fields];
        $endpoint = $fsqId;

        try {
            $response = $this->prepareRequest()->get($endpoint, $query);

            if (!$response->successful()) {
                return $this->handleApiError($response, 'getPlaceDetails', ['fsq_id' => $fsqId] + $query);
            }

            $placeData = $response->json();
            if ($placeData) {
                return Result::success($placeData);
            }

            Log::warning('FoursquareService@getPlaceDetails received successful response but non-JSON body.', [
                'fsq_id' => $fsqId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return Result::failure(ErrorCode::FOURSQUARE_API_ERROR, 'Received invalid data format from Foursquare.', ['fsq_id' => $fsqId]);
        } catch (ConnectionException $e) {
            $context = ['error' => $e->getMessage()];
            Log::debug('FoursquareService@getPlaceDetails: ConnectionException caught. Returning failure.', [
                'fsq_id' => $fsqId,
                'query' => $query,
                'error_code' => ErrorCode::FOURSQUARE_CONNECTION_ERROR->value,
                'message' => 'Failed to connect to Foursquare API.',
                'context' => $context
            ]);
            return Result::failure(ErrorCode::FOURSQUARE_CONNECTION_ERROR, 'Failed to connect to Foursquare API.', $context);
        } catch (Throwable $e) {
            $context = ['error' => $e->getMessage()];
            Log::error('FoursquareService@getPlaceDetails request failed unexpectedly.', [
                'fsq_id' => $fsqId,
                'query' => $query,
                'exception' => $e
            ]);
            Log::debug('FoursquareService@getPlaceDetails: Throwable caught. Returning failure.', [
                'fsq_id' => $fsqId,
                'query' => $query,
                'error_code' => ErrorCode::UNEXPECTED_ERROR->value,
                'message' => 'An unexpected error occurred while communicating with Foursquare.',
                'context' => $context
            ]);
            return Result::failure(ErrorCode::UNEXPECTED_ERROR, 'An unexpected error occurred while communicating with Foursquare.', $context);
        }
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

        $errorCode = ($status >= 500) ? ErrorCode::FOURSQUARE_API_UNAVAILABLE : ErrorCode::FOURSQUARE_API_ERROR;
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
