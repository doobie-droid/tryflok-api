<?php

namespace App\Http\Controllers\API\V1;

use Illuminate\Http\Request;
use App\Constants\Constants;
use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\ContentComment;
use App\Models\ContentCommentComment;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ContentCommentController extends Controller
{
    public function createContentComment(Request $request, $id)
    {
        try {
            $validator = Validator::make(array_merge($request->all(), ['id' => $id]), [
                'id' => ['required', 'string', 'exists:contents,id'],
                'comment' => ['required', 'string'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $comment = ContentComment::create([
                'comment' => $request->comment,
                'user_id' => $request->user()->id,
                'content_id' => $id,
            ]);          
            return $this->respondWithSuccess('comment has been created successfully', [
                'comment' => $comment->with('content')->first(),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function listContentComments(Request $request, $id)
    {
        try {
            $content = Content::where('id', $id)->first();
            if (is_null($content)) {
                return $this->respondBadRequest('Invalid content ID supplied');
            }
            $page = ctype_digit(strval($request->query('page', 1))) ? $request->query('page', 1) : 1;
            $limit = ctype_digit(strval($request->query('limit', 10))) ? $request->query('limit', 10) : 1;
            if ($limit > Constants::MAX_ITEMS_LIMIT) {
                $limit = Constants::MAX_ITEMS_LIMIT;
            }

            $comments = $content->comments()->with('user', 'user.profile_picture', 'user.roles', 'comments')
            ->orderBy('created_at', 'desc')->paginate($limit, ['*'], 'page', $page);
            return $this->respondWithSuccess('comments retrieved successfully', [
                'comments' => $comments,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function deleteContentComment(Request $request, $comment_id)
    {
        try {
            $validator = Validator::make(array_merge($request->all(), ['id' => $comment_id]), [
                'id' => ['required', 'string', 'exists:content_comments,id'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }
               
            //make sure user owns comment
            $contentComment = ContentComment::where('id', $comment_id)
            ->where('user_id', $request->user()->id)
            ->first();

            if ( is_null($contentComment)) {
                return $this->respondBadRequest('You do not have permission to delete this comment');
            } 
            $contentComment->delete();
            return $this->respondWithSuccess('Comment deleted successfully', [
                'comment' => $contentComment,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function updateContentComment(Request $request, $comment_id)
    {
        try {
            $validator = Validator::make(array_merge($request->all(), ['id' => $comment_id]), [
                'id' => ['required', 'string', 'exists:content_comments,id'],
                'comment' => ['required', 'string'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }
   
            //make sure user owns comment
            $contentComment = ContentComment::where('id', $comment_id)
            ->where('user_id', $request->user()->id)
            ->with('user', 'user.profile_picture', 'user.roles', 'comments')
            ->first();
            if ( is_null($contentComment)) {
                return $this->respondBadRequest('You do not have permission to update this comment');
            }    

            $contentComment->comment = $request->comment;
            $contentComment->save();

            return $this->respondWithSuccess('Comment has been updated successfully', [
                'comment' => $contentComment,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function createContentCommentComment(Request $request, $comment_id)
    {
        try {
            $validator = Validator::make(array_merge($request->all(), ['id' => $comment_id]), [
                'id' => ['required', 'string', 'exists:content_comments,id'],
                'comment' => ['required', 'string'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $comment = ContentCommentComment::create([
                'comment' => $request->comment,
                'user_id' => $request->user()->id,
                'content_comment_id' => $comment_id,
            ]);          
            return $this->respondWithSuccess('comment comment has been created successfully', [
                'comment' => $comment->with('contentComment')->first(),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function listContentCommentComments(Request $request, $comment_id)
    {
        try {
            $contentComment = ContentComment::where('id', $comment_id)->first();
            if (is_null($contentComment)) {
                return $this->respondBadRequest('Invalid content_comment ID supplied');
            }
            $page = ctype_digit(strval($request->query('page', 1))) ? $request->query('page', 1) : 1;
            $limit = ctype_digit(strval($request->query('limit', 10))) ? $request->query('limit', 10) : 1;
            if ($limit > Constants::MAX_ITEMS_LIMIT) {
                $limit = Constants::MAX_ITEMS_LIMIT;
            }

            $commentComments = $contentComment->comments()->with('user', 'user.profile_picture', 'user.roles', 'contentComment')
            ->orderBy('created_at', 'desc')->paginate($limit, ['*'], 'page', $page);
            return $this->respondWithSuccess('comment comments retrieved successfully', [
                'commentComments' => $commentComments,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function updateContentCommentComment(Request $request, $comment_comment_id)
    {
        try {
            $validator = Validator::make(array_merge($request->all(), ['id' => $comment_comment_id]), [
                'id' => ['required', 'string', 'exists:content_comment_comments,id'],
                'comment' => ['required', 'string'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }
   
            //make sure user owns comment comment
            $contentCommentComment = ContentCommentComment::where('id', $comment_comment_id)
            ->where('user_id', $request->user()->id)
            ->with('user', 'user.profile_picture', 'user.roles', 'contentComment')
            ->first();
            if ( is_null($contentCommentComment)) {
                return $this->respondBadRequest('You do not have permission to update this comment');
            }    

            $contentCommentComment->comment = $request->comment;
            $contentCommentComment->save();

            return $this->respondWithSuccess('Comment comment has been updated successfully', [
                'comment' => $contentCommentComment->with('contentComment')->first(),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function deleteContentCommentComment(Request $request, $comment_comment_id)
    {
        try {
            $validator = Validator::make(array_merge($request->all(), ['id' => $comment_comment_id]), [
                'id' => ['required', 'string', 'exists:content_comment_comments,id'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }
               
            //make sure user owns comment
            $contentCommentComment = ContentCommentComment::where('id', $comment_comment_id)
            ->where('user_id', $request->user()->id)
            ->first();

            if ( is_null($contentCommentComment)) {
                return $this->respondBadRequest('You do not have permission to delete this comment');
            } 
            $contentCommentComment->delete();
            return $this->respondWithSuccess('Comment deleted successfully', [
                'comment' => $contentCommentComment,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }
}
