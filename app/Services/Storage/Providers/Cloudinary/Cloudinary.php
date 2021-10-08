<?php
namespace App\Services\Storage\Providers\Cloudinary;

use App\Services\Storage\StorageInterface;

class Cloudinary implements StorageInterface {
    /**
     * Upload a file
     * 
     * @param string $path
     * @param string $folder
     * @param array $options
     * 
     * @return array [storage_provider_id, url]
     */
    public function upload($path, $folder = '', $options = [])
    {
        $options = array_merge($options, ['folder' => $folder]);
        $response = cloudinary()->uploadFile($path, $options)->getResponse();

        return [
            'storage_provider_id' => $response['public_id'],
            'url' => $response['secure_url'],
        ];
    }

    /**
     * Delete an uploaded file
     * 
     * @param string $storage_provider_id
     * 
     * @return bool
     */
    public function delete($storage_provider_id, $options = [])
    {
        $reponse = cloudinary()->destroy($storage_provider_id, $options);
        return true;
    }
}