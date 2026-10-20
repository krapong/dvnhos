<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SopController extends Controller
{
    public function index()
    {
        $sops = \App\Models\SopDocument::latest()->paginate(20);
        return view('frontend.pages.sops', compact('sops'));
    }

    public function download($id)
    {
        $sop = \App\Models\SopDocument::findOrFail($id);
        $filePath = storage_path('app/public/' . $sop->file_path);

        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }

        try {
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'default_font' => 'garuda', // use a built-in thai font
                'margin_top' => 15,
            ]);

            $mpdf->autoScriptToLang = true;
            $mpdf->autoLangToFont = true;

            // Set watermark text (2 lines) with 22pt size
            $watermarkText = "เอกสารนี้ฉบับทางการจะเป็นเอกสารอิเลคทรอนิกส์อยู่ในระบบเครือข่ายสารสนเทศ\nโรงพยาบาลเทพรัตนเวชชานุกูล เฉลิมพระเกียรติ ๖๐ พรรษา เท่านั้นห้ามสำเนาหรือทำซ้ำโดยไม่ได้รับอนุญาต";
            
            $mpdf->SetWatermarkText($watermarkText, 0.15, 22); // alpha 0.15, size 22pt
            $mpdf->showWatermarkText = true;
            $mpdf->watermarkTextAlpha = 0.15;

            // Print document code on Header if available
            if (!empty($sop->code)) {
                $mpdf->SetHeader('<div style="text-align: right; font-weight: bold; color: #333333; font-size: 10pt;">รหัสเอกสาร: ' . htmlspecialchars($sop->code) . '</div>');
            }
            
            $pageCount = $mpdf->SetSourceFile($filePath);
            for ($i = 1; $i <= $pageCount; $i++) {
                $tplId = $mpdf->importPage($i);
                $size = $mpdf->getTemplateSize($tplId);
                
                // Determine orientation from template size
                $orientation = isset($size['orientation']) ? $size['orientation'] : ($size['width'] > $size['height'] ? 'L' : 'P');
                
                // mPDF quirk: it expects 'sheet-size' as portrait dimensions (width < height)
                // and then swaps them internally if orientation is 'L'.
                $baseWidth = min($size['width'], $size['height']);
                $baseHeight = max($size['width'], $size['height']);

                $mpdf->AddPageByArray([
                    'orientation' => $orientation,
                    'sheet-size' => [$baseWidth, $baseHeight]
                ]);
                
                $mpdf->useTemplate($tplId);
            }

            // Use original filename if available, otherwise generate one
            $downloadFilename = $sop->original_filename ? $sop->original_filename : 'SOP_' . $sop->id . '.pdf';

            return response()->streamDownload(function() use ($mpdf) {
                echo $mpdf->Output('', 'S');
            }, $downloadFilename, [
                'Content-Type' => 'application/pdf',
            ]);

        } catch (\Exception $e) {
            \Log::error('PDF Watermark error: ' . $e->getMessage());
            // Fallback to direct download if watermarking fails
            $downloadFilename = $sop->original_filename ? $sop->original_filename : 'SOP_' . $sop->id . '.pdf';
            return response()->download($filePath, $downloadFilename);
        }
    }
}
