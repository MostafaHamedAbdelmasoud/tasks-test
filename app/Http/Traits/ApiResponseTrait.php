<?php

namespace App\Http\Traits;

trait ApiResponseTrait
{
    protected function successResponse($data = null, $message = 'Success', $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function errorResponse($message = 'Error', $code = 400, $errors = null)
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    protected function validationErrorResponse($errors, $message = 'Validation failed', $code = 422)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    protected function notFoundResponse($message = 'Resource not found')
    {
        return $this->errorResponse($message, 404);
    }

    protected function unauthorizedResponse($message = 'Unauthorized')
    {
        return $this->errorResponse($message, 401);
    }

    protected function forbiddenResponse($message = 'Forbidden')
    {
        return $this->errorResponse($message, 403);
    }

    protected function createdResponse($data = null, $message = 'Resource created successfully')
    {
        return $this->successResponse($data, $message, 201);
    }

    protected function updatedResponse($data = null, $message = 'Resource updated successfully')
    {
        return $this->successResponse($data, $message);
    }

    protected function deletedResponse($message = 'Resource deleted successfully')
    {
        return $this->successResponse(null, $message);
    }
}