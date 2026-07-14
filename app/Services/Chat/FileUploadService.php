<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use finfo;

/**
 * Handles secure file uploads for chat messages.
 *
 * Security measures:
 * - Magic byte validation (not just MIME type)
 * - File size limits
 * - Restricted file types
 * - Storage outside web root
 * - Signed URLs for access
 */
final class FileUploadService
{
    /** @var string[] Allowed MIME types */
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
    ];

    /** @var string[] Allowed extensions */
    private const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'pdf', 'doc', 'docx', 'txt',
    ];

    /** @var int Max file size (10MB) */
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    /**
     * Map extensions to expected MIME types for cross-validation.
     *
     * @var array<string, string>
     */
    private const EXTENSION_TO_MIME = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'txt'  => 'text/plain',
    ];

    private MessageService $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    /**
     * Upload a file and create a message.
     *
     * @throws \InvalidArgumentException when validation fails
     */
    public function upload(
        Conversation $conversation,
        int $senderId,
        string $senderType,
        UploadedFile $file,
    ): Message {
        $this->validateFile($file);

        $filename = $this->generateFilename($file);

        // Store file securely outside the web root
        $path = $file->storeAs(
            "chat/{$conversation->conversation_id}",
            $filename,
            'private'
        );

        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();
        $isImage = $this->isImage($mimeType);

        $messageType = $isImage ? 'image' : 'file';
        $content = $isImage
            ? '[Image]'
            : "[File: {$file->getClientOriginalName()}]";

        return $this->messageService->send(
            $conversation,
            $senderId,
            $senderType,
            $content,
            $messageType,
            [
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'is_image'  => $isImage,
            ]
        );
    }

    /**
     * Get a signed temporary URL for file access.
     */
    public function getSignedUrl(string $path, int $expirationMinutes = 60): string
    {
        return Storage::disk('private')->temporaryUrl(
            $path,
            now()->addMinutes($expirationMinutes)
        );
    }

    /**
     * Delete a file from storage.
     */
    public function deleteFile(string $path): bool
    {
        return Storage::disk('private')->delete($path);
    }

    /**
     * Check if a file exists in storage.
     */
    public function fileExists(string $path): bool
    {
        return Storage::disk('private')->exists($path);
    }

    /**
     * Get the file size on disk in bytes.
     */
    public function getFileSize(string $path): ?int
    {
        if (! $this->fileExists($path)) {
            return null;
        }

        return Storage::disk('private')->size($path);
    }

    /**
     * Validate an uploaded file against all security checks.
     *
     * @throws \InvalidArgumentException when any validation rule fails
     */
    private function validateFile(UploadedFile $file): void
    {
        // 1. Reject empty uploads
        if ($file->getSize() === 0 || $file->getError() !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('File upload failed or is empty.');
        }

        // 2. Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException(
                'File size exceeds maximum limit of 10MB.'
            );
        }

        // 3. Check extension
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === '' || ! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new \InvalidArgumentException(
                'File type not allowed. Allowed types: ' . implode(', ', self::ALLOWED_EXTENSIONS)
            );
        }

        // 4. Magic byte validation — reads the actual file content, not user-supplied headers
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($file->getRealPath());

        if ($detectedMime === false || ! in_array($detectedMime, self::ALLOWED_MIME_TYPES, true)) {
            throw new \InvalidArgumentException(
                'File content does not match an allowed type.'
            );
        }

        // 5. Cross-validate MIME type matches the claimed extension
        $expectedMime = self::EXTENSION_TO_MIME[$extension] ?? null;

        if ($expectedMime !== null && $detectedMime !== $expectedMime) {
            throw new \InvalidArgumentException(
                "File content ({$detectedMime}) does not match extension ({$extension})."
            );
        }
    }

    /**
     * Generate a unique filename to prevent path traversal and collisions.
     */
    private function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();

        return Str::uuid() . '.' . $extension;
    }

    /**
     * Check if the given MIME type represents an image.
     */
    public function isImage(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'image/');
    }

    /**
     * Get allowed file extensions for display in the UI.
     *
     * @return string[]
     */
    public function getAllowedTypes(): array
    {
        return self::ALLOWED_EXTENSIONS;
    }

    /**
     * Get max file size in bytes.
     */
    public function getMaxFileSize(): int
    {
        return self::MAX_FILE_SIZE;
    }
}
