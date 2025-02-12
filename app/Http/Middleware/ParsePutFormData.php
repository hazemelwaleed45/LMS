<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ParsePutFormData
{
    public function handle(Request $request, Closure $next)
    {
        // Check if the request is a PUT and has form-data
        if ($request->isMethod('put') && $request->header('Content-Type') === 'multipart/form-data') {
            // Parse the raw input data
            $data = [];
            parse_str(file_get_contents('php://input'), $data);

            // Merge the parsed data into the request
            $request->merge($data);

            // Handle file uploads if present
            if (!empty($_FILES)) {
                $request->files->replace($_FILES);
            }
        }

        return $next($request);
    }
}
