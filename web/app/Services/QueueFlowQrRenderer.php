<?php

namespace App\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
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

    public function renderQueueEntryUrl(string $url): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($this->renderQueueEntrySvg($url));
    }

    public function renderQueueEntrySvg(string $url): string
    {
        return (new Builder(
            writer: new SvgWriter,
            data: $url,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 720,
            margin: 32,
        ))->build()->getString();
    }
}
