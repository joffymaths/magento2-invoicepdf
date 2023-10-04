<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Joffy\InvoicePdf\Model\Order\Pdf;

use TCPDF;

/**
 * Sales Order Shipment PDF model
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Invoice extends \Magento\Sales\Model\Order\Pdf\Invoice
{
    protected $fileStorageDatabase;

    /**
     * Return PDF document
     *
     * @param array|Collection $invoices
     * @return \Zend_Pdf
     */
    public function getPdf($invoices = [])
    {
        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');
        $pdf = new Layout(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        // set document information
        $pdf->SetTitle('invoice');
        // set default header data
        // $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        // $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        // $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        // $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        $pdf->AddPage();

        $arabic_store = false;
        // Loop through each invoice
        foreach ($invoices as $invoice) {
            $store = $this->_storeManager->getStore('ar');
            if($store->getId() == $invoice->getStoreId()){
                $arabic_store = true;
            }
            $order = $invoice->getOrder();
            $billingAddress = $this->_formatAddress($this->addressRenderer->format($order->getBillingAddress(), 'pdf'));
            $soldTo = implode("<br>",$billingAddress);
            $shippingAddress = $this->_formatAddress($this->addressRenderer->format($order->getShippingAddress(), 'pdf'));
            $shipTo = implode("<br>",$shippingAddress);
            $shippingMethod = $order->getShippingDescription();
            $payment=$this->getPaymentInfo($order);
            $shippingtext = ($arabic_store) ? $this->getConvertedAmount($order->formatPriceTxt($order->getShippingAmount())) : $order->formatPriceTxt($order->getShippingAmount());
            $shippingchargetext = ($arabic_store) ? 'تكلفة التوصيل' : 'Total Shipping Charges';
            $totalShippingChargesText = "("
                . $shippingchargetext
                . " "
                . $shippingtext
                . ")";

            $fontname = \TCPDF_FONTS::addTTFfont($this->_rootDirectory->getAbsolutePath('lib/internal/GnuFreeFont/FreeSerif.ttf'), '', '', 12);
            $pdf->SetFont($fontname, '', 14, '', false);

            $soldToTitle = ($arabic_store)? 'عنوان المشتري'.':' : 'Sold to:';
            $shipToTitle = ($arabic_store)? 'عنوان التوصيل'.':' : 'Ship to:';
            $invoiceTitle = ($arabic_store)? 'الفاتورة رقم'.' ' : 'Invoice ';
            $orderTitle = ($arabic_store)? 'طلب رقم'.' ' : 'Order ';
            $orderdateTitle = ($arabic_store)? 'تاريخ التوصيل'.': ' : 'Order Date: ';

            $customerDetails='
            <table style="width: 100%;background-color: #fff;padding: 10px;">
                <tr style="font-size: 18px; background-color: #737373;border-collapse: collapse;border: 1px solid #808080">
                    <th colspan="3" style="font-size: 13px;border-collapse: collapse; background-color: #737373;border: 1px solid #737373;padding: 10px;text-align: left;color: #fff;" >'.$invoiceTitle.'#' . $invoice->getIncrementId().'<br>'.$orderTitle.'#' . $order->getIncrementId().'<br>'.$orderdateTitle. $this->_localeDate->formatDate(
                        $this->_localeDate->scopeDate(
                            $order->getStore(),
                            $order->getCreatedAt(),
                            true
                        ),
                        \IntlDateFormatter::MEDIUM,
                        false
                    ).'</th>
                </tr>
                <tr style="width: 100%;">
                    <td colspan="2" style="background-color: #EDEBEB;border-collapse: collapse; padding: 15px; color: #000; font-size: 15px;  border: 1px solid #808080; width: 50%;padding: 10px;text-align: left; font-weight: bold;">'.$soldToTitle.'</td>
                    <td colspan="2" style="white-space: nowrap;background-color: #EDEBEB;border-collapse: collapse;font-weight: bold; padding: 15px;color: #000; font-size: 15px;  border: 1px solid #808080;width: 50%;padding: 10px;">'.$shipToTitle.'</td>
                </tr>

                <tr class="row-effect">
                    <td colspan="2" style="font-size: 14px;font-style: Bold;border-collapse: collapse;border-bottom: 1px solid #808080;border-left: 1px solid #808080; padding: 10px; ">'.$soldTo.'</td>
                    <td colspan="2" style="font-size: 14px;font-style: Bold;border-collapse: collapse;border-bottom: 1px solid #808080;border-right: 1px solid #808080; padding: 10px; ">'.$shipTo.'</td>

                </tr>
            </table>';

            // $pdf->setXY(10,15);
            $pdf->writeHTML($customerDetails, true, false, true, false, '');
            $pdf->setXY(10,80);
            $pdf->SetFillColor(237, 235, 235);
            $pdf->SetTextColor(0);
            $pdf->SetDrawColor(128, 128, 128);
            $pdf->SetFont($fontname,'B',12);
            $method1_title = ($arabic_store) ? 'طريقة الدفع'.':' : ' '.__('Payment Method:');
            $pdf->Cell(95, 10, $method1_title, 1, 0, 'L', 1);
            $method2_title = ($arabic_store) ? 'تكلفة التوصيل'.':' : ' '.__('Shipping Method:');
            $pdf->Cell(95, 10, $method2_title, 1, 0, 'L', 1);
            $pdf->Ln();
            $pdf->SetFont($fontname,'R',10);
            $pdf->Cell(95, 10,' '.$payment, 'L=1', 0, 'L',0);
            // $pdf->Cell(95, 10,$shippingMethod, 'R=1', 0, 'L', 0);
            $pdf->Cell(95, 10,$totalShippingChargesText, 'R=1', 0, 'L', 0);
            $pdf->Ln();
            // $pdf->Cell(95, 10,"", 'L=1', 0, 'L', 0);
            // $pdf->Cell(95, 10,$totalShippingChargesText, 'R=1', 0, 'L', 0);
            // $pdf->Ln();
            $pdf->Cell(95, 1,"", 'L=1, B=0', 0, 'L', 0);
            $pdf->Cell(95, 1,"", 'R=1, B=0', 0, 'L', 0);
            $pdf->Ln(10);

            $pdf->SetFillColor(237, 235, 235);
            $pdf->SetTextColor(0);
            $pdf->SetDrawColor(128, 128, 128);
            $pdf->SetFont($fontname,'R',10);
            $product_title = ($arabic_store) ? 'منتج' : 'Products';
            $pdf->Cell(70, 7, $product_title, 'L=1, T=1, B=1', 0, 'L',1);
            $pdf->Cell(45, 7,'SKU', 'T=1, B=1', 0, 'L', 1);
            $price_title = ($arabic_store) ? 'السعر' : ' '.'Price';
            $pdf->Cell(20, 7,$price_title, 'T=1, B=1', 0, 'L', 1);
            $quantity_title = ($arabic_store) ? 'الكميّة' : ' '.'Qty';
            $pdf->Cell(10, 7,$quantity_title, 'T=1, B=1', 0, 'L', 1);
            $pdf->Cell(20, 7,' '.'Tax', 'T=1, B=1', 0, ($arabic_store) ? 'R' : 'L', 1);
            $subtotal_title = ($arabic_store) ? 'المجموع' : ' '.'Subtotal';
            $pdf->Cell(25, 7,$subtotal_title, 'T=1, B=1, R=1', 0, ($arabic_store) ? 'R' : 'L', 1);
            $pdf->Ln();

            // Add items
            foreach ($invoice->getAllItems() as $item) {
                if ($item->getOrderItem()->getParentItem()) {
                    continue;
                }
                $pdf->SetFillColor(255, 255, 255);
                if(strlen($item->getName()) >= 42){
                    $pdf->MultiCell(70, 10, $item->getName(), 0, 0, 'L', 0);
                }
                else{
                    $pdf->Cell(70, 10, $item->getName(), 0, 0, 'L', 0);
                }
                if(strlen($item->getSku()) >= 25){
                    $pdf->MultiCell(45, 10, $item->getSku(), 0, 0, 'L', 0);
                }
                else{
                    $pdf->Cell(45, 10, $item->getSku(), 0, 0, 'L', 0);
                }

                $price = ($arabic_store) ? $this->getConvertedAmount($order->formatPriceTxt($item->getPrice())) : $order->formatPriceTxt($item->getPrice());
                $pdf->Cell(20, 10, ' '.$price, 0, 0, ($arabic_store) ? 'R' : 'L', 0);
                $pdf->Cell(10, 10, ' '.number_format($item->getQty(),0), 0, 0, ($arabic_store) ? 'R' : 'L', 0);
                $tax = ($arabic_store) ? $this->getConvertedAmount($order->formatPriceTxt($item->getTaxAmount())) : $order->formatPriceTxt($item->getTaxAmount());
                $pdf->Cell(20, 10, ' '.$tax, 0, 0, ($arabic_store) ? 'R' : 'L', 0);
                $rowtotal = ($arabic_store) ? $this->getConvertedAmount($order->formatPriceTxt($item->getRowTotal())) : $order->formatPriceTxt($item->getRowTotal());
                $pdf->Cell(25, 10, ' '.$rowtotal, 0, 0, ($arabic_store) ? 'R' : 'L', 0);
                $pdf->Ln();
            }
            $pdf->Ln(5);

            $pdf->SetFont($fontname,'B',10);
            $pdf->Cell(160, 10, ($arabic_store) ? ':'.$subtotal_title : $subtotal_title.':', 0, 0, 'R',0);
            $subtotal = ($arabic_store) ? $this->getConvertedAmount($order->formatPriceTxt($invoice->getSubtotal())) : $order->formatPriceTxt($invoice->getSubtotal());
            $pdf->Cell(30, 10,$subtotal, 0, 0, 'R', 0);
            $pdf->Ln(5);
            $shipping_amount_title = ($arabic_store) ? ':'.'التوصيل' : ' '.'Shipping & Handling:';
            $free_shipping = ($arabic_store) ? 'توصيل مجاني' : ' '.'Free Shipping';
            $pdf->Cell(160, 10, $shipping_amount_title, 0, 0, 'R',0);
            $shippingcharge = ($arabic_store) ? $this->getConvertedAmount($order->formatPriceTxt($invoice->getShippingAmount())) : $order->formatPriceTxt($invoice->getShippingAmount());

            if($order->getIsShipmentFree() == 1){
                $shippingcharge = $free_shipping;
            }
            $pdf->Cell(30, 10,$shippingcharge, 0, 0, 'R', 0);
            $pdf->Ln(5);
            $grand_total_title = ($arabic_store) ? ':'.'المجموع الكلي' : ' '.'Grand Total:';
            $pdf->Cell(160, 10, $grand_total_title, 0, 0, 'R', 0);
            $grandtotal = ($arabic_store) ? $this->getConvertedAmount($order->formatPriceTxt($invoice->getGrandtotal())) : $order->formatPriceTxt($invoice->getGrandtotal());
            $pdf->Cell(30, 10,$grandtotal, 0, 0, 'R', 0);
            $pdf->Ln(12);
        }
        $pdf->Output('invoice'.date("Y-m-d_H-i-s").'.pdf', 'D');

        return $pdf;
    }

    public function getPaymentInfo($order){
        $paymentInfo = $this->_paymentData->getInfoBlock($order->getPayment())->setIsSecureMode(true)->toPdf();
        $paymentInfo = htmlspecialchars_decode($paymentInfo, ENT_QUOTES);
        $payment = explode('{{pdf_row_separator}}', $paymentInfo);
        foreach ($payment as $key => $value) {
            if (strip_tags(trim($value)) == '') {
                unset($payment[$key]);
            }
        }
        reset($payment);
        return implode(",", $payment);
    }

    public function getConvertedAmount($amount){
        $normal_numbers = array('.','0','1','2','3','4','5','6','7','8','9');
        $arabic_numbers = array(',','٠','١','٢','٣','٤','٥','٦','٧','٨','٩');

        $amount = str_replace($normal_numbers, $arabic_numbers, $amount);
        $amount = str_replace(['QAR'], [''], $amount);
        return 'ق. ر. '.$amount;
    }
}
