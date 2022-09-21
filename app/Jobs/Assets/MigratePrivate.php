<?php

namespace App\Jobs\Assets;

use App\Models\Asset;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MigratePrivate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $old_domain, $new_domain, $asset;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Asset $asset)
    {
        $this->old_domain = config('services.cloudfront.private_url');
        $this->new_domain = config('flok.private_media_url');
        $this->asset = $asset;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->updateItemUrl($this->asset);
        if ($this->asset->asset_type === 'video') {
            $this->updateItemContent($this->asset);

            foreach ($this->asset->resolutions as $resolution) {
                $this->updateItemUrl($resolution);
                $this->updateItemContent($resolution);
            }
        }
    }

    private function updateItemUrl($item)
    {
        $new_url = $this->getNewUrl($item->url);
        $item->url = $new_url;
        $item->save();
    }

    private function updateItemContent($item)
    {
        $new_content = $this->getNewM3u8Content($item->storage_provider_id);
        $this->deleteOldContent($item->storage_provider_id);
        $this->setNewContent($item->storage_provider_id, $new_content);
    }

    private function getNewM3u8Content($provider_id)
    {
        $old_contents = Storage::disk('private_s3')->get($provider_id);
        return str_replace($this->old_domain, $this->new_domain, $old_contents);
    }

    private function deleteOldContent($provider_id)
    {
        Storage::disk('private_s3')->delete($provider_id);
    }

    private function setNewContent($provider_id, $content)
    {
        Storage::disk('private_s3')->put($provider_id, $content);
    }

    private function getNewUrl($old_url)
    {
        return str_replace($this->old_domain, $this->new_domain, $old_url);
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
