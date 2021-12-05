<?php

namespace App\Http\Controllers\API\V1;

use App\Constants\Constants;
use App\Constants\Roles;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApprovalResource;
use App\Jobs\Collection\ApproveCollectionChildren as ApproveCollectionChildrenJob;
use App\Models\Approval;
use App\Models\Collection;
use App\Models\Content;
use App\Services\Storage\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ApprovalController extends Controller
{
    public function create(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'items' => ['required',],
                'items.*.public_id' => ['required', 'string', ],
                'items.*.type' => ['required', 'string', 'regex:(collection|content)',],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            foreach ($request->items as $item) {
                //validate that the content or collection exists and they belong to the user making request
                $itemModel = null;
                switch ($item['type']) {
                    case 'content':
                        $itemModel = Content::where('public_id', $item['public_id'])->where('user_id', $request->user()->id)->first();
                        break;
                    case 'collection':
                        $itemModel = Collection::where('public_id', $item['public_id'])->where('user_id', $request->user()->id)->first();
                        break;
                }
                if (is_null($itemModel)) {
                    return $this->respondBadRequest('Item with public_id [' . $item['public_id'] . '] does not exist or is not owned by you.');
                }
            }

            //create an approval request for each of them
            $approval_requests = [];
            foreach ($request->items as $item) {
                switch ($item['type']) {
                    case 'content':
                        $itemModel = Content::where('public_id', $item['public_id'])->where('user_id', $request->user()->id)->first();
                        break;
                    case 'collection':
                        $itemModel = Collection::where('public_id', $item['public_id'])->where('user_id', $request->user()->id)->first();
                        break;
                }
                $approval_request = Approval::where('approvable_type', $item['type'])->where('approvable_id', $itemModel->id)->first();
                if (is_null($approval_request)) {
                    $approval_request = $itemModel->approvalRequest()->create([
                        'public_id' => uniqid(rand()),
                        'user_id' => $itemModel->user_id,
                        'status' => 'pending',
                        'needs_action_from' => 'admin',
                    ]);
                } else {
                    if ($approval_request->status !== 'approved') {
                        $approval_request->status = 'pending';
                        $approval_request->needs_action_from = 'admin';
                        $approval_request->save();
                    }
                }

                $approval_requests[] = $approval_request;
            }

            return $this->respondWithSuccess('Approvals requested successfully', [
                'approval_requests' => $approval_requests,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function getUserRequests(Request $request)
    {
        try {
            $page = ctype_digit(strval($request->query('page', 1))) ? $request->query('page', 1) : 1;
            $limit = ctype_digit(strval($request->query('limit', 10))) ? $request->query('limit', 10) : 1;
            if ($limit > Constants::MAX_ITEMS_LIMIT) {
                $limit = Constants::MAX_ITEMS_LIMIT;
            }
            $status = $request->query('status', '');

            $approval_requests = Approval::where('user_id', $request->user()->id);

            if ($status != '') {
                $status = explode(',', urldecode($status));
                $status = array_diff($status, ['']);//get rid of empty values
                $approval_requests = $approval_requests->whereIn('status', $status);
            }
            $approval_requests = $approval_requests->with('approvable', 'approvable.cover')->orderBy('created_at', 'asc')->paginate($limit, ['*'], 'page', $page);

            return $this->respondWithSuccess('Approval requests retrieved successfully', [
                'approval_requests' => ApprovalResource::collection($approval_requests),
                'current_page' => (int) $approval_requests->currentPage(),
                'items_per_page' => (int) $approval_requests->perPage(),
                'total' => (int) $approval_requests->total(),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function sendApprovalMessage(Request $request, $public_id)
    {
        try {
            $validator = Validator::make(array_merge($request->all(), ['public_id' => $public_id]), [
                'public_id' => ['required', 'exists:approvals,public_id'],
                'message' => ['required', 'string', 'max:500'],
                'attachments' => ['sometimes',],
                'attachments.*' => ['required', 'file', 'max:5120'],//5MB
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $approval_request = Approval::where('public_id', $public_id)->first();
            if (! $request->user()->hasRole(Roles::SUPER_ADMIN) && ! $request->user()->hasRole(Roles::ADMIN) && $request->user()->id !== $approval_request->user_id) {
                return $this->respondBadRequest('You cannot send a message to this request because you do not own it nor are you an admin');
            }

            $message = $approval_request->messages()->create([
                'public_id' => uniqid(rand()),
                'message' => $request->message,
                'from' => $request->user()->id === $approval_request->user_id ? 'creator' : 'admin',
                'to' => $request->user()->id === $approval_request->user_id ? 'admin' : 'creator',
            ]);

            //add files if any
            if ($request->hasfile('attachments')) {
                $storage = new Storage('cloudinary');
                foreach ($request->file('attachments') as $file) {
                    $uploadedImageData = $storage->upload($file->getRealPath(), 'approval-requests/messages/attachments');
                    $message->attachments()->create([
                        'public_id' => uniqid(rand()),
                        'storage_provider' => 'cloudinary',
                        'storage_provider_id' => $uploadedImageData['storage_provider_id'],
                        'url' => $uploadedImageData['url'],
                        'purpose' => 'message-attachment',
                        'asset_type' => 'file',
                        'mime_type' => $file->getMimeType(),
                    ]);
                }
            }
            $message->attachments = $message->attachments()->get();
            return $this->respondWithSuccess('Message sent successfully.', [
                'message' => $message,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function getApprovalMessages(Request $request, $public_id)
    {
        try {
            $validator = Validator::make(array_merge($request->all(), ['public_id' => $public_id]), [
                'public_id' => ['required', 'exists:approvals,public_id'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $approval_request = Approval::where('public_id', $public_id)->first();
            if (! $request->user()->hasRole(Roles::SUPER_ADMIN) && ! $request->user()->hasRole(Roles::ADMIN) && $request->user()->id !== $approval_request->user_id) {
                return $this->respondBadRequest('You cannot view messages for this request because you do not own it nor are you an admin');
            }

            $page = ctype_digit(strval($request->query('page', 1))) ? $request->query('page', 1) : 1;
            $limit = ctype_digit(strval($request->query('limit', 10))) ? $request->query('limit', 10) : 1;
            if ($limit > Constants::MAX_ITEMS_LIMIT) {
                $limit = Constants::MAX_ITEMS_LIMIT;
            }

            $messages = $approval_request->messages()->with('attachments')->orderBy('created_at', 'desc')->paginate($limit, ['*'], 'page', $page);

            return $this->respondWithSuccess('Messages retrieved successfully.', [
                'messages' => $messages,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function getAll(Request $request)
    {
        try {
            if (! $request->user()->hasRole(Roles::SUPER_ADMIN) && ! $request->user()->hasRole(Roles::ADMIN)) {
                return $this->respondBadRequest('Only administrators can view this data');
            }
            $page = ctype_digit(strval($request->query('page', 1))) ? $request->query('page', 1) : 1;
            $limit = ctype_digit(strval($request->query('limit', 10))) ? $request->query('limit', 10) : 1;
            if ($limit > Constants::MAX_ITEMS_LIMIT) {
                $limit = Constants::MAX_ITEMS_LIMIT;
            }

            $status = $request->query('status', '');

            $approval_requests = Approval::with('approvable', 'approvable.cover');

            if ($status != '') {
                $status = explode(',', urldecode($status));
                $status = array_diff($status, ['']);//get rid of empty values
                $approval_requests = $approval_requests->whereIn('status', $status);
            }

            $approval_requests = $approval_requests->orderBy('created_at', 'asc')->paginate($limit, ['*'], 'page', $page);

            return $this->respondWithSuccess('Approvals retrieved successfully', [
                'approval_requests' => ApprovalResource::collection($approval_requests),
                'current_page' => (int) $approval_requests->currentPage(),
                'items_per_page' => (int) $approval_requests->perPage(),
                'total' => (int) $approval_requests->total(),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function update(Request $request, $public_id)
    {
        try {
            if (! $request->user()->hasRole(Roles::SUPER_ADMIN) && ! $request->user()->hasRole(Roles::ADMIN)) {
                return $this->respondBadRequest('Only administrators can access this endpoint');
            }
            $validator = Validator::make(array_merge($request->all(), ['public_id' => $public_id]), [
                'public_id' => ['required', 'exists:approvals,public_id'],
                'approval_action' => ['required', 'string', 'regex:(approve|decline)',],
            ]);

            $approval_request = Approval::where('public_id', $public_id)->first();

            switch ($request->approval_action) {
                case 'decline':
                    $approval_request->status = 'declined';
                    $approval_request->needs_action_from = 'user';
                    $approval_request->save();
                    //TO DO: send mail telling creator it was declined
                    break;
                case 'approve':
                    $approval_request->status = 'approved';
                    $approval_request->needs_action_from = 'user';
                    $approval_request->save();
                    $approvable = $approval_request->approvable()->first();
                    $approvable->approved_by_admin = 1;
                    $approvable->save();
                    if ($approval_request->approvable_type === 'collection') {
                        ApproveCollectionChildrenJob::dispatch([
                            'collection' => $approvable,
                        ]);
                    }
                    //TO DO: send mail telling creator it was approved
                    break;
                default:
                    Log::info('Did not match any');
            }

            return $this->respondWithSuccess('Approval updated successfully', [
                'approval_request' => new ApprovalResource($approval_request),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }
}
