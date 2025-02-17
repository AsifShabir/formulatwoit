<?php

namespace App\Console\Commands;

use App\Business;
use App\Contact;
use App\Http\Controllers\SellPosController;
use App\Mail\GenerateLabels;
use App\Transaction;
use App\Utils\BusinessUtil;
use App\Utils\CashRegisterUtil;
use App\Utils\ContactUtil;
use DB;
use Illuminate\Console\Command;
use Modules\Woocommerce\Notifications\SyncOrdersNotification;
use App\Utils\FadexUtil;
use App\Utils\GlsUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use function PHPUnit\Framework\fileExists;

class GenerateShippingLabels extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'pos:GenerateShippingLabels';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and merge todays shipping labels in single pdf file.';

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
        parent::__construct();

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

    /**
     * Execute the console command.
     *
     * @return int
     * 03452828469
     * 03214151305
     */

    public function handle()
    {

        $yesterday = Carbon:: yesterday()->format('Y-m-d');
        //$yesterday = "2024-11-04";
        $todayDate = date("Y-m-d");
        $folderName = storage_path("app/public/shipping_labels");
        $outputfolderName = storage_path('app/public');
        $todayPdfs = [];
        $profofDelivery = [];
        
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
                    /*
                    // 100% GLS
                    if(in_array($country,['ES','PT','BG','HR','SK','SI','EE','HU'])){
                        $shipping_method = "gls";
                    }
                    // 100% Fedex
                    if(in_array($country,['AL','AD','BA','FI','GI','GL'])){
                        $shipping_method = "fedex";
                    }

                    if(in_array($country,['DE','AT','BE','GR']) && $weight < 5){
                        $shipping_method = 'gls';
                    }elseif(in_array($country,['DE','AT','BE','GR']) && $weight > 5){
                        $shipping_method = 'fedex';
                    }

                    if(in_array($country,['CY','VA','IE']) && $weight < 4){
                        $shipping_method = 'gls';
                    }elseif(in_array($country,['CY','VA','IE']) && $weight > 4){
                        $shipping_method = 'fedex';
                    }

                    if(in_array($country,['FR']) && $weight < 3){
                        $shipping_method = 'gls';
                    }elseif(in_array($country,['FR']) && $weight > 3){
                        $shipping_method = 'fedex';
                    }

                    if(in_array($country,['DK']) && $weight < 7){
                        $shipping_method = 'gls';
                    }elseif(in_array($country,['DK']) && $weight > 7){
                        $shipping_method = 'fedex';
                    }*/

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

                    //dd($shipping_method, $country, $weight);
                    
                    //echo $shipping_method."=>".$transaction->id.'=>'.$country."\n";
                    //continue;
                    if($transaction->shipping_labels_docs == ''){
                        if($shipping_method == 'fedex'){
                            $access_token = $this->fadexUtil->fadex_access_token($business_id);
                            $shipping_data = $this->fadexUtil->createShippingLabel($transaction,$access_token);

                            if(!empty($shipping_data)){
                                $profofDelivery[] = [
                                    'tracking' => $shipping_data['output']['transactionShipments'][0]['pieceResponses'][0]['trackingNumber'],
                                    'orderid'  => $transaction->invoice_no,
                                    'country'  => $country,
                                    'quantity' => 1
                                ];
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
                        }
                    }else{
                        if($shipping_method == 'fedex'){
                            $shipping_docs = json_decode($transaction->shipping_labels_docs);
                            $shipping_docs = is_array($shipping_docs) ? $shipping_docs[0] : $shipping_docs;
                            $profofDelivery[] = [
                                'tracking' => $shipping_docs->trackingNumber,
                                'orderid'  => $transaction->invoice_no,
                                'country'  => $country,
                                'quantity' => 1
                            ];
                            //$todayPdfs['label'] = $todayPdfs[$transaction->id]['label'] = storage_path("app/public/shipping_labels/documents_".$transaction->id."/shipping-label.pdf");
                            if(file_exists(storage_path("app/public/shipping_labels/documents_".$transaction->id."/shipping-label.pdf"))){
                                $todayPdfs[$transaction->id]['invoice'] =  storage_path("app/public/shipping_labels/documents_".$transaction->id."/invoice.pdf");
                            
                                $todayPdfs['label'] = $todayPdfs[$transaction->id]['label'] = storage_path("app/public/shipping_labels/documents_".$transaction->id."/shipping-label.pdf");
                            }
                        }
                    }
                }
            }else{
                $this->info('No orders found today.');
                return true;
            }
            

            /*$pdfFiles = glob($folderName . '/*.pdf');

            
            if ($pdfFiles) {
                foreach ($pdfFiles as $pdfFile) {
                    $filePath = $folderName.'\\'.basename($pdfFile);
                        // Get the file's modification time
                    $fileModificationTime = date('Y-m-d', filemtime($filePath));

                    // Check if the file was modified today
                    if ($fileModificationTime === $todayDate) {
                        // Add the file to the result array
                        $todayPdfs[] = $filePath;
                    }
                    
                }
            }*/
            //dd($todayPdfs);
            //print_r($todayPdfs);
            if(!empty($todayPdfs)){
                try{
                $pdf = new \Clegginabox\PDFMerger\PDFMerger;
                $pdf_invoice = new \Clegginabox\PDFMerger\PDFMerger;
                foreach($todayPdfs as $pdfFile){
                    if(isset($pdfFile['label']) && fileExists($pdfFile['label'])){
                        $pdf->addPDF($pdfFile['label'], 'all');
                    }
                    if(isset($pdfFile['invoice']) && fileExists($pdfFile['invoice'])){    
                        $pdf_invoice->addPDF($pdfFile['invoice'], 'all');
                    }
                }
                // Ensure the directory exists
                $todays_labels_folder = $outputfolderName.'/'.$yesterday.'_labels';
                if (!file_exists($todays_labels_folder)) {
                    mkdir($todays_labels_folder, 0755, true);
                }

                $all_labels_pdf = $todays_labels_folder.'/shipping_labels_today_fedex.pdf';
                $all_invoices_pdf = $todays_labels_folder.'/shipping_invoice_today_fedex.pdf';

                $pdf->merge('file',$all_labels_pdf);
                $pdf_invoice->merge('file',$all_invoices_pdf);

                $pod = $this->proofofDeliveryContent($profofDelivery,$todays_labels_folder);
                
                $attachments = [$all_labels_pdf,$all_invoices_pdf,$pod];

                $this->notify(1,$attachments);
                $this->info('email sent successfully');
                }catch (\Exception $e) {
                    $this->info(json_encode($todayPdfs));
                    $this->error('Error sending email: ' . $e->getMessage());
                }
            }else{
                $this->info('no pdf files generate today.');
            }

            //DB::commit();
        } catch (\Exception $e) {
            //dd($e);
            //DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            print_r('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $this->info('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());
        }

        $this->info('Cron run successfully');
    }

    public function proofofDeliveryContent($profofDelivery,$path){


        // Ensure the directory exists
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $filename = $path.'/proof-of-delivery.pdf';
        $shipping_method = 'Fedex';
        
        $body = view('sale_pos.receipts.proofofdelivery', compact('profofDelivery','shipping_method'))->render();

        $mpdf = new \Mpdf\Mpdf();

        
        $mpdf->SetTitle('proof-of-delivery.pdf');
        $mpdf->WriteHTML($body);
        $mpdf->OutputFile($filename);

        return $filename;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['business_id', InputArgument::REQUIRED, 'ID of the business'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    // protected function getOptions()
    // {
    //     return [
    //         ['example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null],
    //     ];
    // }

    /**
     * Sends notification to the user.
     *
     * @return void
     */
    private function notify($user_id,$attachment_path)
    {
        try{
            $to = "haider8278@gmail.com";
            //$to = "haider8278@gmail.com";
            $bcc = ["orders@tubo.plus","montajes@manufacturastorrero.com","jjm@tubo.plus", "gaguilarg@gemypa.com"];

            $subject = "Generate Fedex Labels";
            Mail::to($to)
            ->bcc($bcc)
            ->send(new GenerateLabels($attachment_path,$subject));
        } catch (\Exception $e) {
            echo $e->getMessage();
            $this->info($e->getMessage());
        }

        $this->info('email sent successfully');
    }
}
