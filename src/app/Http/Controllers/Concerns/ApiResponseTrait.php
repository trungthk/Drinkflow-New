<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait chuẩn hóa cấu trúc phản hồi JSON (API Response) trên toàn bộ hệ thống DrinkFlow.
 */
trait ApiResponseTrait
{
    /**
     * Trả về phản hồi JSON thành công (Success Response).
     *
     * @param  mixed  $data  Dữ liệu trả về.
     * @param  string  $message  Thông điệp phản hồi.
     * @param  int  $code  HTTP status code (mặc định 200 OK).
     * @param  array<string, string>  $headers  HTTP headers bổ sung.
     * @return \Illuminate\Http\JsonResponse
     */
    protected function successResponse(
        mixed $data = null,
        string $message = '',
        int $code = Response::HTTP_OK,
        array $headers = []
    ): JsonResponse {
        $payload = ['success' => true];

        if ($message !== '') {
            $payload['message'] = $message;
        }

        if ($data !== null) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $code, $headers);
    }

    /**
     * Trả về phản hồi JSON khi tạo mới tài nguyên thành công (201 Created).
     *
     * @param  mixed  $data  Dữ liệu bản ghi vừa tạo.
     * @param  string  $message  Thông điệp phản hồi.
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createdResponse(mixed $data = null, string $message = ''): JsonResponse
    {
        return $this->successResponse($data, $message, Response::HTTP_CREATED);
    }

    /**
     * Trả về phản hồi JSON thông báo lỗi (Error Response).
     *
     * @param  string  $message  Nội dung thông báo lỗi.
     * @param  int  $code  HTTP status code (mặc định 400 Bad Request).
     * @param  mixed  $errors  Chi tiết lỗi kiểm tra (validation errors hoặc metadata).
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse(
        string $message,
        int $code = Response::HTTP_BAD_REQUEST,
        mixed $errors = null
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $code);
    }

    /**
     * Trả về phản hồi JSON cho dữ liệu phân trang (Pagination).
     *
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator  $paginator  Đối tượng phân trang.
     * @param  string  $message  Thông điệp bổ sung nếu có.
     * @return \Illuminate\Http\JsonResponse
     */
    protected function paginatedResponse(LengthAwarePaginator $paginator, string $message = ''): JsonResponse
    {
        $payload = [
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
        ];

        if ($message !== '') {
            $payload['message'] = $message;
        }

        return response()->json($payload, Response::HTTP_OK);
    }
}
