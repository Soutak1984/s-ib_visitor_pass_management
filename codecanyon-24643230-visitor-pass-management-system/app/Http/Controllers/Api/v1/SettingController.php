<?php
/**
 * Created by PhpStorm.
 * User: dipok
 * Date: 20/4/20
 * Time: 2:41 PM
 */

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\SettingResource;
use App\Models\Setting;

class SettingController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $settings     = Setting::get()->pluck('value', 'key');

        return response()->json([
            'status' => 200,
            'data'   => new SettingResource($settings),
        ], 200);
    }
}
