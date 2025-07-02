<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class DocumentationController extends Controller
{
    /**
     * Menghasilkan PDF dokumentasi untuk download
     */
    public function downloadPdf()
    {
        $pdf = PDF::loadView('documentation');
        
        // Set paper size dan orientation
        $pdf->setPaper('A4', 'portrait');
        
        // Set options untuk PDF
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'Times New Roman',
            'dpi' => 150,
            'defaultPaperSize' => 'a4',
            'isFontSubsettingEnabled' => true,
        ]);

        // Generate nama file
        $filename = 'Dokumentasi_Sistem_Toko_Beras_MVC_' . date('Y-m-d_H-i-s') . '.pdf';

        // Return PDF untuk download
        return $pdf->download($filename);
    }
} 