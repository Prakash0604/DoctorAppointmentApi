<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function sendResponse($data = [], $message = 'Success', $code = 200)
    {
        return response()->json([
            'status' => true,
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function sendPaginatedResponse($data, $resource, $message = '', $status = 200)
    {
        if (count($data) === 0) {
            return $this->sendError('No data found');
        }
        return response()->json([
            'success' => true,
            'message' => $message,
            'status_code' => $status,
            'data' => $resource::collection($data),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'next_page_url' => $data->nextPageUrl(),
                'prev_page_url' => $data->previousPageUrl(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
                'path' => $data->path(),
            ]
        ], $status);
    }


    public function sendError($message = 'Error', $code = 400, $data = [])
    {
        return response()->json([
            'status' => false,
            'code' => $code,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    protected function paginateResponse($resource, $paginator, $message)
    {
        return $this->sendResponse([
            'data' => $resource,
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ]
        ], $message);
    }



    public function uploadfiles($file, $folder)
    {
        $filename = 'images/' . $folder . '/' . uniqid() . str_replace(' ', '', date('y-m-d-h-i-s') . '' . preg_replace('/\s+/', '', str_replace('%', '', $file->getClientOriginalName())));
        $file->move('images/' . $folder, $filename);
        // return str_replace('images/', '', $filename);
        return  $filename;
    }

    public function removePublicFile($file_path)
    {
        @unlink(public_path($file_path));
    }
}
