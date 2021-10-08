<?php
namespace App\Services\Storage;

use Illuminate\Support\Str;
use App\Services\Storage\Providers\Cloudinary\Cloudinary;

class Storage implements StorageInterface {
    protected $providers = [];
	protected $provider;
	
	public function __construct($provider = null)
	{
		$this->provider = $provider;
	}

    public function createCloudinaryProvider()
    {
        return new Cloudinary();
    }

    /**
     * Upload a file
     * 
     * @param Symfony\Component\HttpFoundation\File\UploadedFile $file
     * @param string $folder
     * 
     * @return array [storage_provider_id, url]
     */
    public function upload($file, $folder = '')
    {
        return $this->provider()->upload($file, $folder);
    }

    /**
     * Delete an uploaded file
     * 
     * @param string $storage_provider_id
     * 
     * @return bool
     */
    public function delete($storage_provider_id)
    {
        return $this->provider()->delete($storage_provider_id);
    }

    /**
	 * Get a provider instance.
	 *
	 * @param  string  $provider
	 * @return mixed
	 *
	 * @throws \InvalidArgumentException
	 */
	public function provider()
	{
		$provider = $this->provider;

		if (is_null($provider)) {
            throw new \InvalidArgumentException(sprintf(
                'Unable to resolve NULL driver for [%s].', static::class
            ));
		}

		if (! isset($this->providers[$provider])) {
			$this->providers[$provider] = $this->createProvider($provider);
		}

		return $this->providers[$provider];
	}

    /**
	 * Create a new provider instance.
	 *
	 * @param  string  $provider
	 * @return mixed
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function createProvider($provider)
	{

		$method = 'create' . Str::studly($provider) . 'Provider';

		if (method_exists($this, $method)) {
			return $this->$method();
		}

		throw new \InvalidArgumentException("Provider [$driver] not supported.");
	}
}