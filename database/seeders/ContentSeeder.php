<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\Content;
use App\Models\Userable;
use Illuminate\Database\Seeder;
use Tests\MockData\Collection as MockCollection;
use Tests\MockData\Content as MockContent;

class ContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /**
         * Add Content
         * 
         */
        $unpaidOneOffVideoContent = Content::create(MockContent::SEEDED_UNPAID_ONE_OFF_VIDEO);
        //add benefactors 
        $unpaidOneOffVideoContent->benefactors()->createMany([
            [
                'user_id' => 4,
                'share' => 60,
            ],
            [
                'user_id' => 6,
                'share' => 20,
            ],
            [
                'user_id' => 7,
                'share' => 20,
            ],
        ]);
        //add asset
        $unpaidOneOffVideoContent->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => 'sdsdsd-weowpe',
            'url' => 'https://res.cloudinary.com/akiddie/video/upload/v1617731065/contents/826963893606c9de73bc36/video/e8n3rzrgikt7erqbwtrg.mp4',
            'purpose' => 'video',
            'asset_type' => 'video',
            'mime_type' => 'video/mp4',
        ]);
        //add cover
        $unpaidOneOffVideoContent->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => '2229-soperl',
            'url' => 'https://res.cloudinary.com/akiddie/image/upload/v1617731061/contents/826963893606c9de73bc36/cover/ldvsubjoaqicmg5htjep.png',
            'purpose' => 'cover',
            'asset_type' => 'image',
            'mime_type' => 'image/png',
        ]);
        //add prices
        $unpaidOneOffVideoContent->prices()->create([
            'public_id' => '826963893606c9de73bc3p',
            'amount' => 2000,
            'interval' => 'one-off',
            'currency' => 'USD',
        ]);

        $unpaidSubAudioContent = Content::create(MockContent::SEEDED_UNPAID_SUBSCRIPTION_AUDIO);
        //add benefactors 
        $unpaidSubAudioContent->benefactors()->createMany([
            [
                'user_id' => 4,
                'share' => 60,
            ],
            [
                'user_id' => 6,
                'share' => 20,
            ],
            [
                'user_id' => 7,
                'share' => 20,
            ],
        ]);
        //add asset
        $unpaidSubAudioContent->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => 'sdsdsd-weowpe',
            'url' => 'https://res.cloudinary.com/akiddie/video/upload/v1617732523/contents/678606098606ca378009e0/audio/iznirhejvkmfhonreoex.mp3',
            'purpose' => 'audio-book',
            'asset_type' => 'audio',
            'mime_type' => 'audio/mpeg',
        ]);
        //add cover
        $unpaidSubAudioContent->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => '2229-soperl',
            'url' => 'https://res.cloudinary.com/akiddie/image/upload/v1617731061/contents/826963893606c9de73bc36/cover/ldvsubjoaqicmg5htjep.png',
            'purpose' => 'cover',
            'asset_type' => 'image',
            'mime_type' => 'image/png',
        ]);
        //add prices
        $unpaidSubAudioContent->prices()->create([
            'public_id' => '678606098606ca378009ep',
            'amount' => 2000,
            'interval' => 'month',
            'interval_amount' => 1,
            'currency' => 'USD',
        ]);
        
        $unpaidPdfBookContent = Content::create(MockContent::SEEDED_UNPAID_PDF_BOOK);
        //add benefactors 
        $unpaidPdfBookContent->benefactors()->createMany([
            [
                'user_id' => 4,
                'share' => 60,
            ],
            [
                'user_id' => 6,
                'share' => 20,
            ],
            [
                'user_id' => 7,
                'share' => 20,
            ],
        ]);
        //add assets
        $unpaidPdfBookContent->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => 'sdsdsd-weowpe',
            'url' => 'https://res.cloudinary.com/akiddie/image/upload/v1617351790/contents/18240891946066d41d60016/pdf/cgtststmokocjespehd6.pdf',
            'purpose' => 'pdf-book-page',
            'asset_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'page' => 1,
        ]);
        $unpaidPdfBookContent->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => 'sdsdsd-weowpe',
            'url' => 'https://res.cloudinary.com/akiddie/image/upload/v1617351795/contents/18240891946066d41d60016/pdf/yzauemgy8sj5tooklhyq.pdf',
            'purpose' => 'pdf-book-page',
            'asset_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'page' => 2,
        ]);
        //add cover
        $unpaidPdfBookContent->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => '2229-soperl',
            'url' => 'https://res.cloudinary.com/akiddie/image/upload/v1617731061/contents/826963893606c9de73bc36/cover/ldvsubjoaqicmg5htjep.png',
            'purpose' => 'cover',
            'asset_type' => 'image',
            'mime_type' => 'image/png',
        ]);
        //add prices
        $unpaidPdfBookContent->prices()->create([
            'public_id' => '18240891946066d41d6001p',
            'amount' => 2000,
            'interval' => 'one-off',
            'currency' => 'USD',
        ]);

        $unpaidImageBookContent = Content::create(MockContent::SEEDED_UNPAID_IMAGE_BOOK);
        //add benefactors 
        $unpaidImageBookContent->benefactors()->createMany([
            [
                'user_id' => 4,
                'share' => 60,
            ],
            [
                'user_id' => 6,
                'share' => 20,
            ],
            [
                'user_id' => 7,
                'share' => 20,
            ],
        ]);
        //add assets
        $unpaidImageBookContent->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => 'sdsdsd-weowpe',
            'url' => 'https://res.cloudinary.com/akiddie/image/upload/v1617351886/contents/14517881306066d47a53205/2d-image/vxcv8euujozrmyhr18pc.jpg',
            'purpose' => 'image-book-page',
            'asset_type' => 'image',
            'mime_type' => 'image/jpeg',
            'page' => 1,
        ]);
        $unpaidImageBookContent->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => 'sdsdsd-weowpe',
            'url' => 'https://res.cloudinary.com/akiddie/image/upload/v1617351889/contents/14517881306066d47a53205/2d-image/c6fl14rxytwwzfso6ptt.jpg',
            'purpose' => 'image-book-page',
            'asset_type' => 'image',
            'mime_type' => 'image/jpeg',
            'page' => 2,
        ]);

        $unpaidImageBookContent->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => 'sdsdsd-weowpe',
            'url' => 'https://res.cloudinary.com/akiddie/image/upload/v1617351892/contents/14517881306066d47a53205/2d-image/dee9y2gz9bkf6nzwnwwp.jpg',
            'purpose' => 'image-book-page',
            'asset_type' => 'image',
            'mime_type' => 'image/jpeg',
            'page' => 3,
        ]);
        //add cover
        $unpaidImageBookContent->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => '2229-soperl',
            'url' => 'https://res.cloudinary.com/akiddie/image/upload/v1617731061/contents/826963893606c9de73bc36/cover/ldvsubjoaqicmg5htjep.png',
            'purpose' => 'cover',
            'asset_type' => 'image',
            'mime_type' => 'image/png',
        ]);
        
        //add prices
        $unpaidImageBookContent->prices()->create([
            'public_id' => '14517881306066d47a5320p',
            'amount' => 2000,
            'interval' => 'one-off',
            'currency' => 'USD',
        ]);

        /**
         * Add Collection
         * 
         */
        $collection = Collection::create(MockCollection::SEEDED_UNPAID_COLLECTION);
        //add benefactors 
        $collection->benefactors()->createMany([
            [
                'user_id' => 4,
                'share' => 60,
            ],
            [
                'user_id' => 6,
                'share' => 20,
            ],
            [
                'user_id' => 7,
                'share' => 20,
            ],
        ]);
        //add cover
        $collection->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => '2229-soperl',
            'url' => 'https://res.cloudinary.com/akiddie/image/upload/v1617731061/contents/826963893606c9de73bc36/cover/ldvsubjoaqicmg5htjep.png',
            'purpose' => 'cover',
            'asset_type' => 'image',
            'mime_type' => 'image/png',
        ]);
        //add prices
        $collection->prices()->create([
            'public_id' => '1867378820606c9e5ab27cp',
            'amount' => 2000,
            'interval' => 'month',
            'interval_amount' => 1,
            'currency' => 'USD',
        ]);
        //add content
        $collection->contents()->attach([1,2,3,4]);

        //seed the userables
        Userable::create([
            'user_id' => 3,
            'userable_type' => 'content',
            'userable_id' => 1,
            'status' => 'available',
        ]);

        Userable::create([
            'user_id' => 3,
            'userable_type' => 'content',
            'userable_id' => 2,
            'status' => 'available',
        ]);

        Userable::create([
            'user_id' => 3,
            'userable_type' => 'content',
            'userable_id' => 3,
            'status' => 'wishlist',
        ]);

        Userable::create([
            'user_id' => 3,
            'userable_type' => 'collection',
            'userable_id' => 1,
            'status' => 'available',
        ]);
        //add the children of the collection to the userable
        Userable::create([
            'user_id' => 3,
            'parent_id' => 4,
            'userable_type' => 'content',
            'userable_id' => 1,
            'status' => 'available',
        ]);
        Userable::create([
            'user_id' => 3,
            'parent_id' => 4,
            'userable_type' => 'content',
            'userable_id' => 2,
            'status' => 'available',
        ]);
        Userable::create([
            'user_id' => 3,
            'parent_id' => 4,
            'userable_type' => 'content',
            'userable_id' => 3,
            'status' => 'available',
        ]);
        Userable::create([
            'user_id' => 3,
            'parent_id' => 4,
            'userable_type' => 'content',
            'userable_id' => 4,
            'status' => 'available',
        ]);

        //unpaid entities
        $unpaidOneOffVideoContent = Content::create(MockContent::SEEDED_UNPAID_ONE_OFF_VIDEO_2);
        //add benefactors 
        $unpaidOneOffVideoContent->benefactors()->createMany([
            [
                'user_id' => 4,
                'share' => 60,
            ],
            [
                'user_id' => 6,
                'share' => 20,
            ],
            [
                'user_id' => 7,
                'share' => 20,
            ],
        ]);
        //add asset
        $unpaidOneOffVideoContent->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => 'sdsdsd-weowpeu',
            'url' => 'https://res.cloudinary.com/akiddie/video/upload/v1617731065/contents/826963893606c9de73bc36/video/e8n3rzrgikt7erqbwtrg.mp4',
            'purpose' => 'video',
            'asset_type' => 'video',
            'mime_type' => 'video/mp4',
        ]);
        //add cover
        $unpaidOneOffVideoContent->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => '2229-soperlu',
            'url' => 'https://res.cloudinary.com/akiddie/image/upload/v1617731061/contents/826963893606c9de73bc36/cover/ldvsubjoaqicmg5htjep.png',
            'purpose' => 'cover',
            'asset_type' => 'image',
            'mime_type' => 'image/png',
        ]);
        //add prices
        $unpaidOneOffVideoContent->prices()->create([
            'public_id' => '826963893606c9de73bc3pu',
            'amount' => 2000,
            'interval' => 'one-off',
            'currency' => 'USD',
        ]);

        $unpaidSubAudioContent = Content::create(MockContent::SEEDED_UNPAID_SUBSCRIPTION_AUDIO2);
        //add benefactors 
        $unpaidSubAudioContent->benefactors()->createMany([
            [
                'user_id' => 4,
                'share' => 60,
            ],
            [
                'user_id' => 6,
                'share' => 20,
            ],
            [
                'user_id' => 7,
                'share' => 20,
            ],
        ]);
        //add asset
        $unpaidSubAudioContent->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => 'sdsdsd-weowpeu',
            'url' => 'https://res.cloudinary.com/akiddie/video/upload/v1617732523/contents/678606098606ca378009e0/audio/iznirhejvkmfhonreoex.mp3',
            'purpose' => 'audio-book',
            'asset_type' => 'audio',
            'mime_type' => 'audio/mpeg',
        ]);
        //add cover
        $unpaidSubAudioContent->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => '2229-soperlu',
            'url' => 'https://res.cloudinary.com/akiddie/image/upload/v1617731061/contents/826963893606c9de73bc36/cover/ldvsubjoaqicmg5htjep.png',
            'purpose' => 'cover',
            'asset_type' => 'image',
            'mime_type' => 'image/png',
        ]);
        //add prices
        $unpaidSubAudioContent->prices()->create([
            'public_id' => '678606098606ca378009epu',
            'amount' => 2000,
            'interval' => 'month',
            'interval_amount' => 1,
            'currency' => 'USD',
        ]);
        
        $unpaidPdfBookContent = Content::create(MockContent::SEEDED_UNPAID_PDF_BOOK2);
        //add benefactors 
        $unpaidPdfBookContent->benefactors()->createMany([
            [
                'user_id' => 4,
                'share' => 60,
            ],
            [
                'user_id' => 6,
                'share' => 20,
            ],
            [
                'user_id' => 7,
                'share' => 20,
            ],
        ]);
        //add assets
        $unpaidPdfBookContent->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => 'sdsdsd-weowpeu',
            'url' => 'https://res.cloudinary.com/akiddie/image/upload/v1617351790/contents/18240891946066d41d60016/pdf/cgtststmokocjespehd6.pdf',
            'purpose' => 'pdf-book-page',
            'asset_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'page' => 1,
        ]);
        $unpaidPdfBookContent->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => 'sdsdsd-weowpe',
            'url' => 'https://res.cloudinary.com/akiddie/image/upload/v1617351795/contents/18240891946066d41d60016/pdf/yzauemgy8sj5tooklhyq.pdf',
            'purpose' => 'pdf-book-page',
            'asset_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'page' => 2,
        ]);
        //add cover
        $unpaidPdfBookContent->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => '2229-soperlu',
            'url' => 'https://res.cloudinary.com/akiddie/image/upload/v1617731061/contents/826963893606c9de73bc36/cover/ldvsubjoaqicmg5htjep.png',
            'purpose' => 'cover',
            'asset_type' => 'image',
            'mime_type' => 'image/png',
        ]);
        //add prices
        $unpaidPdfBookContent->prices()->create([
            'public_id' => '18240891946066d41d6001pu',
            'amount' => 2000,
            'interval' => 'one-off',
            'currency' => 'USD',
        ]);

        $unpaidImageBookContent = Content::create(MockContent::SEEDED_UNPAID_IMAGE_BOOK2);
        //add benefactors 
        $unpaidImageBookContent->benefactors()->createMany([
            [
                'user_id' => 4,
                'share' => 60,
            ],
            [
                'user_id' => 6,
                'share' => 20,
            ],
            [
                'user_id' => 7,
                'share' => 20,
            ],
        ]);
        //add assets
        $unpaidImageBookContent->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => 'sdsdsd-weowpeu',
            'url' => 'https://res.cloudinary.com/akiddie/image/upload/v1617351886/contents/14517881306066d47a53205/2d-image/vxcv8euujozrmyhr18pc.jpg',
            'purpose' => 'image-book-page',
            'asset_type' => 'image',
            'mime_type' => 'image/jpeg',
            'page' => 1,
        ]);
        $unpaidImageBookContent->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => 'sdsdsd-weowpeu',
            'url' => 'https://res.cloudinary.com/akiddie/image/upload/v1617351889/contents/14517881306066d47a53205/2d-image/c6fl14rxytwwzfso6ptt.jpg',
            'purpose' => 'image-book-page',
            'asset_type' => 'image',
            'mime_type' => 'image/jpeg',
            'page' => 2,
        ]);

        $unpaidImageBookContent->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => 'sdsdsd-weowpeu',
            'url' => 'https://res.cloudinary.com/akiddie/image/upload/v1617351892/contents/14517881306066d47a53205/2d-image/dee9y2gz9bkf6nzwnwwp.jpg',
            'purpose' => 'image-book-page',
            'asset_type' => 'image',
            'mime_type' => 'image/jpeg',
            'page' => 3,
        ]);
        //add cover
        $unpaidImageBookContent->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => '2229-soperlu',
            'url' => 'https://res.cloudinary.com/akiddie/image/upload/v1617731061/contents/826963893606c9de73bc36/cover/ldvsubjoaqicmg5htjep.png',
            'purpose' => 'cover',
            'asset_type' => 'image',
            'mime_type' => 'image/png',
        ]);
        
        //add prices
        $unpaidImageBookContent->prices()->create([
            'public_id' => '14517881306066d47a5320pu',
            'amount' => 2000,
            'interval' => 'one-off',
            'currency' => 'USD',
        ]);

        /**
         * Add Collection
         * 
         */
        $collection = Collection::create(MockCollection::SEEDED_UNPAID_COLLECTION2);
        //add benefactors 
        $collection->benefactors()->createMany([
            [
                'user_id' => 4,
                'share' => 60,
            ],
            [
                'user_id' => 6,
                'share' => 20,
            ],
            [
                'user_id' => 7,
                'share' => 20,
            ],
        ]);
        //add cover
        $collection->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => '2229-soperlu',
            'url' => 'https://res.cloudinary.com/akiddie/image/upload/v1617731061/contents/826963893606c9de73bc36/cover/ldvsubjoaqicmg5htjep.png',
            'purpose' => 'cover',
            'asset_type' => 'image',
            'mime_type' => 'image/png',
        ]);
        //add prices
        $collection->prices()->create([
            'public_id' => '1867378820606c9e5ab27cpu',
            'amount' => 2000,
            'interval' => 'month',
            'interval_amount' => 1,
            'currency' => 'USD',
        ]);
        //add content
        $collection->contents()->attach([1,2,]);

        $parentCollectionWithSubs = Collection::create(MockCollection::SEEDED_COLLECTION_WITH_SUB);
        //add benefactors 
        $parentCollectionWithSubs->benefactors()->createMany([
            [
                'user_id' => 4,
                'share' => 60,
            ],
            [
                'user_id' => 6,
                'share' => 20,
            ],
            [
                'user_id' => 7,
                'share' => 20,
            ],
        ]);
        //add cover
        $parentCollectionWithSubs->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => '2229-soperlu',
            'url' => 'https://res.cloudinary.com/akiddie/image/upload/v1617731061/contents/826963893606c9de73bc36/cover/ldvsubjoaqicmg5htjep.png',
            'purpose' => 'cover',
            'asset_type' => 'image',
            'mime_type' => 'image/png',
        ]);
        //add prices
        $parentCollectionPrice = $parentCollectionWithSubs->prices()->create([
            'public_id' => uniqid(rand()),
            'amount' => 2000,
            'interval' => 'month',
            'interval_amount' => 1,
            'currency' => 'USD',
        ]);
        //add content
        $parentCollectionWithSubs->contents()->attach([1,]);


        $sub1Level1 = Collection::create(MockCollection::SEEDED_SUB_COLLECTION_1_LEVEL_1);
        //add cover
        $sub1Level1->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => '2229-soperlu',
            'url' => 'https://res.cloudinary.com/akiddie/image/upload/v1617731061/contents/826963893606c9de73bc36/cover/ldvsubjoaqicmg5htjep.png',
            'purpose' => 'cover',
            'asset_type' => 'image',
            'mime_type' => 'image/png',
        ]);
        //add content
        $sub1Level1->contents()->attach([1,]);

        $sub2Level1 = Collection::create(MockCollection::SEEDED_SUB_COLLECTION_2_LEVEL_1);
        //add cover
        $sub2Level1->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => '2229-soperlu',
            'url' => 'https://res.cloudinary.com/akiddie/image/upload/v1617731061/contents/826963893606c9de73bc36/cover/ldvsubjoaqicmg5htjep.png',
            'purpose' => 'cover',
            'asset_type' => 'image',
            'mime_type' => 'image/png',
        ]);
        //add content
        $sub2Level1->contents()->attach([1,]);

        $sub1Child1 = Collection::create(MockCollection::SEEDED_SUB_COLLECTION_1_LEVEL_1_CHILD_1);
        //add cover
        $sub1Child1->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => '2229-soperlu',
            'url' => 'https://res.cloudinary.com/akiddie/image/upload/v1617731061/contents/826963893606c9de73bc36/cover/ldvsubjoaqicmg5htjep.png',
            'purpose' => 'cover',
            'asset_type' => 'image',
            'mime_type' => 'image/png',
        ]);
        //add content
        $sub1Child1->contents()->attach([1,]);

        $sub1Child2 = Collection::create(MockCollection::SEEDED_SUB_COLLECTION_1_LEVEL_1_CHILD_2);
        //add cover
        $sub1Child2->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => '2229-soperlu',
            'url' => 'https://res.cloudinary.com/akiddie/image/upload/v1617731061/contents/826963893606c9de73bc36/cover/ldvsubjoaqicmg5htjep.png',
            'purpose' => 'cover',
            'asset_type' => 'image',
            'mime_type' => 'image/png',
        ]);
        //add content
        $sub1Child2->contents()->attach([1,]);

        $sub2Child1 = Collection::create(MockCollection::SEEDED_SUB_COLLECTION_2_LEVEL_1_CHILD_1);
        //add cover
        $sub2Child1->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => '2229-soperlu',
            'url' => 'https://res.cloudinary.com/akiddie/image/upload/v1617731061/contents/826963893606c9de73bc36/cover/ldvsubjoaqicmg5htjep.png',
            'purpose' => 'cover',
            'asset_type' => 'image',
            'mime_type' => 'image/png',
        ]);
        //add content
        $sub2Child1->contents()->attach([1,]);

        $sub2Child2 = Collection::create(MockCollection::SEEDED_SUB_COLLECTION_2_LEVEL_1_CHILD_2);
        //add cover
        $sub2Child2->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'cloudinary',
            'storage_provider_id' => '2229-soperlu',
            'url' => 'https://res.cloudinary.com/akiddie/image/upload/v1617731061/contents/826963893606c9de73bc36/cover/ldvsubjoaqicmg5htjep.png',
            'purpose' => 'cover',
            'asset_type' => 'image',
            'mime_type' => 'image/png',
        ]);
        //add content
        $sub2Child2->contents()->attach([1,]);

        //attach the child collections
        $parentCollectionWithSubs->childCollections()->attach([$sub1Level1->id,$sub2Level1->id]);
        $sub1Level1->childCollections()->attach([$sub1Child1->id,$sub1Child2->id]);
        $sub2Level1->childCollections()->attach([$sub2Child1->id,$sub2Child2->id]);
    }
}
