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
use App\Models\Language;
use App\Models\Price;
use App\Models\Category;
use App\Jobs\Content\GenerateResolutions\Cover as GenerateCoverResolutionsJob;
use App\Jobs\Content\GenerateResolutions\Video as GenerateVideoResolutionsJob;
use App\Jobs\Content\GenerateResolutions\Audio as GenerateAudioResolutionsJob;
use App\Jobs\Content\GenerateResolutions\Book as GenerateBookResolutionsJob;

class Edit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $data;
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
        if (!is_null($this->data['title'])) {
            $this->data['content']->title = $this->data['title'];
        }

        if (!is_null($this->data['summary'])) {
            $this->data['content']->summary = $this->data['summary'];
        }

        if (!is_null($this->data['type'])) {
            $this->data['content']->type = $this->data['type'];
        }

        if (!is_null($this->data['is_available'])) {
            $this->data['content']->is_available = $this->data['is_available'];
        }

        if (!is_null($this->data['show_only_in_collections'])) {
            $this->data['content']->show_only_in_collections = $this->data['show_only_in_collections'];
        }
        
        $this->data['content']->save();
        //update categories
        if (!is_null($this->data['categories'])) {
           //update categories
           foreach ($this->data['categories'] as $categoryData) {
                $category = Category::where('public_id', $categoryData['public_id'])->first();
                if ($categoryData['action'] === 'add') {
                    $this->data['content']->categories()->syncWithoutDetaching([$category->id]);
                }

                if ($categoryData['action'] === 'remove') {
                    $this->data['content']->categories()->detach($category->id);
                }
            }
        } 

        if (!is_null($this->data['price'])) {
            $price = $this->data['content']->prices()->first();
            if (is_null($price)) {
                $this->data['content']->prices()->create([
                    'public_id' => uniqid(rand()),
                    'amount' => $this->data['price'],
                    'interval' => 'one-off',
                    'currency' => 'USD',
                ]);
            } else {
                $price->amount = $this->data['price'];
                $price->save();
            }
        }
        //update the updated benefactors
        foreach ($this->data['benefactors_to_update'] as $benefactorData) {
            $benefactorData['benefactor']->share = $benefactorData['share'];
            $benefactorData['benefactor']->save();
        }
        //remove the removed benefactors
        foreach ($this->data['benefactors_to_delete'] as $benefactor) {
            $benefactor->delete();
        }
        //add the added benefactors
        foreach ($this->data['benefactors_to_add'] as $benefactorData) {
            $bUM = User::where('public_id', $benefactorData['public_id'])->first();
            $benefactor = $this->data['content']->benefactors()->where('user_id', $bUM->id)->first();
            if (is_null($benefactor)) {
                $this->data['content']->benefactors()->create([
                    'user_id' => $bUM->id,
                    'share' => $benefactorData['share'],
                ]);
            }
        }
         //add cover if exists
         $storage = new Storage('cloudinary');
         if (!is_null($this->data['cover_path']) && $this->data['cover_path'] != "") {
            $oldCover = $this->data['content']->assets()->where('purpose', 'cover')->first();
            if (!is_null($oldCover)) {
                $storage = new Storage('cloudinary');
                if ($oldCover->storage_provider === 'cloudinary') {
                    $storage->delete($oldCover->storage_provider_id);
                } else {
                    LaravelStorage::disk('public_s3')->delete($oldCover->storage_provider_id);
                }
                $oldCover->forceDelete();
            }
            GenerateCoverResolutionsJob::dispatch([
                'content' => $content,
                'filepath' => $this->data['cover_path'],
            ]);
        }

        //determine content type and add content to assets
        switch ($this->data['type']) {
            case "audio":
                if (!is_null($this->data['uploaded_file_path']) && $this->data['uploaded_file_path'] != "") {
                    $oldAssets = $this->data['content']->assets()->where(function ($query) {
                        $query->where('purpose', 'pdf-book-page')
                        ->orWhere('purpose', 'image-book-page')
                        ->orWhere('purpose', 'i360-book-page')
                        ->orWhere('purpose', 'audio-book')
                        ->orWhere('purpose', 'video');
                    })->get();
                    foreach ($oldAssets as $asset) {
                        $storage = new Storage('cloudinary');
                        if ($asset->storage_provider === 'cloudinary') {
                            $storage->delete($asset->storage_provider_id);
                        } else {
                            LaravelStorage::disk('public_s3')->delete($asset->storage_provider_id);
                        }
                        $asset->resolutions()->delete();
                        $asset->forceDelete();
                    }
                    GenerateAudioResolutionsJob::dispatch([
                        'content' => $content,
                        'filepath' => $this->data['uploaded_file_path'],
                    ]);
                }
            break;
            
            case "video":
                if (!is_null($this->data['uploaded_file_path']) && $this->data['uploaded_file_path'] != "") {
                    $oldAssets = $this->data['content']->assets()->where(function ($query) {
                        $query->where('purpose', 'pdf-book-page')
                        ->orWhere('purpose', 'image-book-page')
                        ->orWhere('purpose', 'i360-book-page')
                        ->orWhere('purpose', 'audio-book')
                        ->orWhere('purpose', 'video');
                    })->get();
                    foreach ($oldAssets as $asset) {
                        $storage = new Storage('cloudinary');
                        if ($asset->storage_provider === 'cloudinary') {
                            $storage->delete($asset->storage_provider_id);
                        } else {
                            LaravelStorage::disk('public_s3')->delete($asset->storage_provider_id);
                        }
                        $asset->resolutions()->delete();
                        $asset->forceDelete();
                    }
                    GenerateVideoResolutionsJob::dispatch([
                        'content' => $content,
                        'filepath' => $this->data['uploaded_file_path'],
                    ]);
                }
            break;

            case "book":
                if (!is_null($this->data['uploaded_file_path']) && $this->data['uploaded_file_path'] != "") {
                    $oldAssets = $this->data['content']->assets()->where(function ($query) {
                        $query->where('purpose', 'pdf-book-page')
                        ->orWhere('purpose', 'image-book-page')
                        ->orWhere('purpose', 'i360-book-page')
                        ->orWhere('purpose', 'audio-book')
                        ->orWhere('purpose', 'video');
                    })->get();
                    foreach ($oldAssets as $asset) {
                        $storage = new Storage('cloudinary');
                        if ($asset->storage_provider === 'cloudinary') {
                            $storage->delete($asset->storage_provider_id);
                        } else {
                            LaravelStorage::disk('public_s3')->delete($asset->storage_provider_id);
                        }
                        $asset->resolutions()->delete();
                        $asset->forceDelete();
                    }
                    GenerateBookResolutionsJob::dispatch([
                        'content' => $content,
                        'filepath' => $this->data['uploaded_file_path'],
                        'format' => $this->data['format'],
                    ]);
                }
            break;
        }

    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
        unlink($this->data['uploaded_file_path']);
        //TO DO: mail the user telling them the edit failed
    }
}
