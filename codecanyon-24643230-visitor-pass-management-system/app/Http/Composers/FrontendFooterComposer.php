<?php

namespace App\Http\Composers;

use App\Enums\Status;
use App\Models\Language;
use Illuminate\View\View;

class FrontendFooterComposer
{
    public function compose(View $view)
    {
        $view->with('footermenus', null);
        $view->with('language', Language::where('status', Status::ACTIVE)->get());

    }
}
