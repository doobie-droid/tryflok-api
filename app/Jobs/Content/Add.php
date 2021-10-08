<?php

namespace App\Jobs\Content;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Storage\Storage as Storage;
use Illuminate\Support\Facades\Storage as LaravelStorage;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Content;
use App\Models\Collection;
use App\Models\Language;
use App\Models\Price;
use App\Models\Category;
use App\Jobs\Content\GenerateResolutions\Cover as GenerateCoverResolutionsJob;
use App\Jobs\Content\GenerateResolutions\Video as GenerateVideoResolutionsJob;
use App\Jobs\Content\GenerateResolutions\Audio as GenerateAudioResolutionsJob;
use App\Jobs\Content\GenerateResolutions\Book as GenerateBookResolutionsJob;

class Add implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $data, $content;
    public $timeout = 1200;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //create content
        $content = Content::create([
            'public_id' => uniqid(rand()),
            'title' => $this->data['title'],
            'summary' => $this->data['summary'],
            'user_id' => $this->data['owner']->id,
            'type' => $this->data['type'],
            'language_id' => $this->data['language']->id,
            'is_available' => $this->data['is_available'],
            'show_only_in_collections' => $this->data['show_only_in_collections'],
        ]);

        if (!is_null($this->data['categories'])) {
            $categories = Category::whereIn('public_id', $this->data['categories'])->get();
        } else {
            $categories = [];
        }
        
        $this->content = $content;
        foreach ($categories as $category) {
            $content->categories()->attach($category->id);
        }

        //add price
        $content->prices()->create([
            'public_id' => uniqid(rand()),
            'amount' => $this->data['price'],
            'interval' => 'one-off',
            'currency' => 'USD',
        ]);
        //add benefctors
        foreach ($this->data['benefactors'] as $benefactorData) {
            $bUM = User::where('public_id', $benefactorData['public_id'])->first();
            $content->benefactors()->create([
                'user_id' => $bUM->id,
                'share' => $benefactorData['share'],
            ]);
        }

        //add parent collection
        if (array_key_exists('parent_collection', $this->data) && !is_null($this->data['parent_collection'])) {
            $parentCollection = Collection::where('public_id', $this->data['parent_collection'])->first();
            $parentCollection->contents()->attach($content->id);
        }


        //add cover if exists
        if (!is_null($this->data['cover_path']) && $this->data['cover_path'] != "") {
            GenerateCoverResolutionsJob::dispatch([
                'content' => $content,
                'filepath' => $this->data['cover_path'],
            ]);
        }
        //determine content type and add content to assets
        switch ($this->data['type']) {
            case "audio":
                try {
                    $response = $storage->upload($this->data['uploaded_file_path'], 'contents/' . $content->public_id . '/audio');
                    $file = new \Symfony\Component\HttpFoundation\File\File($this->data['uploaded_file_path']);
                    $content->assets()->create([
                        'public_id' => uniqid(rand()),
                        'storage_provider' => 'cloudinary',
                        'storage_provider_id' => $response['storage_provider_id'],
                        'url' => $response['url'],
                        'purpose' => 'audio-book',
                        'asset_type' => 'audio',
                        'mime_type' => $file->getMimeType(),
                    ]);
                    unlink($this->data['uploaded_file_path']);
                } catch (\Exception $exception) {
                    Log::error($exception);
                    unlink($this->data['uploaded_file_path']);
                    if (!is_null($content)) {
                        //Handle deletion of already uploaded assets
                        $storage = new Storage('cloudinary');
                        foreach ($content->assets as $asset) {
                            $storage->delete($asset->storage_provider_id);
                            $asset->delete();
                        }
                        $content->benefactors()->delete();
                        $content->prices()->delete();
                        $content->categories()->detach();
                        $content->forceDelete();
                    }
                }
               
                break;
            case "video":
                GenerateVideoResolutionsJob::dispatch([
                    'content' => $content,
                    'filepath' => $this->data['uploaded_file_path'],
                ]);
                break;
            case "book":
                GenerateBookResolutionsJob::dispatch([
                    'content' => $content,
                    'filepath' => $this->data['uploaded_file_path'],
                    'format' => $this->data['format'],
                ]);
                break;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
        unlink($this->data['uploaded_file_path']);
        //TO DO: mail the user telling them the upload failed
    }
}
