<?php

declare(strict_types=1);

namespace App\Providers;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use RobThree\Auth\Providers\Qr\IQRCodeProvider;

class ChillerlanQRCodeProvider implements IQRCodeProvider
{
    public function getQRCodeImage(string $qrText, int $size): string
    {
        $options = new QROptions([
            'version' => \chillerlan\QRCode\Common\Version::AUTO,
            'outputInterface' => \chillerlan\QRCode\Output\QRGdImagePNG::class,
            'eccLevel' => \chillerlan\QRCode\Common\EccLevel::L,
            'scale' => 5,
            'outputBase64' => false,
        ]);

        $qrcode = new QRCode($options);
        return $qrcode->render($qrText);
    }

    public function getMimeType(): string
    {
        return 'image/png';
    }
}
