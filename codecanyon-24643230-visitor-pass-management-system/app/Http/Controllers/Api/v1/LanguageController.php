<?php
/**
 * Created by PhpStorm.
 * User: dipok
 * Date: 20/4/20
 * Time: 2:41 PM
 */

namespace App\Http\Controllers\Api\v1;

use App\Enums\Status;
use App\Models\Language;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\LanguageResource;

class LanguageController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $languages = LanguageResource::collection(Language::where('status',Status::ACTIVE)->get());

        return response()->json([
            'status' => 200,
            'data'   => $languages,
        ], 200);
    }
}
