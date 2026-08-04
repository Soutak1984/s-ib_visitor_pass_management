<?php

namespace App\Http\Controllers\Admin;
use App;
use App\Http\Controllers\BackendController;
use Illuminate\Http\RedirectResponse;

class LocalizationController extends BackendController
{
    /**
     * @param $locale
     * @return RedirectResponse
     */
    public function index($locale)
    {
        session()->put('applocale', $locale);
        return redirect()->back();
    }
}
