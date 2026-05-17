<?php
/**
 * PDFHelper.php - مساعد لإنشاء ملفات PDF باستخدام TCPDF
 * يتطلب وجود مكتبة TCPDF في public/libs/tcpdf/
 */

// تحميل TCPDF
$tcpdfPath = PUBLIC_PATH . 'libs/tcpdf/tcpdf.php';
if (file_exists($tcpdfPath)) {
    require_once $tcpdfPath;
} else {
    // في حال عدم وجود المكتبة، نقوم بتعريف كلاس وهمي لتجنب أخطاء الكود حتى يتم رفع المكتبة
    if (!class_exists('TCPDF')) {
        class TCPDF {
            public function __construct() { die('Error: TCPDF library not found in public/libs/tcpdf/'); }
        }
    }
}

class PDFHelper {
    
    /**
     * إنشاء كشف حساب عميل كملف PDF
     */
    public static function generateCustomerStatement($customer, $transactions, $openingBalance, $from, $to, $company) {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        $pdf->SetCreator($company['company_name'] ?? 'نظام الفواتير');
        $pdf->SetAuthor($company['company_name'] ?? 'نظام الفواتير');
        $pdf->SetTitle('كشف حساب عميل - ' . $customer['name']);
        
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        $pdf->AddPage('P', 'A4');
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->SetRTL(true);
        
        // الشعار
        $logoPath = !empty($company['logo_path']) ? PUBLIC_PATH . $company['logo_path'] : '';
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 15, 5, 30, 0);
        }
        
        $pdf->SetY(10);
        $pdf->SetFont('dejavusans', 'B', 16);
        $pdf->Cell(0, 10, $company['company_name'] ?? 'شركة', 0, 1, 'C');
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->Cell(0, 5, $company['address'] ?? '', 0, 1, 'C');
        $pdf->Cell(0, 5, 'هاتف: ' . ($company['phone'] ?? ''), 0, 1, 'C');
        $pdf->Ln(5);
        
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, 'كشف حساب عميل', 0, 1, 'C');
        $pdf->Ln(5);
        
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->MultiCell(0, 6, "العميل: " . $customer['name'] . "\nالهاتف: " . $customer['phone'] . "\nالمنطقة: " . $customer['area'], 0, 'R');
        $pdf->Cell(0, 6, "الفترة من: $from إلى: $to", 0, 1, 'R');
        $pdf->Ln(5);
        
        $html = '<table border="1" cellpadding="5" style="border-collapse: collapse; width: 100%; font-size: 10pt;">
                    <thead>
                        <tr style="background-color: #f2f2f2; font-weight: bold;">
                            <th style="width: 15%; text-align: center;">التاريخ</th>
                            <th style="width: 35%; text-align: center;">البيان</th>
                            <th style="width: 15%; text-align: center;">مدين</th>
                            <th style="width: 15%; text-align: center;">دائن</th>
                            <th style="width: 20%; text-align: center;">الرصيد</th>
                         </tr>
                    </thead>
                    <tbody>';
        
        $balance = $openingBalance;
        $html .= '<tr>
                    <td style="text-align: center;">' . $from . '</td>
                    <td style="text-align: right;"><strong>رصيد أول المدة</strong></td>
                    <td style="text-align: center;">' . ($openingBalance > 0 ? number_format($openingBalance, 2) : '0.00') . '</td>
                    <td style="text-align: center;">' . ($openingBalance < 0 ? number_format(abs($openingBalance), 2) : '0.00') . '</td>
                    <td style="text-align: center;">' . number_format($balance, 2) . '</td>
                  </tr>';
        
        foreach ($transactions as $trans) {
            if ($trans['type'] == 'invoice') {
                $balance += $trans['amount'];
                $html .= '<tr>
                            <td style="text-align: center;">' . $trans['date'] . '</td>
                            <td style="text-align: right;">فاتورة رقم ' . $trans['ref'] . '</td>
                            <td style="text-align: center;">' . number_format($trans['amount'], 2) . '</td>
                            <td style="text-align: center;">0.00</td>
                            <td style="text-align: center;">' . number_format($balance, 2) . '</td>
                          </tr>';
            } elseif ($trans['type'] == 'payment') {
                $balance -= $trans['paid'];
                $html .= '<tr>
                            <td style="text-align: center;">' . $trans['date'] . '</td>
                            <td style="text-align: right;">دفعة قسط / ' . $trans['ref'] . '</td>
                            <td style="text-align: center;">0.00</td>
                            <td style="text-align: center;">' . number_format($trans['paid'], 2) . '</td>
                            <td style="text-align: center;">' . number_format($balance, 2) . '</td>
                          </tr>';
            }
        }
        
        $html .= '<tr style="background-color: #f9f9f9; font-weight: bold;">
                    <td colspan="4" style="text-align: left;">الرصيد الحالي</td>
                    <td style="text-align: center;">' . number_format($balance, 2) . '</td>
                  </tr>';
        $html .= '</tbody></table>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output("customer_statement_{$customer['id']}.pdf", 'D');
        exit;
    }
    
    /**
     * إنشاء كشف حساب مورد كملف PDF
     */
    public static function generateSupplierStatement($supplier, $transactions, $openingBalance, $from, $to, $company) {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator($company['company_name'] ?? 'نظام المشتريات');
        $pdf->SetTitle('كشف حساب مورد - ' . $supplier['name']);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage('P', 'A4');
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->SetRTL(true);
        
        $logoPath = !empty($company['logo_path']) ? PUBLIC_PATH . $company['logo_path'] : '';
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 15, 5, 30, 0);
        }
        
        $pdf->SetY(10);
        $pdf->SetFont('dejavusans', 'B', 16);
        $pdf->Cell(0, 10, $company['company_name'] ?? 'شركة', 0, 1, 'C');
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->Cell(0, 5, $company['address'] ?? '', 0, 1, 'C');
        $pdf->Cell(0, 5, 'هاتف: ' . ($company['phone'] ?? ''), 0, 1, 'C');
        $pdf->Ln(5);
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, 'كشف حساب مورد', 0, 1, 'C');
        $pdf->Ln(5);
        
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->MultiCell(0, 6, "المورد: " . $supplier['name'] . "\nالهاتف: " . $supplier['phone'], 0, 'R');
        $pdf->Cell(0, 6, "الفترة من: $from إلى: $to", 0, 1, 'R');
        $pdf->Ln(5);
        
        $html = '<table border="1" cellpadding="5" style="border-collapse: collapse; width: 100%; font-size: 10pt;">
                    <thead>
                        <tr style="background-color: #f2f2f2; font-weight: bold;">
                            <th style="width: 15%; text-align: center;">التاريخ</th>
                            <th style="width: 35%; text-align: center;">البيان</th>
                            <th style="width: 15%; text-align: center;">مدين (لنا)</th>
                            <th style="width: 15%; text-align: center;">دائن (علينا)</th>
                            <th style="width: 20%; text-align: center;">الرصيد</th>
                         </tr>
                    </thead>
                    <tbody>';
        
        $balance = $openingBalance;
        $html .= '<tr>
                    <td style="text-align: center;">' . $from . '</td>
                    <td style="text-align: right;"><strong>رصيد أول المدة</strong></td>
                    <td style="text-align: center;">' . ($openingBalance > 0 ? number_format($openingBalance, 2) : '0.00') . '</td>
                    <td style="text-align: center;">' . ($openingBalance < 0 ? number_format(abs($openingBalance), 2) : '0.00') . '</td>
                    <td style="text-align: center;">' . number_format($balance, 2) . '</td>
                  </tr>';
        
        foreach ($transactions as $trans) {
            if ($trans['type'] == 'invoice') {
                $balance += $trans['amount'];
                $html .= '<tr>
                            <td style="text-align: center;">' . $trans['date'] . '</td>
                            <td style="text-align: right;">فاتورة مشتريات رقم ' . $trans['ref'] . '</td>
                            <td style="text-align: center;">' . number_format($trans['amount'], 2) . '</td>
                            <td style="text-align: center;">0.00</td>
                            <td style="text-align: center;">' . number_format($balance, 2) . '</td>
                          </tr>';
            } elseif ($trans['type'] == 'payment') {
                $balance -= $trans['paid'];
                $html .= '<tr>
                            <td style="text-align: center;">' . $trans['date'] . '</td>
                            <td style="text-align: right;">دفعة نقدية</td>
                            <td style="text-align: center;">0.00</td>
                            <td style="text-align: center;">' . number_format($trans['paid'], 2) . '</td>
                            <td style="text-align: center;">' . number_format($balance, 2) . '</td>
                          </tr>';
            }
        }
        
        $html .= '<tr style="background-color: #f9f9f9; font-weight: bold;">
                    <td colspan="4" style="text-align: left;">الرصيد الحالي</td>
                    <td style="text-align: center;">' . number_format($balance, 2) . '</td>
                  </tr>';
        $html .= '</tbody></table>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output("supplier_statement_{$supplier['id']}.pdf", 'D');
        exit;
    }
    
    /**
     * طباعة إيصالات الأقساط (PDF)
     */
    public static function generateInstallmentReceipt($installments, $company) {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator($company['company_name'] ?? 'نظام الأقساط');
        $pdf->SetTitle('إيصالات الأقساط');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        foreach ($installments as $inst) {
            $pdf->AddPage('P', array(210, 99)); 
            $pdf->SetFont('dejavusans', '', 8);
            $pdf->SetRTL(true);
            
            // الهيدر
            $logoPath = !empty($company['logo_path']) ? PUBLIC_PATH . $company['logo_path'] : '';
            if (file_exists($logoPath)) {
                $pdf->Image($logoPath, 10, 5, 20, 0);
            }
            $pdf->SetY(8);
            $pdf->SetFont('dejavusans', 'B', 11);
            $pdf->Cell(0, 6, $company['company_name'] ?? 'شركة', 0, 1, 'C');
            $pdf->SetFont('dejavusans', '', 7);
            $pdf->Cell(0, 4, ($company['address'] ?? '') . ' | هاتف: ' . ($company['phone'] ?? ''), 0, 1, 'C');
            
            // مستطيل الحالة
            $pdf->SetFillColor(153, 0, 0);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Rect(10, 22, 40, 6, 'F');
            $pdf->SetXY(10, 22);
            $pdf->SetFont('dejavusans', 'B', 9);
            $pdf->Cell(40, 6, "قسط {$inst['installment_number']}/{$inst['total_installments']}", 0, 0, 'C');
            $pdf->SetTextColor(0, 0, 0);
            
            // البيانات
            $pdf->SetY(30);
            $pdf->SetFont('dejavusans', '', 8);
            
            $html = '<table cellpadding="2">
                <tr>
                    <td width="50%">
                        <strong>العميل:</strong> ' . htmlspecialchars($inst['customer_name']) . '<br>
                        <strong>الهاتف:</strong> ' . $inst['phone'] . '<br>
                        <strong>المنطقة:</strong> ' . htmlspecialchars($inst['area']) . '
                    </td>
                    <td width="50%" style="text-align: left;">
                        <strong>فاتورة:</strong> ' . $inst['invoice_number'] . '<br>
                        <strong>التاريخ:</strong> ' . $inst['invoice_date'] . '<br>
                        <strong>الاستحقاق:</strong> <span style="color:red;">' . $inst['due_date'] . '</span>
                    </td>
                </tr>
            </table>';
            $pdf->writeHTML($html, true, false, true, false, '');
            
            // جدول المنتجات
            $pdf->SetY(50);
            $items = $inst['items'] ?? [];
            if (!empty($items)) {
                $html = '<table border="1" cellpadding="2" style="font-size:7pt; width:100%; border-collapse:collapse;">
                            <tr style="background-color:#eee; font-weight:bold;">
                                <th width="40%">المنتج</th><th width="15%">الوحدة</th><th width="15%">الكمية</th><th width="15%">السعر</th><th width="15%">الإجمالي</th>
                            </tr>';
                foreach ($items as $item) {
                    $html .= '<tr>
                                <td>' . htmlspecialchars($item['product_name']) . '</td>
                                <td>' . ($item['unit_name'] ?? '') . '</td>
                                <td style="text-align:center;">' . $item['quantity'] . '</td>
                                <td style="text-align:center;">' . number_format($item['unit_price'], 2) . '</td>
                                <td style="text-align:center;">' . number_format($item['total'], 2) . '</td>
                              </tr>';
                }
                $html .= '</table>';
                $pdf->writeHTML($html, true, false, true, false, '');
            }
            
            // المبالغ
            $pdf->SetY(80);
            $pdf->SetFont('dejavusans', 'B', 10);
            $pdf->Cell(0, 8, "المبلغ المطلوب: " . number_format($inst['amount'], 2) . " ج.م", 0, 1, 'C', false);
            
            $pdf->SetFont('dejavusans', '', 7);
            $pdf->Cell(0, 4, "مندوب المبيعات: " . ($inst['sales_rep_name'] ?? ''), 0, 1, 'R');
        }
        
        $pdf->Output("receipts.pdf", 'D');
        exit;
    }
}
