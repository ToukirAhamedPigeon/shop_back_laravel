<?php

namespace Modules\Shared\Infrastructure\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class RemoteFileHelper
{
    private string $storageType;
    private string $remoteUrl;
    private string $authToken;

    public function __construct()
    {
        $this->storageType = config('filesystems.default_storage_type', 'local');
        $this->remoteUrl = config('filesystems.remote_storage_url', 'https://shopfiles.pigeonic.com');
        $this->authToken = config('filesystems.remote_storage_token', '');

        Log::info('RemoteFileHelper initialized', [
            'storage_type' => $this->storageType,
            'remote_url' => $this->remoteUrl,
            'has_token' => !empty($this->authToken)
        ]);
    }

    /**
     * Save file to remote storage
     */
    public function saveFile(?UploadedFile $file, string $subFolder = 'users', ?array $resizeOptions = null): ?string
    {
        if ($this->storageType === 'local') {
            Log::info('Using LOCAL storage');
            return FileHelper::saveFileLocal($file, $subFolder, $resizeOptions);
        }

        Log::info('Using REMOTE storage');

        if (!$file || !$file->isValid()) {
            return null;
        }

        // Validate file
        $this->validateFile($file);

        // Process image with resizing
        $options = $resizeOptions ?? FileHelper::$defaultResizeOptions;
        $processedImageData = $this->processImage($file, $options);

        $fileName = $processedImageData['fileName'];
        $imageData = $processedImageData['data'];

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->authToken,
            ])->attach('image', $imageData, $fileName)
              ->post($this->remoteUrl . '/api/upload.php', [
                  'folder' => $subFolder,
                  'fileName' => $fileName,
              ]);

            Log::info('Remote upload response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                $result = $response->json();
                if ($result && $result['success'] === true && !empty($result['url'])) {
                    Log::info('✅ Remote upload successful', ['url' => $result['url']]);
                    return $result['url'];
                }
            }

            throw new \Exception("Upload failed: " . $response->body());
        } catch (\Exception $e) {
            Log::error('Remote upload failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Delete file from remote storage
     */
    public function deleteFile(?string $filePath): void
    {
        if (empty($filePath)) {
            return;
        }

        if ($this->storageType === 'local') {
            FileHelper::deleteFileLocal($filePath);
            return;
        }

        try {
            // Extract folder and filename from URL
            $url = $filePath;
            if (!str_starts_with($filePath, 'http')) {
                $url = $this->remoteUrl . $filePath;
            }

            $parts = parse_url($url);
            $path = trim($parts['path'] ?? '', '/');
            $segments = explode('/', $path);

            if (count($segments) >= 3) {
                $folder = $segments[count($segments) - 2];
                $filename = $segments[count($segments) - 1];

                $response = Http::withHeaders([
                    'Authorization' => $this->authToken,
                ])->post($this->remoteUrl . '/api/delete.php', [
                    'folder' => $folder,
                    'fileName' => $filename,
                ]);

                if ($response->successful()) {
                    Log::info('Remote delete successful', ['folder' => $folder, 'filename' => $filename]);
                } else {
                    Log::warning('Remote delete failed', ['response' => $response->body()]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Remote delete error', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Process image with resizing
     */
    private function processImage(UploadedFile $file, array $options): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $fileName = Str::uuid()->toString() . '.' . $extension;

        // If resize is disabled or not an image, return original file
        if (!$options['enabled'] || !str_starts_with($file->getMimeType(), 'image/')) {
            return [
                'fileName' => $fileName,
                'data' => file_get_contents($file->getRealPath())
            ];
        }

        try {
            $image = Image::read($file->getRealPath());
            $width = $image->width();
            $height = $image->height();

            // Resize if needed
            if ($width > $options['maxWidth'] || $height > $options['maxHeight']) {
                $ratio = min($options['maxWidth'] / $width, $options['maxHeight'] / $height);
                $newWidth = (int)($width * $ratio);
                $newHeight = (int)($height * $ratio);

                $image->scale($newWidth, $newHeight);
            }

            // Encode to appropriate format
            $quality = 90;
            $imageData = null;

            if ($extension === 'png') {
                $imageData = (string) $image->encodeByExtension('png', quality: $quality);
            } elseif (in_array($extension, ['jpg', 'jpeg'])) {
                $imageData = (string) $image->encodeByExtension('jpg', quality: $quality);
            } elseif ($extension === 'webp') {
                $imageData = (string) $image->encodeByExtension('webp', quality: $quality);
            } else {
                $imageData = (string) $image->encodeByExtension('png', quality: $quality);
            }

            return [
                'fileName' => $fileName,
                'data' => $imageData
            ];
        } catch (\Exception $e) {
            Log::warning('Image processing failed, using original', ['error' => $e->getMessage()]);
            return [
                'fileName' => $fileName,
                'data' => file_get_contents($file->getRealPath())
            ];
        }
    }

    /**
     * Validate uploaded file
     */
    private function validateFile(UploadedFile $file): void
    {
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file->getSize() > $maxSize) {
            throw new \Exception('File size must be less than 5MB');
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            throw new \Exception('Only JPG, PNG, WEBP images are allowed');
        }
    }
}
