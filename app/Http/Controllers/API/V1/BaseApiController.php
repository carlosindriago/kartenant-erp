<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Base API Controller
 *
 * Provides standard response methods for all API controllers.
 * All API v1 controllers should extend this class.
 */
class BaseApiController extends Controller
{
    /**
     * Return success response
     *
     * @param  mixed  $data
     */
    protected function successResponse($data = null, ?string $message = null, int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
        ];

        if ($data !== null) {
            // Handle Laravel API Resources
            if ($data instanceof JsonResource || $data instanceof ResourceCollection) {
                $response['data'] = $data->resolve();
            } else {
                $response['data'] = $data;
            }
        }

        if ($message) {
            $response['message'] = $message;
        }

        $response['meta'] = [
            'timestamp' => now()->toIso8601String(),
            'version' => '2.0.0',
        ];

        return response()->json($response, $statusCode);
    }

    /**
     * Return success response with pagination
     */
    protected function successResponseWithPagination(ResourceCollection $collection, ?string $message = null): JsonResponse
    {
        $data = $collection->resolve();
        $paginator = $collection->resource;

        $response = [
            'success' => true,
            'data' => $data,
        ];

        if ($message) {
            $response['message'] = $message;
        }

        $response['meta'] = [
            'current_page' => $paginator->currentPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'per_page' => $paginator->perPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'path' => $paginator->path(),
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
            'timestamp' => now()->toIso8601String(),
            'version' => '2.0.0',
        ];

        return response()->json($response, 200);
    }

    /**
     * Return error response
     */
    protected function errorResponse(
        string $message,
        string $code = 'ERROR',
        ?array $details = null,
        int $statusCode = 400
    ): JsonResponse {
        $response = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];

        if ($details) {
            $response['error']['details'] = $details;
        }

        $response['meta'] = [
            'timestamp' => now()->toIso8601String(),
        ];

        return response()->json($response, $statusCode);
    }

    /**
     * Return validation error response
     */
    protected function validationErrorResponse(
        array $errors,
        string $message = 'Los datos proporcionados no son válidos'
    ): JsonResponse {
        return $this->errorResponse(
            message: $message,
            code: 'VALIDATION_ERROR',
            details: $errors,
            statusCode: 422
        );
    }

    /**
     * Return not found error response
     */
    protected function notFoundResponse(string $message = 'Recurso no encontrado'): JsonResponse
    {
        return $this->errorResponse(
            message: $message,
            code: 'NOT_FOUND',
            statusCode: 404
        );
    }

    /**
     * Return unauthorized error response
     */
    protected function unauthorizedResponse(string $message = 'No autorizado'): JsonResponse
    {
        return $this->errorResponse(
            message: $message,
            code: 'UNAUTHORIZED',
            statusCode: 401
        );
    }

    /**
     * Return forbidden error response
     */
    protected function forbiddenResponse(string $message = 'Acceso prohibido'): JsonResponse
    {
        return $this->errorResponse(
            message: $message,
            code: 'FORBIDDEN',
            statusCode: 403
        );
    }

    /**
     * Return created response
     *
     * @param  mixed  $data
     */
    protected function createdResponse($data, string $message = 'Recurso creado exitosamente'): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Return no content response (for deletes)
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
