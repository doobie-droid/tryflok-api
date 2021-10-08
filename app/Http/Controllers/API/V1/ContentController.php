<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Constants\Permissions;
use App\Constants\Roles;
use App\Constants\Constants;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Content;
use App\Models\Language;
use App\Models\Price;
use App\Models\Review;
use App\Models\Userable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
use App\Jobs\Content\Add as AddContentJob;
use App\Jobs\Content\Edit as EditContentJob;
use App\Http\Resources\ContentResource;
use App\Jobs\Dash as DashJob;

class ContentController extends Controller
{
    public function dashVideoUpload(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'video' => ['required', 'file',],
            ]);
            $path = Storage::disk('public')->put('upload', $request->video);
            DashJob::dispatch([
                'resource_path' => public_path() . '/' . $path,
            ]);
            return $this->respondWithSuccess("Content has been queued for upload. It would be uploaded shortly");
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		} 
    }

    private function encryptData($message, $key)
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        file_put_contents(public_path() . '/ed/nonce', $nonce);
        file_put_contents(public_path() . '/ed/nonce-base64', base64_encode($nonce));
        file_put_contents(public_path() . '/ed/nonce-length', SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $crypt = sodium_crypto_secretbox(
            $message,
            $nonce,
            $key
        );
        file_put_contents(public_path() . '/ed/crypt', $crypt);
        file_put_contents(public_path() . '/ed/crypt-base64', base64_encode($crypt));
        return base64_encode(
            $nonce .
            $crypt
        );
    }

    private function decryptData($encrypted, $key)
    {
        $decoded = base64_decode($encrypted);
        $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, NULL, '8bit');

        return sodium_crypto_secretbox_open(
            $ciphertext,
            $nonce,
            $key
        );
    }

    public function generateKey()
    {
        $keypair = sodium_crypto_box_keypair();
        $publicKey = sodium_crypto_box_publickey($keypair);
        return $this->respondWithSuccess("keys",[
            //'private_plain' => $keypair,
            'private_b64' => base64_encode($keypair),
            //'public_plain' => $publicKey,
            'public_b64' => base64_encode($publicKey),
        ]);
    }
    public function testEncryption(Request $request)
    {
        $key = sodium_crypto_secretbox_keygen();
        file_put_contents(public_path() . '/ed/key', $key);
        file_put_contents(public_path() . '/ed/key-base64', base64_encode($key));
        //original files
        $imagePath = public_path() . '/ued/1.jpg';
        $pdfPath = public_path() . '/ued/1.pdf';
        $audioPath = public_path() . '/ued/canon.mp3';

        //encrypted path
        $encImagePath = public_path() . '/ed/1.imge';
        $encPdfPath = public_path() . '/ed/1.pdfe';
        $encAudioPath = public_path() . '/ed/1.aude';

        //decrypted path
        $decImagePath = public_path() . '/dd/1.jpg';
        $decPdfPath = public_path() . '/dd/1.pdf';
        $decAudioPath = public_path() . '/dd/1.mp3';

        //generate base64 of assets
        $image = file_get_contents($imagePath);
        $imageB64 = base64_encode($image);

       // $pdf = file_get_contents($pdfPath);
       // $pdfB64 = base64_encode($pdf);

       // $audio = file_get_contents($audioPath);
       // $audioB64 = base64_encode($audio);
           
        //encrypt base64 string
        $encImage = $this->encryptData($imageB64, $key);
        //$encPdf = $this->encryptData($pdfB64, $key);
       // $encAudio = $this->encryptData($audioB64, $key);

        //save encrypted string to file
        file_put_contents($encImagePath, $encImage);
        //file_put_contents($encPdfPath, $encPdf);
       // file_put_contents($encAudioPath, $encAudio);

        //decrypt string
        $decImageBase64 = $this->decryptData($encImage, $key);
        //$decPdfBase64 = $this->decryptData($encPdf, $key);
        //$decAudioBase64 = $this->decryptData($encAudio, $key);

        //save decrypted to file
        file_put_contents($decImagePath, base64_decode($decImageBase64));
        //file_put_contents($decPdfPath, base64_decode($decPdfBase64));
       // file_put_contents($decAudioPath, base64_decode($decAudioBase64));

        return "Hello encryption";
    }

    public function create(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => ['required', 'string'],
                'summary' => ['sometimes', 'string'],
                'price' => ['required', 'numeric', 'min:0', 'max:9999.99'],
                'cover' => ['required', 'image', 'max:1024'],//1MB 
                'type' => ['required', 'string', 'regex:(book|audio|video)'],
                'audio' => ['required_if:type,audio', 'file', 'max:102400'],//100MB -- max of 1Hr of the highest quality audio.
                'video' => ['required_if:type,video', 'file','max:4096000'],//4GB -- we would increase later when we get better creators on board
                'format' => ['required_if:type,book', 'string', 'regex:(pdf|2d-image|3d-image)'],
                'zip' => ['required_if:type,book', 'file', 'mimes:zip', 'max:102400'],//100MB - each page must not surpass 1MB and max of 100 pages (it's a children's book)
                'categories' => ['sometimes', 'required'],
                'categories.*' => ['sometimes', 'string','exists:categories,public_id'],
                'parent_collection' => ['sometimes','required','exists:collections,public_id'],
                'benefactors' => ['required'],
                'benefactors.*.public_id' => ['required', 'exists:users,public_id'],
                'is_available' => ['required', 'integer', 'regex:(0|1)'],
                'show_only_in_collections' => ['required', 'integer', 'regex:(0|1)'],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            $reworkedBenefactors = [];
            $totalShare = 0;
            $creatorInBenefactorList = false;
            foreach ($request->benefactors as $key => $data) {
                if (array_key_exists("public_id", $data)) {
                    $reworkedBenefactors["k" . $key]['public_id'] = $data['public_id'];
                    if ($data['public_id'] === $request->user()->public_id) {
                        $creatorInBenefactorList = true;
                    }
                }

                if (array_key_exists("share", $data)) {
                    $reworkedBenefactors["k" . $key]['share'] = $data['share'];
                    $totalShare = bcadd($totalShare, $data['share'], 2);
                }
            }
            //make sure that the share add up to 100
            if ((int)$totalShare !== 100) {
                return $this->respondBadRequest("Benefactor share numbers do not add up to 100");
            }
            //make sure the creator of this content/collection is included in benefactor list
            if ($creatorInBenefactorList === false) {
                return $this->respondBadRequest("Creator of content not included in benefactor list");
            }
            $reworkedBenefactors2 = [];
            foreach ($reworkedBenefactors as $key => $data) {
                $reworkedBenefactors2[] = $data;
            }

            $user = $request->user();

            $english = Cache::remember('languages:english', Constants::MONTH_CACHE_TIME, function () {
                return Language::where('name', 'english')->first();
            });

            $coverPath = "";
            if ($request->hasFile('cover'))
            {
                $path = Storage::disk('local')->put('uploads/covers', $request->cover);
                $coverPath = storage_path() . "/app/" . $path;
            } 
            $uploadedFilePath = "";
            switch ($request->type) {
                case "book":
                    $path = Storage::disk('local')->put('uploads/zips', $request->zip);
                    $uploadedFilePath = storage_path() . "/app/" . $path;
                    break;
                case "audio":
                    $path = Storage::disk('local')->put('uploads/audios', $request->audio);
                    $uploadedFilePath = storage_path() . "/app/" . $path;
                    break;
                case "video":
                    $path = Storage::disk('local')->put('uploads/videos', $request->video);
                    $uploadedFilePath = storage_path() . "/app/" . $path;
                    break;
            }

            AddContentJob::dispatch([
                'title' => $request->title,
                'summary' => $request->summary,
                'price' => $request->price,
                'type' => $request->type,
                'format' => $request->format,
                'cover_path' => $coverPath,
                'uploaded_file_path' => $uploadedFilePath,
                'language' => $english,
                'owner' => $user,
                'categories' => $request->categories && is_array($request->categories)? array_unique($request->categories) : null,
                'benefactors' => $reworkedBenefactors2,
                'is_available' => $request->is_available,
                'show_only_in_collections' => $request->show_only_in_collections,
                'parent_collection' => $request->parent_collection,
            ]);
            
            $this->setStatusCode(202);
            return $this->respondWithSuccess("Content has been queued for upload. It would be uploaded shortly");

        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		} 
    }

    public function update(Request $request, $public_id)
    {
        try {
            $validator = Validator::make(array_merge($request->all(), ['id' => $public_id]), [
                'id' => ['required', 'string', 'exists:contents,public_id'],
                'title' => ['sometimes', 'nullable', 'string'],
                'summary' => ['sometimes','nullable', 'string'],
                'price' => ['sometimes','nullable', 'numeric', 'min:0', 'max:9999.99'],
                'cover' => ['sometimes','nullable', 'image', 'max:1024'],//1MB
                'type' => ['required_with:zip,audio,video', 'string', 'regex:(book|audio|video)'],
                'audio' => ['sometimes', 'nullable', 'file', 'max:102400'],//100MB  - 1 hour of max audio quality
                'video' => ['sometimes', 'nullable', 'file', 'max:4096000'],//4GB
                'format' => ['required_with:zip', 'string', 'regex:(pdf|2d-image|3d-image)'],
                'zip' => ['sometimes', 'nullable', 'mimes:zip', 'max:102400'],//100MB - each page must not surpass 1MB and max of 100 pages
                'categories' => ['sometimes', 'nullable'],
                'categories.*.action' => ['required', 'string', 'regex:(add|remove)'],
                'categories.*.public_id' => ['required', 'string','exists:categories,public_id'],
                'benefactors' => ['sometimes', 'nullable'],
                'benefactors.*.action' => ['required', 'string', 'regex:(add|remove|update)'],
                'benefactors.*.public_id' => ['required', 'exists:users,public_id'],
                'is_available' => ['sometimes', 'nullable', 'integer', 'regex:(0|1)'],
                'show_only_in_collections' => ['sometimes', 'nullable', 'integer', 'regex:(0|1)'],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            //make sure user owns content
            $content = Content::where('public_id', $public_id)->where('user_id', $request->user()->id)->first();
            if (is_null($content)) {
                return $this->respondBadRequest("You do not have permission to update this content");
            }

            //validate benefactors
            $currentBenefactors = $content->benefactors()->get();
            $benefactorsToAdd = [];
            $benefactorsToUpdate = [];
            $benefactorsToDelete = [];
            if (isset($request->benefactors) && is_array($request->benefactors)) {
                $sum = 0;
                foreach ($currentBenefactors as $benefactor) {
                    $found = false;
                    foreach ($request->benefactors as $requestBenefactor) {
                        //if found
                        if ($benefactor->user->public_id === $requestBenefactor['public_id']) {
                            $found = true;
                            if ($requestBenefactor['action'] === 'update') {
                                if (!array_key_exists('share', $requestBenefactor)){
                                    return $this->respondBadRequest("[share] is required if benefactor is being updated. Missing share detected for benefator with public_id [" . $requestBenefactor['public_id'] . "]" );
                                }
                                $benefactorsToUpdate[] = ['benefactor' => $benefactor, 'share' => $requestBenefactor['share']];
                                $sum = bcadd($sum,$requestBenefactor['share'],6);
                            }
                            if ($requestBenefactor['action'] === 'remove') {
                                $benefactorsToDelete[] = $benefactor;
                            }
                        }
                    }
                    if (!$found) {
                        $sum = bcadd($sum,$benefactor->share,6);
                    }
                }
                //loop through request benefactors and add the ones that are to be added
                foreach ($request->benefactors as $requestBenefactor) {
                    if ($requestBenefactor['action'] === 'add') {
                        if (!array_key_exists('share', $requestBenefactor)){
                            return $this->respondBadRequest("[share] is required if benefactor is being added. Missing share detected for benefator with public_id [" . $requestBenefactor['public_id'] . "]" );
                        }
                        $benefactorsToAdd[] = $requestBenefactor;
                        $sum = bcadd($sum,$requestBenefactor['share'],6);
                    }
                }

                $sum = (int) $sum;
                if ($sum !== 100) {
                    return $this->respondBadRequest("Benefactors sum does not add up to 100. " . $sum . " gotten instead" );
                }
            }

            $user = $request->user();

            $english = Cache::remember('languages:english', Constants::MONTH_CACHE_TIME, function () {
                return Language::where('name', 'english')->first();
            });

            $coverPath = "";
            if ($request->hasFile('cover'))
            {
                $path = Storage::disk('local')->put('uploads/covers', $request->cover);
                $coverPath = storage_path() . "/app/" . $path;
            } 
            $uploadedFilePath = "";
            switch ($request->type) {
                case "book":
                    if ($request->hasFile('zip'))
                    {
                        $path = Storage::disk('local')->put('uploads/zips', $request->zip);
                        $uploadedFilePath = storage_path() . "/app/" . $path;
                    } 
                    break;
                case "audio":
                    if ($request->hasFile('audio'))
                    {
                        $path = Storage::disk('local')->put('uploads/audios', $request->audio);
                        $uploadedFilePath = storage_path() . "/app/" . $path;
                    } 
                    break;
                case "video":
                    if ($request->hasFile('video'))
                    {
                        $path = Storage::disk('local')->put('uploads/videos', $request->video);
                        $uploadedFilePath = storage_path() . "/app/" . $path;
                    } 
                    break;
            }

            EditContentJob::dispatch([
                'content' => $content,
                'title' => isset($request->title) ? $request->title : NULL,
                'summary' => isset($request->summary) ? $request->summary : NULL,
                'price' => isset($request->price) ? $request->price : NULL,
                'type' => isset($request->type) ? $request->type : NULL,
                'format' => isset($request->format) ? $request->format : NULL,
                'cover_path' => $coverPath,
                'uploaded_file_path' => $uploadedFilePath,
                'language' => $english,
                'owner' => $user,
                'categories' => isset($request->categories) ? $request->categories : NULL,
                'benefactors_to_add' => $benefactorsToAdd,
                'benefactors_to_update' => $benefactorsToUpdate,
                'benefactors_to_delete' => $benefactorsToDelete,
                'is_available' => isset($request->is_available) ? $request->is_available : NULL,
                'show_only_in_collections' => isset($request->show_only_in_collections) ? $request->show_only_in_collections : NULL,
            ]);
            
            $this->setStatusCode(202);
            return $this->respondWithSuccess("Content has been queued for update. It would be uploaded shortly");

        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		} 
    }

    public function getAll(Request $request)
    {
        try {
            $page = ctype_digit(strval($request->query('page', 1))) ? $request->query('page', 1) : 1;
            $limit = ctype_digit(strval($request->query('limit', 10))) ? $request->query('limit', 10) : 1;
            if ($limit > Constants::MAX_ITEMS_LIMIT) {
                $limit = Constants::MAX_ITEMS_LIMIT;
            }
            $title = urldecode($request->query('title', ''));
            $categories = $request->query('categories', '');
            $types = $request->query('type','');
            $creators = $request->query('creators','');
            $freeItems = $request->query('free', 0);

                if ($categories != "") {
                    $categories = explode(",", urldecode($categories));
                    $categories = array_diff($categories, [""]);//get rid of empty values
                    $contents = Content::whereHas('categories', function (Builder $query) use ($categories) {
                        $query->whereIn('name', $categories);
                    })->where('title', 'LIKE', '%' . $title . '%')->where('is_available', 1)->where('show_only_in_collections', 0)->where('approved_by_admin', 1);
                } else {
                    $contents = Content::where('title', 'LIKE', '%' . $title . '%')->where('is_available', 1)->where('show_only_in_collections', 0)->where('approved_by_admin', 1);
                }
    
                if ($freeItems == 1) {
                    $contents = $contents->whereHas('prices', function (Builder $query) {
                        $query->where('amount', 0);
                    });
                }
    
                if ($types != "") {
                    $types = explode(",", urldecode($types));
                    $types = array_diff($types, [""]);//get rid of empty values
                    $contents = $contents->whereIn('type', $types);
                } 
    
                if ($creators != "") {
                    $creators = explode(",", urldecode($creators));
                    $creators = array_diff($creators, [""]);//get rid of empty values
                    $creators_id = User::whereIn('public_id', $creators)->pluck('id')->toArray();
                    $contents = $contents->whereIn('user_id', $creators_id );
                } 
    
                if ($request->user() == NULL || $request->user()->id == NULL) {
                    $user_id = 0;
                } else {
                    $user_id = $request->user()->id;
                }
                $contents = $contents->withCount([
                    'ratings' => function ($query) {
                        $query->where('rating', '>', 0);
                    }
                ])->withAvg([
                    'ratings' => function($query)
                    {
                        $query->where('rating', '>', 0);
                    }
                ], 'rating')->with('cover')->with('owner','owner.profile_picture')->with('categories')->with('prices', 'prices.continent', 'prices.country')->with(['userables' => function ($query) use ($user_id) {
                    $query->with('subscription')->where('user_id',  $user_id)->where('status', 'available');
                }])->orderBy('created_at', 'desc')->paginate($limit, array('*'), 'page', $page);
            
            return $this->respondWithSuccess("Contents retrieved successfully",[
                'contents' => ContentResource::collection($contents),
                'current_page' => $contents->currentPage(),
                'items_per_page' => $contents->perPage(),
                'total' => $contents->total(),
            ]);

        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		} 
    }

    public function getUserCreatedContents(Request $request, $user_public_id)
    {
        try {
            $user = User::where('public_id', $user_public_id)->first();
            if (is_null($user)) {
                return $this->respondBadRequest("Invalid user ID supplied");
            }

            $page = ctype_digit(strval($request->query('page', 1))) ? $request->query('page', 1) : 1;
            $limit = ctype_digit(strval($request->query('limit', 10))) ? $request->query('limit', 10) : 1;
            if ($limit > Constants::MAX_ITEMS_LIMIT) {
                $limit = Constants::MAX_ITEMS_LIMIT;
            }
            $title = urldecode($request->query('title', ''));
            $categories = $request->query('categories', '');
            $types = $request->query('type','');
            $freeItems = $request->query('free', 0);

                if ($categories != "") {
                    $categories = explode(",", urldecode($categories));
                    $categories = array_diff($categories, [""]);//get rid of empty values
                    $contents = $user->contentsCreated()->whereHas('categories', function (Builder $query) use ($categories) {
                        $query->whereIn('name', $categories);
                    })->where('title', 'LIKE', '%' . $title . '%');
                } else {
                    $contents = $user->contentsCreated()->where('title', 'LIKE', '%' . $title . '%');
                }

                if ($freeItems == 1) {
                    $contents = $contents->whereHas('prices', function (Builder $query) {
                        $query->where('amount', 0);
                    });
                }

                if ($request->user() == NULL || $request->user()->id == NULL) {
                    $contents = $contents->where('is_available', 1)->where('show_only_in_collections', 0)->where('approved_by_admin', 1);
                } else {
                    if ($request->user()->id !== $user->id) {
                        $contents = $contents->where('is_available', 1)->where('show_only_in_collections', 0)->where('approved_by_admin', 1);
                    }
                }

                if ($types != "") {
                    $types = explode(",", urldecode($types));
                    $types = array_diff($types, [""]);//get rid of empty values
                    $contents = $contents->whereIn('type', $types);
                } 

                $contents = $contents->withCount([
                    'ratings' => function ($query) {
                        $query->where('rating', '>', 0);
                    }
                ])->withAvg([
                    'ratings' => function($query)
                    {
                        $query->where('rating', '>', 0);
                    }
                ], 'rating')->with('cover')->with('approvalRequest')->with('owner', 'owner.profile_picture')->with('categories')->with('prices', 'prices.continent', 'prices.country')->orderBy('created_at', 'desc')->paginate($limit, array('*'), 'page', $page);
            return $this->respondWithSuccess("Contents retrieved successfully",[
                'contents' => ContentResource::collection($contents),
                'current_page' => $contents->currentPage(),
                'items_per_page' => $contents->perPage(),
                'total' => $contents->total(),
            ]);

        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		} 
    }

    public function getReviews(Request $request, $public_id)
    {
        try {
            $content = Content::where('public_id', $public_id)->first();
            if (is_null($content)) {
                return $this->respondBadRequest("Invalid content ID supplied");
            }
            $page = ctype_digit(strval($request->query('page', 1))) ? $request->query('page', 1) : 1;
            $limit = ctype_digit(strval($request->query('limit', 10))) ? $request->query('limit', 10) : 1;
            if ($limit > Constants::MAX_ITEMS_LIMIT) {
                $limit = Constants::MAX_ITEMS_LIMIT;
            }

                $reviews = $content->reviews()->with('user', 'user.profile_picture', 'user.roles')->orderBy('created_at', 'desc')->paginate($limit, array('*'), 'page', $page);
            return $this->respondWithSuccess("Reviews retrieved successfully",[
                'reviews' => $reviews,
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		} 
    }

    public function getSingle(Request $request, $public_id)
    {
        try {
            $validator = Validator::make(['public_id' => $public_id], [
                'public_id' => ['required', 'string', 'exists:contents,public_id'],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            if ($request->user() == NULL || $request->user()->id == NULL) {
                $user_id = 0;
            } else {
                $user_id = $request->user()->id;
            }
            
            $content = Content::where('public_id', $public_id)->withCount([
                'ratings' => function ($query) {
                    $query->where('rating', '>', 0);
                }
            ])->withAvg([
                'ratings' => function($query)
                {
                    $query->where('rating', '>', 0);
                }
            ], 'rating')->with('cover')->with('approvalRequest')->with('categories','benefactors', 'benefactors.user')->with('prices', 'prices.continent', 'prices.country')->with('owner', 'owner.profile_picture')->with(['userables' => function ($query) use ($user_id) {
                $query->with('subscription')->where('user_id',  $user_id)->where('status', 'available');
            }])->first();
            return $this->respondWithSuccess("Content retrieved successfully",[
                'content' => new ContentResource($content),
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		} 
    }

    public function getAssets(Request $request, $public_id)
    {
        try {
            $validator = Validator::make(['public_id' => $public_id], [
                'public_id' => ['required', 'string', 'exists:contents,public_id'],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }
           
            $content = Content::where('public_id', $public_id)->first();
            $freePrice = $content->prices()->where('amount', 0)->first();
            //check if user owns this content if it is not free
            $userable = Userable::where('user_id',  $request->user()->id)->where('userable_type', 'content')->where('userable_id', $content->id)->where('status', 'available')->first();
            if (is_null($freePrice) && $content->user_id !== $request->user()->id && !$request->user()->hasRole(Roles::ADMIN) && !$request->user()->hasRole(Roles::SUPER_ADMIN)) {
                if (is_null($userable)) {
                    $this->setStatusCode(402);
                    return $this->respondBadRequest("You do not have permission to view this paid content as you have not purchased it or are not subscribed to it.");
                }
            }
            //only admins or creators can access this via web
            if ($request->user()->id !== $content->user_id && !$request->user()->hasRole(Roles::ADMIN) && !$request->user()->hasRole(Roles::SUPER_ADMIN)  && trim($request->header('User-Agent')) !== 'Dart/2.10 (dart:io)') {
                return $this->respondBadRequest("An error occurred");
            }
            
            return $this->respondWithSuccess("Assets retrieved successfully",[
                'assets' => $content->assets()->with('resolutions')->where('purpose', '<>', 'cover')->get(),
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function getFreeAssets(Request $request, $public_id)
    {
        try {
            $validator = Validator::make(['public_id' => $public_id], [
                'public_id' => ['required', 'string', 'exists:contents,public_id'],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }
           
            $content = Content::where('public_id', $public_id)->first();
            $freePrice = $content->prices()->where('amount', 0)->first();
            if (is_null($freePrice)) {
                $this->setStatusCode(402);
                return $this->respondBadRequest("You do not have permission to view this paid content as you have not purchased it or are not subscribed to it.");
            }
            //only admins or creators can access this via web
            if ($request->user()->id !== $content->user_id && !$request->user()->hasRole(Roles::ADMIN) && !$request->user()->hasRole(Roles::SUPER_ADMIN)  && trim($request->header('User-Agent')) !== 'Dart/2.10 (dart:io)') {
                return $this->respondBadRequest("An error occurred");
            }
            
            return $this->respondWithSuccess("Assets retrieved successfully",[
                'assets' => $content->assets()->where('purpose', '<>', 'cover')->get(),
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }
}
