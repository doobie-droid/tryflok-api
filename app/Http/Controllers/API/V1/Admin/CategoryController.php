<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function create(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            $category = Category::create([
                'public_id' => uniqid(rand()),
                'name' => $request->name,
            ]);
            return $this->respondWithSuccess("Category created successfully.", [
                'category' => new CategoryResource($category),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError("Oops, an error occurred. Please try again later.");
        }
    }

    public function update(Request $request, $public_id)
    {
        try {
            $validator = Validator::make(array_merge($request->all(), ['public_id' => $public_id]), [
                'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
                'public_id' => ['required', 'string', 'exists:categories,public_id'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }
            $category = Category::where('public_id', $public_id)->first();
            $category->name = $request->name;
            $category->save();

            return $this->respondWithSuccess("Category updated successfully.", [
                'category' => new CategoryResource($category),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError("Oops, an error occurred. Please try again later.");
        }
    }
}
