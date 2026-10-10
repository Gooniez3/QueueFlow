<?php

namespace App\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;

final class QueueFlowQrRenderer
{
    public function render(string $credential): string
    {
        return (new Builder(
            writer: new SvgWriter,
            data: $credential,
            size: 112,
            margin: 4,
        ))->build()->getDataUri();
    }
}
