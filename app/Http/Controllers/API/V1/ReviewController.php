<?php

namespace App\Http\Controllers\API\V1;

use App\Constants\Constants;
use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\Content;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    public function create(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => ['required', 'string', ],
                'type' => ['required', 'string', 'regex:(content|collection|review)',],
                'rating' => ['sometimes', 'required', 'numeric', 'min:1', 'max:5'],
                'comment' => ['sometimes', 'required', 'string', ],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $itemModel = null;

            switch ($request->type) {
                case 'content':
                    $itemModel = Content::where('id', $request->id)->first();
                    break;
                case 'collection':
                    $itemModel = Collection::where('id', $request->id)->first();
                    break;
                case 'review':
                    $itemModel = Review::where('id', $request->id)->first();
                    break;
            }

            if (is_null($itemModel)) {
                return $this->respondBadRequest('Invalid public ID supplied for ' . ucfirst($request->type));
            }

            //check if a review has been submitted before
            $review = Review::where('user_id', $request->user()->id)->where('reviewable_type', $request->type)->where('reviewable_id', $itemModel->id)->first();

            if (! is_null($review)) {
                //not null, update
                $review->fill($request->only(['rating', 'comment']));
                $review->save();
            } else {
                //it is null, create
                $review = $itemModel->reviews()->create([
                    'user_id' => $request->user()->id,
                    'comment' => $request->comment,
                    'rating' => $request->rating,
                ]);
            }

            return $this->respondWithSuccess('Review recorded successfully', [
                'review' => $review,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function getReviews(Request $request, $id)
    {
        try {
            $review = Review::where('id', $id)->first();
            if (is_null($review)) {
                return $this->respondBadRequest('Invalid review ID supplied');
            }
            $page = ctype_digit(strval($request->query('page', 1))) ? $request->query('page', 1) : 1;
            $limit = ctype_digit(strval($request->query('limit', 10))) ? $request->query('limit', 10) : 1;
            if ($limit > Constants::MAX_ITEMS_LIMIT) {
                $limit = Constants::MAX_ITEMS_LIMIT;
            }

            $reviews = $review->reviews()->with('user', 'user.profile_picture', 'user.roles')->orderBy('created_at', 'desc')->paginate($limit, ['*'], 'page', $page);
            return $this->respondWithSuccess('Reviews retrieved successfully', [
                'reviews' => $reviews,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function addViews(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => ['required', 'string', ],
                'type' => ['required', 'string', 'regex:(content|collection)',],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $itemModel = null;

            switch ($request->type) {
                case 'content':
                    $itemModel = Content::where('id', $request->id)->first();
                    break;
                case 'collection':
                    $itemModel = Collection::where('id', $request->id)->first();
                    break;
            }

            if (is_null($itemModel)) {
                return $this->respondBadRequest('Invalid public ID supplied for ' . ucfirst($request->type));
            }

            $itemModel->views = $itemModel->views + 1;
            $itemModel->save();

            return $this->respondWithSuccess('View recorded successfully', [
                'item' => $itemModel,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }
}
