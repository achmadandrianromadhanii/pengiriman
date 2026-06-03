<?php

namespace App\Services;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QrCodeService
{
    public function makeDataUri(string $text, int $size = 160): string
    {
        $options = new QROptions([
            'version' => 5,
            'outputInterface' => QRGdImagePNG::class,
            'eccLevel' => EccLevel::L,
            'scale' => 5,
            'outputBase64' => true,
            'imageTransparent' => false,
        ]);

        return (new QRCode($options))->render($text);
    }
}
