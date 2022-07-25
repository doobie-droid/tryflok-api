<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Content;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;


class ContentPollController extends Controller

{
    public function createPoll(Request $request, $content_id)
    {
        try {
            $validator = Validator::make(array_merge($request->all(), ['id' => $content_id]), [
                'id' => ['required', 'string'],
                'question' => ['required', 'string', 'max:200', 'min:1'],
                'closes_at' => ['required'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $content = Content::where('id', $content_id)->where('user_id', $request->user()->id)->first();
            if (is_null($content)) {
                return $this->respondBadRequest('You do not have permission to create a poll for this content');
            }

            $poll = $content->polls()->create([
                'question' => $request->question,
                'closes_at' => $request->closes_at,
                'user_id' => $content->user_id,
            ]);

            $input = $request->all();
            for ($i = 0; $i <= count($input['option']); $i++)
            {
                $options = [
                        'content_poll_id' => $poll->id,
                        'option' => $input['option'][$i],
                ];
            }
            
            $option = $poll->pollOptions()->create($options);

            $poll = ContentPoll::with('content', 'options')->where('id', $poll->id)->first();
            return $this->respondWithSuccess('Poll has been created successfully', [
            'poll' => $poll,
            ]);           

        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }
}
