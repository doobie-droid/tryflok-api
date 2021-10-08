<?php

namespace App\Services\Storage;

interface StorageInterface {
    /**
     * Upload a file
     * 
     * @param Symfony\Component\HttpFoundation\File\UploadedFile $file
     * @param string $folder
     * 
     * @return array [storage_provider_id, url]
     */
    public function upload($file, $folder = '');

    /**
     * Delete an uploaded file
     * 
     * @param string $storage_provider_id
     * 
     * @return bool
     */
    public function delete($storage_provider_id);
}