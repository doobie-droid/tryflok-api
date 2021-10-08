<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Constants\Permissions;
use App\Constants\Roles;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Review;
use App\Models\Content;
use App\Models\Collection;

class ReviewController extends Controller
{
    public function create(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'public_id' => ['required', 'string', ],
                'type' => ['required', 'string', 'regex:(content|collection)',],
                'rating' => ['sometimes', 'required', 'numeric', 'min:1', 'max:5'],
                'comment' => ['sometimes', 'required', 'string', ],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            $itemModel = NULL;

            switch ($request->type) {
                case 'content':
                    $itemModel = Content::where('public_id', $request->public_id)->first();
                    break;
                case 'collection':
                    $itemModel = Collection::where('public_id', $request->public_id)->first();
                    break;
            }

            if (is_null($itemModel)) {
                return $this->respondBadRequest("Invalid public ID supplied for " . ucfirst($request->type));
            }

            //check if a review has been submitted before
            $review = Review::where('user_id', $request->user()->id)->where('reviewable_type', $request->type)->where('reviewable_id', $itemModel->id)->first();

            if (!is_null($review)) {
                //not null, update
                $review->fill($request->only(['rating', 'comment']));
                $review->save();
            } else {
                //it is null, create
                $review = $itemModel->reviews()->create([
                    'public_id' => uniqid(rand()),
                    'user_id' => $request->user()->id,
                    'comment' => $request->comment,
                    'rating' => $request->rating,
                ]);
            }

            return $this->respondWithSuccess("Review recorded successfully", [
                'review' => $review,
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		} 
    }

    public function addViews(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'public_id' => ['required', 'string', ],
                'type' => ['required', 'string', 'regex:(content|collection)',],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            $itemModel = NULL;

            switch ($request->type) {
                case 'content':
                    $itemModel = Content::where('public_id', $request->public_id)->first();
                    break;
                case 'collection':
                    $itemModel = Collection::where('public_id', $request->public_id)->first();
                    break;
            }

            if (is_null($itemModel)) {
                return $this->respondBadRequest("Invalid public ID supplied for " . ucfirst($request->type));
            }

            $itemModel->views = $itemModel->views + 1;
            $itemModel->save();

            return $this->respondWithSuccess("View recorded successfully", [
                'item' => $itemModel,
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		} 
    }
}
