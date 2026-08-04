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
 * Generate a QR code PNG without depending on a full ImageMagick install.
 * Portable PHP often has Imagick loaded but missing IM_MOD_RL_png_.dll, which
 * breaks simplesoftwareio/simple-qrcode format('png'). This helper uses GD.
 *
 * @param  string  $content  Text/URL to encode
 * @param  string  $path     Absolute file path ending in .png
 * @param  int     $size     Output image size in pixels
 * @return void
 */
if (! function_exists('generate_qrcode_png')) {
    function generate_qrcode_png(string $content, string $path, int $size = 300): void
    {
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Try Imagick-based generator first (works when ImageMagick PNG module is present).
        try {
            if (extension_loaded('imagick')) {
                \SimpleSoftwareIO\QrCode\Facades\QrCode::size($size)
                    ->format('png')
                    ->generate($content, $path);

                if (is_file($path) && filesize($path) > 0) {
                    return;
                }
            }
        } catch (\Throwable $e) {
            // Fall through to GD renderer.
        }

        if (! function_exists('imagecreatetruecolor')) {
            throw new \RuntimeException(
                'Cannot generate QR code PNG: Imagick PNG support failed and GD is not available.'
            );
        }

        $qrCode = \BaconQrCode\Encoder\Encoder::encode(
            $content,
            \BaconQrCode\Common\ErrorCorrectionLevel::L()
        );
        $matrix = $qrCode->getMatrix();
        $moduleCount = $matrix->getWidth();
        $margin = 2;
        $totalModules = $moduleCount + ($margin * 2);
        $moduleSize = max(1, intdiv($size, $totalModules));
        $imageSize = $moduleSize * $totalModules;

        $image = imagecreatetruecolor($imageSize, $imageSize);
        if ($image === false) {
            throw new \RuntimeException('Failed to create GD image for QR code.');
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $white);

        for ($y = 0; $y < $moduleCount; $y++) {
            for ($x = 0; $x < $moduleCount; $x++) {
                // Dark modules are 1 in BaconQrCode matrix.
                if ($matrix->get($x, $y) === 1) {
                    $x1 = ($x + $margin) * $moduleSize;
                    $y1 = ($y + $margin) * $moduleSize;
                    imagefilledrectangle(
                        $image,
                        $x1,
                        $y1,
                        $x1 + $moduleSize - 1,
                        $y1 + $moduleSize - 1,
                        $black
                    );
                }
            }
        }

        if (! imagepng($image, $path)) {
            imagedestroy($image);
            throw new \RuntimeException('Failed to write QR code PNG to: ' . $path);
        }

        imagedestroy($image);
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
