<?php

namespace Grandwebdesign\Larafile;

use Illuminate\Support\Facades\Storage;
use Error;
use Illuminate\Http\File;
use Illuminate\Http\Resources\MissingValue;

class Larafile
{
    private $storage;

    /**
     * Larafile Class
     * 
     * @param string|\Illuminate\Http\UploadedFile $file File Object
     * @param ?string $folder Folder to upload the file to
     * @param ?string $fileName Name to save file as
     * @param ?bool $compress Compress the file when uploading
     */
    public function __construct(
        public $file,
        public $folder = null,
        public $fileName = null,
        public $compress = false
    )
    {
        $this->storage = Storage::disk('uploads');
        if (!$this->fileName && !is_string($this->file)) {
            $this->fileName = $this->file->getClientOriginalName() . '.' . $this->file->getClientOriginalExtension();
        }
    }

    /**
     * Upload file
     * 
     * @throws Error If cannot upload file
     * @return string
     */
    public function upload(): string
    {
        $file = $this->file;

        if ($this->compress) {
            try {
                $compressedFile = $this->compress();
                $file = new File($compressedFile);
            } catch (Error $e) {
                throw new Error($e->getMessage(), $e->getCode());
            }
        }

        try {
            $this->storage->putFileAs(
                $this->folder,
                $file,
                $this->fileName
            );
        } catch (Error $e) {
            throw new Error($e->getMessage(), $e->getCode());
        }

        $this->cleanTempFolder($compressedFile ?? null);

        return $this->folder . '/' . $this->fileName;
    }

    /**
     * Removes file from server
     * 
     * @throws Error If cannot delete file
     * @return bool
     */
    public function delete()
    {
        if (!$this->storage->exists($this->file))
        {
            return true;
        }

        try {
            $this->storage->delete($this->file);
        } catch (Error $e) {
            throw new Error($e->getMessage(), $e->getCode());
        }

        return true;
    }

    private function compress()
    {
        if (!config('larafile.tinify_key')) {
            throw new MissingValue('TINIFY_KEY environment variable is not set.', 422);
        }

        if (!in_array($this->file->getMimeType(), $this->allowedFileTypesToCompress())) {
            throw new Error('Cannot compress '. $this->file->getMimeType(), 422);
        }

        if (!is_dir(storage_path('app/public/temp'))) {
             mkdir(storage_path('app/public/temp'));
        }

        $tempFile = storage_path('app/public/temp/' . uniqid('temp-img-') . '.' . $this->file->getClientOriginalExtension());

        \Tinify\setKey(config('digitaloceanspaces.tinify_key'));
        \Tinify\fromBuffer(
            file_get_contents($this->file->getRealPath())
        )->toFile($tempFile);
        
        return $tempFile;
    }

    /**
     * Allowed mime types for compressing
     * 
     * @return array<string>
     */
    private function allowedFileTypesToCompress(): array
    {
        return [
            'image/jpeg',
            'image/gif',
            'image/png',
            'image/bmp',
            'image/svg+xml'
        ];
    }

    /**
     * Remove temporary file if compressing was done
     * 
     * @param ?string $compressedFile Compressed file path
     * 
     * @return void
     */
    private function cleanTempFolder(?string $compressedFile = null): void
    {
        if ($compressedFile) {
            if (file_exists($compressedFile)) {
                unlink($compressedFile);
            }
        }
    }
}