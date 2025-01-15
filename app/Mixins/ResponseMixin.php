<?php

namespace App\Mixins;

use Closure;
use Illuminate\Support\Facades\Response;
use stdClass;

class ResponseMixin
{
    /**
     * Returns a closure that generates a JSON response for errors.
     */
    public function errors(): Closure
    {
        return function ($message, $errors = [], $status = 422) {
            return Response::json([
                'success' => false,
                'status' => $status,
                'message' => $message,
                'errors' => empty($errors) ? new stdClass : $errors,
            ], $status);
        };
    }

    /**
     * Returns a closure that generates a JSON response for success.
     */
    public function success(): Closure
    {
        return function ($message = '', $data = [], $status = 200) {
            $data = empty($data) && is_object($message) || is_array($message) ? $message : $data;

            return Response::json([
                'success' => true,
                'status' => $status,
                'message' => is_string($message) ? $message : '',
                'data' => empty($data) ? new stdClass : $data,
            ], $status);
        };
    }

    /**
     * Returns a closure that generates a JSON response for paginated data.
     */
    public function paginate(): Closure
    {
        return function ($resource) {
            return Response::json([
                'success' => true,
                'status' => 200,
                'message' => '',
                'data' => $resource,
            ]);
        };
    }
}
