<?php

if(! function_exists('currencyFormat')) {
    function currencyFormat($currency)
    {
        return Setting::get('currency_code').number_format($currency, 2);
    }
}

/**
 * Attach image to a Spatie HasMedia model from file upload or camera base64 capture.
 * Saves to public/uploads disk so images work without storage symlink.
 *
 * @param  \Spatie\MediaLibrary\HasMedia  $model
 * @param  \Illuminate\Http\Request|null  $request
 * @param  string  $collection
 * @return void
 */
if (! function_exists('attachMediaFromRequest')) {
    function attachMediaFromRequest($model, $request = null, string $collection = 'user'): void
    {
        $request = $request ?: request();

        try {
            if ($request->hasFile('image')) {
                $model->clearMediaCollection($collection);
                $model->addMediaFromRequest('image')
                    ->toMediaCollection($collection, 'public_uploads');
                return;
            }

            $captured = $request->input('captured_image');
            if (!blank($captured) && \Illuminate\Support\Str::startsWith($captured, 'data:image')) {
                $image = str_replace(
                    ['data:image/png;base64,', 'data:image/jpeg;base64,', ' '],
                    ['', '', '+'],
                    $captured
                );
                $imageName = $collection . '_' . \Illuminate\Support\Str::random(12) . '.png';
                $tempPath = storage_path('app/' . $imageName);
                \Illuminate\Support\Facades\File::put($tempPath, base64_decode($image));

                if (\Illuminate\Support\Facades\File::exists($tempPath)) {
                    $model->clearMediaCollection($collection);
                    $model->addMedia($tempPath)
                        ->usingFileName($imageName)
                        ->toMediaCollection($collection, 'public_uploads');
                    \Illuminate\Support\Facades\File::delete($tempPath);
                }
            }
        } catch (\Exception $e) {
            \Log::warning('attachMediaFromRequest failed: ' . $e->getMessage());
        }
    }
}

/**
 * Get domain (host without sub-domain)
 *
 * @param null $url
 * @return string
 */
function getDomain($url = null)
{
    if (!empty($url)) {
        $host = parse_url($url, PHP_URL_HOST);
    } else {
        $host = getHost();
    }

    $tmp = explode('.', $host);
    if (count($tmp) > 2) {
        $itemsToKeep = count($tmp) - 2;
        $tlds = config('tlds');
        if (isset($tmp[$itemsToKeep]) && isset($tlds[$tmp[$itemsToKeep]])) {
            $itemsToKeep = $itemsToKeep - 1;
        }
        for ($i = 0; $i < $itemsToKeep; $i++) {
            \Illuminate\Support\Arr::forget($tmp, $i);
        }
        $domain = implode('.', $tmp);
    } else {
        $domain = @implode('.', $tmp);
    }

    return $domain;
}

/**
 * Get host (domain with sub-domain)
 *
 * @param null $url
 * @return array|mixed|string
 */
function getHost($url = null)
{
    if (!empty($url)) {
        $host = parse_url($url, PHP_URL_HOST);
    } else {
        $host = (trim(request()->server('HTTP_HOST')) != '') ? request()->server('HTTP_HOST') : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
    }

    if ($host == '') {
        $host = parse_url(url()->current(), PHP_URL_HOST);
    }

    return $host;
}

function isValidJson($string)
{
    try {
        json_decode($string);
    } catch (\Exception $e) {
        return false;
    }

    return (json_last_error() == JSON_ERROR_NONE);
}
