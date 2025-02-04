<?php

namespace App\Http\Controllers;

use App\Business;
use App\Contact;
use App\Transaction;
use App\Utils\BusinessUtil;
use App\Utils\CashRegisterUtil;
use App\Utils\ContactUtil;
use App\Utils\FadexUtil;
use App\Utils\GlsUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GenerateLabelsController extends Controller
{

    /**
     * All Utils instance.
     */
    protected $contactUtil;

    protected $productUtil;

    protected $businessUtil;

    protected $transactionUtil;

    protected $cashRegisterUtil;

    protected $moduleUtil;

    protected $notificationUtil;

    protected $fadexUtil;
    protected $glsUtil;

    /**
     * Create a new command instance.
     *
     * @param  AmazonUtil  $amazonUtil
     * @return void
     */
    public function __construct(
        ContactUtil $contactUtil,
        ProductUtil $productUtil,
        BusinessUtil $businessUtil,
        TransactionUtil $transactionUtil,
        CashRegisterUtil $cashRegisterUtil,
        ModuleUtil $moduleUtil,
        NotificationUtil $notificationUtil,
        FadexUtil $fadexUtil,
        GlsUtil $glsUtil)
    {

        $this->contactUtil = $contactUtil;
        $this->productUtil = $productUtil;
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->cashRegisterUtil = $cashRegisterUtil;
        $this->moduleUtil = $moduleUtil;
        $this->notificationUtil = $notificationUtil;
        $this->fadexUtil = $fadexUtil;
        $this->glsUtil = $glsUtil;
    }

    public function index(Request $request){
        if($request->type == 'fedex'){
            $this->fedexLabels($request->date);
        }else{
            $this->gls($request->date);
        }

    }

    public function fedexLabels($date)
    {
        $yesterday = $date;
        $todayDate = date("Y-m-d");
        $folderName = storage_path("app/public/shipping_labels");
        $outputfolderName = storage_path('app/public');
        $todayPdfs = [];
        
        try {
            //DB::beginTransaction();
            $business_id = 1;

            $business = Business::findOrFail($business_id);
            $owner_id = $business->owner_id;

            //Set timezone to business timezone
            $timezone = $business->time_zone;
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);

            
            $transactions = Transaction::where('business_id', $business_id)
                            ->where('status','final')
                            ->whereDate('transaction_date',$yesterday)
                            ->get();
            
            //dd($transactions);
                            
            if($transactions->count() > 0){
                foreach($transactions as $trans){
                    if($trans->source == 'Amazon'){continue;}
                    $shipping_method = 'fedex';
                    $transaction = Transaction::where('business_id', $business_id)
                        ->where('id', $trans->id)
                        ->with('sell_lines', 'sell_lines.product', 'payment_lines','location')
                        ->first();
                    
                    $sellposController = new SellPosController($this->contactUtil, $this->productUtil,
                    $this->businessUtil,
                    $this->transactionUtil,
                    $this->cashRegisterUtil,
                    $this->moduleUtil,
                    $this->notificationUtil,
                    $this->fadexUtil,
                    $this->glsUtil);
                    $sellposController->downloadPdf($transaction->id,$business_id);
                    
                    $weight = 0;
                    foreach($transaction->sell_lines as $sell_line){
                        $weight += $sell_line->product->weight;
                    }

                    $contact = Contact::find($transaction->contact_id);
                    $shipping_address = $contact->address_line_1;
                    $consigneename = $contact->name;
                    $country = $contact->country;
                    if($country == "Spain" || $country == "España"){
                        $country = "ES";
                    }
                    if($country == "Portugal" || $country == "Portugal"){
                        $country = "PT";
                    }
                    //dd($country);
                    $city = $contact->city;
                    $state = $contact->state;
                    $zipcode = $contact->zip_code;
                    $phone = $contact->mobile;
                    $email = $contact->email;                    
                    if($contact->name == "Walk-In Customer"){
                        continue;
                    }
                    

                    // 100% GLS
                    $countryCodesForGLS = [
                        "ES",     // España
                        "ES-IC",  // España (Islas Canarias)
                        "ES-IB",  // España Baleares
                        "DE",     // Alemania
                        "AT",     // Austria
                        "BE",     // Bélgica
                        "BG",     // Bulgaria
                        "CY",     // Chipre
                        "VA",     // Ciudad del Vaticano
                        "FR-COR", // Córcega (Francia)
                        "HR",     // Croacia
                        "DK",     // Dinamarca
                        "SK",     // Eslovaquia o República Eslovaca
                        "SI",     // Eslovenia
                        "EE",     // Estonia
                        "FR",     // Francia
                        "GR",     // Grecia
                        "HU",     // Hungría
                        "IE",     // Irlanda
                        "IT",     // Italia
                        "LV",     // Letonia
                        "LI",     // Liechtenstein
                        "LT",     // Lituania
                        "LU",     // Luxemburgo
                        "MT",     // Malta
                        "MC",     // Mónaco
                        "NO",     // Noruega
                        "NL",     // Países Bajos
                        "PL",     // Polonia
                        "PT",     // Portugal
                        "GB",     // Reino Unido
                        "CZ",     // República Checa
                        "RO",     // Rumanía
                        "SM",     // San Marino
                        "RS",     // Serbia
                        "CH",     // Suiza
                        "TR"      // Turquía
                    ];
                    if(in_array($country,$countryCodesForGLS)){
                        $shipping_method = "gls";
                    }else{
                        $shipping_method = "fedex";
                    }
                    //echo $shipping_method.'=>'.$transaction->id.'=>'.$country.'<br>';
                    
                    if($transaction->shipping_labels_docs == '' && $shipping_method == 'fedex'){
                            $access_token = $this->fadexUtil->fadex_access_token($business_id);
                            $shipping_data = $this->fadexUtil->createShippingLabel($transaction,$access_token);

                            if(!empty($shipping_data)){
                                $todayPdfs[$transaction->id]['invoice'] =  storage_path("app/public/shipping_labels/documents_".$transaction->id."/invoice.pdf");
                                //$shipmentDocuments = $shipping_data['output']['transactionShipments'][0]['shipmentDocuments'];
                                $shipmentDocuments = $shipping_data['output']['transactionShipments'][0]['pieceResponses'][0]['packageDocuments'];
                                $shipmentData = $shipping_data;

                                $shipmentDocumentsData = $shipmentDocuments;
                                foreach($shipmentDocuments as $doc){
                                    $url = $doc['url'];
                                    $filename = 'shipping-label.pdf';
                                    $pdfFilePath = storage_path('app/public/shipping_labels/documents_'.$transaction->id.'/'.$filename);
                                    
                                    // Ensure the directory exists
                                    if (!file_exists(dirname($pdfFilePath))) {
                                        mkdir(dirname($pdfFilePath), 0755, true);
                                    }

                                    $pdfFileData = file_get_contents($url);
                                    // Save the PDF file to the specified path
                                    file_put_contents($pdfFilePath, $pdfFileData);

                                    $fileurl = Storage::url($folderName . 'documents_'.$transaction->id.'/' . $filename);
                                    $fileurl = asset('/storage/shipping_labels/documents_'.$transaction->id.'/'.$filename);
                                    $todayPdfs[$transaction->id]['label'] = $pdfFilePath;
                                    $shipmentDocumentsData = [
                                        'url' => $fileurl,
                                        'contentType' => $doc['contentType'],
                                        'copiesToPrint' => $doc['copiesToPrint'],
                                        'trackingNumber' => $shipping_data['output']['transactionShipments'][0]['pieceResponses'][0]['trackingNumber'],
                                    ];
                                    
                                }

                                $transaction->shipping_labels_docs = json_encode($shipmentDocumentsData);
                                $transaction->shipping_lebels_data = json_encode($shipmentDocuments);
                                $transaction->save();
                                $shipping_docs = json_decode($transaction->shipping_labels_docs);
                            }
                    }else{
                        if($shipping_method == 'fedex'){
                            //$todayPdfs['label'] = $todayPdfs[$transaction->id]['label'] = storage_path("app/public/shipping_labels/documents_".$transaction->id."/shipping-label.pdf");

                            if(file_exists(storage_path("app/public/shipping_labels/documents_".$transaction->id."/shipping-label.pdf"))){
                                $todayPdfs[$transaction->id]['invoice'] =  storage_path("app/public/shipping_labels/documents_".$transaction->id."/invoice.pdf");
                            
                                $todayPdfs['label'] = $todayPdfs[$transaction->id]['label'] = storage_path("app/public/shipping_labels/documents_".$transaction->id."/shipping-label.pdf");
                            }
                        }
                    }
                }
            }else{
                echo json_encode(['type'=>'error','msg'=>'No orders found today.']);
            }
            

            if(!empty($todayPdfs)){
                $todays_labels_folder = $outputfolderName.'/'.$yesterday.'_labels';
                if(file_exists($todays_labels_folder.'/shipping_labels_today_fedex.pdf')){
                   $url = asset('storage/'.$yesterday.'_labels/shipping_labels_today_fedex.pdf');

                    echo json_encode(['type'=>'success','url'=>$url,'todaypdf'=>$todayPdfs]); 
                    exit();
                }
                $pdf = new \Clegginabox\PDFMerger\PDFMerger;
                foreach($todayPdfs as $pdfFile){
                    if(isset($pdfFile['label']) && file_exists($pdfFile['label'])){
                        $pdf->addPDF($pdfFile['label'], 'all');
                    }
                    if(isset($pdfFile['invoice']) && file_exists($pdfFile['invoice'])){    
                        $pdf->addPDF($pdfFile['invoice'], 'all');
                    }
                }
                // Ensure the directory exists
                
                if (!file_exists($todays_labels_folder)) {
                    mkdir($todays_labels_folder, 0755, true);
                }
                $pdf->merge('file',$todays_labels_folder.'/shipping_labels_today_fedex.pdf');

                $url = asset('storage/'.$yesterday.'_labels/shipping_labels_today_fedex.pdf');

                echo json_encode(['type'=>'success','url'=>$url,'todaypdf'=>$todayPdfs]);

                //$this->notify(1,$todays_labels_folder.'/shipping_labels_today-m.pdf');
                //echo json_encode(['type'=>'success','url'=>asset($todays_labels_folder.'/shipping_labels_today.pdf')]);
            }else{
                echo json_encode(['type'=>'error','msg'=>'no pdf files generate today.']);
            }

        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            echo response()->json(['type'=>'error','msg'=>$e],200);
        }

    }

    public function gls($date)
    {

        $yesterday = $date;
        //$yesterday = "2024-11-04";
        $todayDate = date("Y-m-d");
        $folderName = storage_path("app/public/shipping_labels");
        $outputfolderName = storage_path('app/public');
        $todayPdfs = [];
        
        try {
            //DB::beginTransaction();
            $business_id = 1;

            $business = Business::findOrFail($business_id);
            $owner_id = $business->owner_id;

            //Set timezone to business timezone
            $timezone = $business->time_zone;
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);

            
            $transactions = Transaction::where('business_id', $business_id)
                            ->where('status','final')
                            ->whereDate('transaction_date',$yesterday)
                            ->get();
            
            //dd($transactions);
                            
            if($transactions->count() > 0){
                foreach($transactions as $trans){
                    if($trans->source == 'Amazon'){continue;}
                    $shipping_method = 'fedex';
                    $transaction = Transaction::where('business_id', $business_id)
                        ->where('id', $trans->id)
                        ->with('sell_lines', 'sell_lines.product', 'payment_lines','location')
                        ->first();
                    
                    $sellposController = new SellPosController($this->contactUtil, $this->productUtil,
                    $this->businessUtil,
                    $this->transactionUtil,
                    $this->cashRegisterUtil,
                    $this->moduleUtil,
                    $this->notificationUtil,
                    $this->fadexUtil,
                    $this->glsUtil);
                    $sellposController->downloadPdf($transaction->id,$business_id);
                    
                    $weight = 0;
                    foreach($transaction->sell_lines as $sell_line){
                        $weight += $sell_line->product->weight;
                    }

                    $contact = Contact::find($transaction->contact_id);
                    $shipping_address = $contact->address_line_1;
                    $consigneename = $contact->name;
                    $country = $contact->country;
                    if($country == "Spain" || $country == "España"){
                        $country = "ES";
                    }
                    $city = $contact->city;
                    $state = $contact->state;
                    $zipcode = $contact->zip_code;
                    $phone = $contact->mobile;
                    $email = $contact->email;                    

                    // 100% GLS
                    if(in_array($country,['ES','DE','AT','BE','BG','CY','VA','FA','HA','DK','SK','SI','EE','GR','HU','IE','IT','LV','LI','LT','LU','MT','MC','NO','NL','PL','PT','GB','CZ','RO','RS','CH','TR'])){
                        $shipping_method = "gls";
                    }else{
                        $shipping_method = "fedex";
                    }
                    
                    if($transaction->shipping_labels_docs == '' && $shipping_method == 'gls'){
                        //echo $shipping_method.'=>'.$country.'=>'.$transaction->id.'<br>';
                        $glsLabel = $this->glsUtil->createShippingLabel($transaction);
                        //dd($glsLabel);
                        if(isset($glsLabel['errors'])){
                            //print_r($glsLabel['errors']);
                        }
                        if(isset($glsLabel['url'])){
                            $todayPdfs[$transaction->id]['invoice'] =  storage_path("app/public/shipping_labels/documents_".$transaction->id."/invoice.pdf");
                            $todayPdfs[$transaction->id]['label'] = storage_path("app/public/shipping_labels/documents_".$transaction->id."/shipping-label.pdf");
                            $shipping_docs = $glsLabel;
                            $shipmentData = $shipping_docs;
                            $transaction->shipping_labels_docs = json_encode($shipmentData);
                            $transaction->shipping_lebels_data = json_encode($glsLabel);
                            $transaction->save();
                            $shipping_docs = json_decode($transaction->shipping_labels_docs);
                        }
                    }else{
                        if($shipping_method == 'gls'){
                            if(file_exists(storage_path("app/public/shipping_labels/documents_".$transaction->id."/shipping-label.pdf"))){
                                $todayPdfs[$transaction->id]['invoice'] =  storage_path("app/public/shipping_labels/documents_".$transaction->id."/invoice.pdf");
                            
                                $todayPdfs['label'] = $todayPdfs[$transaction->id]['label'] = storage_path("app/public/shipping_labels/documents_".$transaction->id."/shipping-label.pdf");
                            }
                        }
                    }
                }
            }else{
                echo json_encode(['type'=>'error','msg'=>'No GLS orders found today.']);
            }
            

            //dd($todayPdfs);
            //print_r($todayPdfs);
            if(!empty($todayPdfs)){
                $todays_labels_folder = $outputfolderName.'/'.$yesterday.'_labels';
                if(file_exists($todays_labels_folder.'/shipping_labels_today_gls.pdf')){
                    $url = asset('storage/'.$yesterday.'_labels/shipping_labels_today_gls.pdf');

                    echo json_encode(['type'=>'success','url'=>$url,'todaypdf'=>$todayPdfs]);
                    exit();
                }
                try{
                $pdf = new \Clegginabox\PDFMerger\PDFMerger;
                foreach($todayPdfs as $pdfFile){
                    if(isset($pdfFile['label']) && file_exists($pdfFile['label'])){
                        $pdf->addPDF($pdfFile['label'], 'all');
                    }
                    if(isset($pdfFile['invoice']) && file_exists($pdfFile['invoice'])){    
                        $pdf->addPDF($pdfFile['invoice'], 'all');
                    }
                }
                // Ensure the directory exists
                
                if (!file_exists($todays_labels_folder)) {
                    mkdir($todays_labels_folder, 0755, true);
                }
                $pdf->merge('file',$todays_labels_folder.'/shipping_labels_today_gls.pdf');

                $url = asset('storage/'.$yesterday.'_labels/shipping_labels_today_gls.pdf');

                echo json_encode(['type'=>'success','url'=>$url,'todaypdf'=>$todayPdfs]);
                }catch (\Exception $e) {
                    //dd($e);
                    //DB::rollBack();
                    \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
                    echo response()->json(['type'=>'error','msg'=>$e]);
                }
            }else{
                echo json_encode(['type'=>'error','msg'=>'no pdf files generate today.']);
            }

            //DB::commit();
        } catch (\Exception $e) {
            //dd($e);
            //DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            echo json_encode(['type'=>'error','msg'=>$e]);
        }

    }
}
