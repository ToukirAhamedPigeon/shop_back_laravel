<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ParseMultipartFormDataForPut
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        Log::info('ParseMultipartFormDataForPut middleware triggered', [
            'method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'path' => $request->path(),
            'url' => $request->fullUrl()
        ]);

        // Check if this is a PUT request with multipart/form-data
        if ($request->isMethod('PUT') && str_contains($request->header('Content-Type') ?? '', 'multipart/form-data')) {
            Log::info('Processing multipart/form-data for PUT request');

            try {
                // Parse the raw input
                $rawData = file_get_contents('php://input');
                $contentType = $request->header('Content-Type');
                $boundary = $this->getBoundary($contentType);

                Log::info('Raw data length: ' . strlen($rawData));
                Log::info('Boundary: ' . $boundary);

                if ($boundary) {
                    $parsedData = $this->parseMultipartData($rawData, $boundary);

                    Log::info('Parsed inputs keys:', array_keys($parsedData['inputs']));
                    Log::info('Parsed files keys:', array_keys($parsedData['files']));

                    // Merge the parsed data into the request
                    if (!empty($parsedData['inputs'])) {
                        $request->merge($parsedData['inputs']);
                    }

                    // Handle files
                    foreach ($parsedData['files'] as $key => $fileData) {
                        $request->files->set($key, $fileData);
                    }

                    Log::info('After merge - request all keys:', array_keys($request->all()));
                    Log::info('After merge - request files keys:', array_keys($request->allFiles()));
                }
            } catch (\Exception $e) {
                Log::error('Error parsing multipart data: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        return $next($request);
    }

    /**
     * Extract boundary from Content-Type header
     */
    private function getBoundary($contentType)
    {
        if (preg_match('/boundary=(.*)$/', $contentType, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Parse multipart form data
     */
    private function parseMultipartData($data, $boundary)
    {
        $parts = explode('--' . $boundary, $data);
        $inputs = [];
        $files = [];

        Log::info('Number of parts: ' . count($parts));

        foreach ($parts as $index => $part) {
            // Skip empty parts and the closing boundary
            if (empty($part) || trim($part) === '--' || trim($part) === '') {
                continue;
            }

            Log::info("Processing part {$index}", ['preview' => substr($part, 0, 100)]);

            // Parse each part - improved regex
            if (preg_match('/Content-Disposition: form-data; name="([^"]+)"(?:; filename="([^"]*)")?(?:\r\nContent-Type: ([^\r\n]+))?\r\n\r\n(.*?)(?:\r\n--|$)/s', $part, $matches)) {
                $name = $matches[1];
                $filename = $matches[2] ?? null;
                $contentType = $matches[3] ?? null;
                $content = rtrim($matches[4] ?? '', "\r\n");

                Log::info("Found field: {$name}", [
                    'has_filename' => !empty($filename),
                    'content_length' => strlen($content)
                ]);

                if (!empty($filename)) {
                    // This is a file
                    $tempPath = tempnam(sys_get_temp_dir(), 'php_');
                    file_put_contents($tempPath, $content);

                    $files[$name] = new UploadedFile(
                        $tempPath,
                        $filename,
                        $contentType,
                        null,
                        true // Mark as test file
                    );
                } else {
                    // This is a regular input
                    $inputs[$name] = $content;
                }
            }
        }

        return [
            'inputs' => $inputs,
            'files' => $files
        ];
    }
}
