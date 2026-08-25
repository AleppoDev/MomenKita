<?php

namespace App\Http\Controllers;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class QrController extends Controller
{
    /**
     * Kod QR menuju ke halaman utama. Aras pembetulan ralat tinggi supaya
     * masih boleh diimbas walaupun kad tercalar atau cahaya dewan malap.
     */
    public function show(Request $request): Response
    {
        $format = $request->query('format') === 'svg' ? 'svg' : 'png';
        $size = min(2000, max(200, $request->integer('size') ?: 900));

        $result = (new Builder(
            writer: $format === 'svg' ? new SvgWriter() : new PngWriter(),
            data: route('landing'),
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $size,
            margin: 24,
        ))->build();

        return response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
