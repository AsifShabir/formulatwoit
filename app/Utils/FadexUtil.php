<?php

namespace App\Utils;

use App\AmazonProduct;
use App\Business;
use App\Category;
use App\Contact;
use App\Exceptions\PurchaseSellMismatch;
use App\Product;
use App\TaxRate;
use App\Transaction;
use App\Utils\ContactUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use App\VariationLocationDetails;
use App\VariationTemplate;
use Automattic\WooCommerce\Client;
use DB;
use App\AmazonSyncLog;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Modules\Woocommerce\Exceptions\WooCommerceError;
use SellingPartnerApi\SellingPartnerApi;
use SellingPartnerApi\Enums\Endpoint;
use SellingPartnerApi\Seller\ListingsItemsV20210801\Dto;
use stdClass;

class FadexUtil extends Util
{
    /**
     * All Utils instance.
     */
    protected $transactionUtil;

    protected $productUtil;

    protected $account_number;
    protected $client_id;
    protected $client_secret;
    protected $api_url;
    protected $access_token;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(TransactionUtil $transactionUtil, ProductUtil $productUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
        // Sandbox
        // $this->account_number = '740562274';
        // $this->client_id = 'l7aab447f5eb254d7184c79229baf47a24';
        // $this->client_secret = '42d156673664403785cb5d79a15cf909';
        // $this->api_url = 'https://apis-sandbox.fedex.com';
        // $this->access_token = null;
        //Production
        $this->account_number = '733317531';
        $this->client_id = 'l794f6597edbdf410c97a07a419cd1d0f8';
        $this->client_secret = '7cbaa1d4-5de9-4c9b-985d-5a4bf94ec744';
        $this->api_url = 'https://apis.fedex.com';
        $this->access_token = null;
    }

    public function get_api_settings($business_id)
    {
        //Production
        $api_settings = [
            'client_id' => 'l794f6597edbdf410c97a07a419cd1d0f8',
            'client_secret' => '7cbaa1d4-5de9-4c9b-985d-5a4bf94ec744',
            'account' => '733317531',
            'url'   => 'https://apis.fedex.com'
        ];
        //Sandbox
        // $api_settings = [
        //     'client_id' => 'l7aab447f5eb254d7184c79229baf47a24',
        //     'client_secret' => '42d156673664403785cb5d79a15cf909',
        //     'account' => '740562274',
        //     'url'   => 'https://apis-sandbox.fedex.com'
        // ];

        return $api_settings;
    }

    /**
     * Creates Automattic\WooCommerce\Client object
     *
     * @param  int  $business_id
     * @return obj
     */
    public function fadex_access_token($business_id)
    {
        $fedex_access_token = false;
        
        // if(request()->session()->has('fedex_access_token')){
        //     $fedex_access_token = request()->session()->get('fedex_access_token');
        //     $fedex_access_time = request()->session()->get('fedex_access_token');
        //     $time1 = $fedex_access_time;
        //     $time2 = Carbon::parse(date("Y-m-d H:i:s"));

        //     $hours = $time1->floatDiffInHours($time2);

        //     if($hours < 1){
        //         $fedex_access_token = true;
        //     }
        // }
        //dd($fadex_cookie);
        if($fedex_access_token){
            return $fedex_access_token;
        }else{
            $endpoint = $this->api_url.'/oauth/token';
            $data = ['grant_type'=> 'client_credentials','client_id'=> $this->client_id, 'client_secret' => $this->client_secret];
            
            $response = Http::asForm()->post($endpoint, $data);
            if($response->successful()){
                $access_token = $response['access_token'];
                session()->put('fedex_access_token',$access_token);
                session()->put('fedex_access_time',now());
                $this->access_token = $access_token;
                return $access_token;
            }else{
                $response->throw();
            }
        }
        //dd(request()->session()->all());

        
    }

    /**
     * Generate an excerpt from a given string with a specific word limit.
     *
     * @param string $text The text to excerpt.
     * @param int $limit The word limit.
     * @param string $end The ending characters to append after the excerpt.
     * @return string
     */
    public function excerpt($text, $limit = 20, $end = '...')
    {
        $words = explode(' ', $text);
        if (count($words) <= $limit) {
            return $text;
        }

        return implode(' ', array_slice($words, 0, $limit)) . $end;
    }

    public function createShippingLabel($transaction,$access_token){
        
        $addresses = json_decode($transaction->order_addresses);
        if(isset($addresses->shipping_address)){
            $shipping_address = $addresses->shipping_address;
            $payload = $this->shippingPayload($shipping_address,$transaction);
            //dd($payload);
            $endpoint = $this->api_url.'/ship/v1/shipments';
            //echo $payload;
            $payload = json_decode($payload,true);
            //dd($payload);
            $response = Http::withToken($access_token)->post($endpoint,$payload);
            //dd($payload);
            if($response->successful()){
                $data = $response->json();
                //dd($payload,$data);
                //dd($data);
                return $data;
                $shipmentDocuments = $data['output']['transactionShipments']['shipmentDocuments'];
                return $shipmentDocuments;

            }else{
                $to = "haider8278@gmail.com";
                $subject = "Error in Fedex shipping label creation";
                $message = "Error in Fedex shipping label creation";
                $content = $message.'<br>'.json_encode([$response->json(),$payload]);
                Mail::send('emails.exception', ['content' => $content], function ($message) use
                ($to, $subject) {
                    $message->to($to)->subject($subject);
                });
                return [];
                //dd($response->json(),$payload);
                //return $response->throw();
            }
        }
        return [];
    }

    public function shippingPayload($shipping_address,$transaction){

        $contact = Contact::find($transaction->contact_id);
        //dd($shipping_address, $contact);
        $name = $shipping_address->shipping_name;
        //$name = "Ford Rosenbaum";
        $line_address = ($shipping_address->shipping_address_line_1 == "") ? $contact->address_line_1 : $shipping_address->shipping_address_line_1;
        //$line_address = "5112 N Tongass Hwy";
        if (strlen($line_address) > 30) {
            if(strlen($line_address > 60)){
                $line_address = substr($line_address, 0, 68);
            }
            $middle = intval(strlen($line_address) / 2); // Find approximate middle point
            $part1 = substr($line_address, 0, $middle);
            $part2 = substr($line_address, $middle);
            
            $line_address = '["'.$part1.'","'.$part2.'"]';
        }else{
            $line_address = '["'.$line_address.'"]';
        }
        $country = $shipping_address->shipping_country;
        //$country = "US";
        if($country == "Spain" || $country == "España"){
            $country = "ES";
        }
        $city = $shipping_address->shipping_city;
        //$city = "Ketchikan";
        $state = $shipping_address->shipping_state;
        $state = "";
        $zipcode = $shipping_address->shipping_zip_code;
        //$zipcode = "99901";
        $phone = $contact->mobile ?? "1234567890";
        //$phone = "9072477575";
        

        $rxzone = ['LU','DE','FR','BE','MC','NL'];
        $szone = ['AD','AT','DK','FO','FI','GR','GL','IT','PT','SM','ES','SE','VA'];
        $tzone = ['BG','CY','CZ','EE','HU','LV','LT','MT','PL','RO','SK','SI'];
        $uzone = ['GI','IS','LI','NO','CH'];
        $vzone = ['AL','BA','HR','MD','ME','MK','RS','TR'];
        $wzone = ['IE'];
        $yzone = [];
        $fexdex_regrional = ['AT','BE','CZ','DK','EE','FI','FR','DE','HU','IT','IE','LV','LT','LU','NL','NO','PL','SI','ES','SE','CH','GB','GR','RO'];
        if(in_array($country,$fexdex_regrional)){
            $serviceType = 'FEDEX_REGIONAL_ECONOMY';
            $specialServiceType = '"shipmentSpecialServices": {"specialServiceTypes": ["ELECTRONIC_TRADE_DOCUMENTS"]},';
        }else{
            $serviceType = 'INTERNATIONAL_ECONOMY';
            $specialServiceType = '"shipmentSpecialServices": {"specialServiceTypes": ["ELECTRONIC_TRADE_DOCUMENTS"], "etdDetail": {"requestedDocumentTypes": ["COMMERCIAL_INVOICE"]}},';
        }

        if(in_array($country,['US','UK','GB','CH'])){
            $specialServiceType = ' "shippingDocumentSpecification": {
                "shippingDocumentTypes": [
                    "COMMERCIAL_INVOICE"
                ],
                "commercialInvoiceDetail": {
                    "customerImageUsages": [
                        {
                        "id": "IMAGE_1",
                        "type": "SIGNATURE",
                        "providedImageType": "SIGNATURE"
                        },
                        {
                        "id": "IMAGE_2",
                        "type": "LETTER_HEAD",
                        "providedImageType": "LETTER_HEAD"
                        }
                    ],
                    "documentFormat": {
                        "stockType": "PAPER_LETTER",
                        "dispositions": [
                            {
                                "dispositionType": "RETURNED"
                            }
                        ],
                        "docType": "PDF"
                    }
                }
            },
            "shipmentSpecialServices": {"specialServiceTypes": ["ELECTRONIC_TRADE_DOCUMENTS"],"etdDetail": {"requestedDocumentTypes": ["COMMERCIAL_INVOICE"]}},';
        }
        if($country == "ES"){
            $serviceType = 'FEDEX_PRIORITY';
            $specialServiceType = '';
        }
        /*if(in_array($country,$rxzone)){
            $serviceType = 'INTERNATIONAL_ECONOMY';
            $specialServiceType = ' "shippingDocumentSpecification": {
            "shippingDocumentTypes": [
                "COMMERCIAL_INVOICE"
            ],
            "commercialInvoiceDetail": {
                "documentFormat": {
                    "stockType": "PAPER_LETTER",
                    "dispositions": [
                        {
                            "dispositionType": "RETURNED"
                        }
                    ],
                    "docType": "PDF"
                }
            }
        },';
        }else{
            $serviceType = 'FEDEX_REGIONAL_ECONOMY';
            $specialServiceType = '"shipmentSpecialServices": {"specialServiceTypes": ["ELECTRONIC_TRADE_DOCUMENTS"]},';
        }
        */
        //$weight = $transaction->sell_lines[0]->product->weight ?? 20;
        //$weight = $weight * 2.205;

        $total_weight = 0;
        $quantity = 0;
        $commodities = '';
        if($transaction->sell_lines->count() > 0){
            foreach($transaction->sell_lines as $sell_line){
                $weight = $sell_line->product->weight;
                
                $weight = $weight * 2.205;
                
                if($weight <= 0){
                    $weight = 1;
                }

                $total_weight += $weight;
                
                $quantity = $sell_line->quantity;
                $description = strip_tags($sell_line->product->product_description);
                $description = $this->excerpt($description,10);
                $description = trim(str_replace(":", "",$description));
                $commodities .= '{"name":"'.$sell_line->product->name.'","description": "'.$description.'","countryOfManufacture": "ES","quantity": "'.$quantity.'","quantityUnits": "PCS","unitPrice": {"amount": "'.$sell_line->unit_price.'","currency": "EUR"},"customsValue": {"amount": "'.$sell_line->unit_price.'","currency": "EUR"},"weight": {"units": "LB","value": "'.$weight.'"}},';
            };
        }else{
            $weight = 1;
            $quantity = 1;
            $total_weight = 1;
            $amount = $transaction->total_before_tax;
            $commodities .= '{"name":"product","description": "product descriptions","countryOfManufacture": "ES","quantity": "'.$quantity.'","quantityUnits": "PCS","unitPrice": {"amount": "'.$amount.'","currency": "EUR"},"customsValue": {"amount": "'.$amount.'","currency": "EUR"},"weight": {"units": "LB","value": "'.$weight.'"}},';
        }
        
        if($total_weight < $weight){
            $total_weight = $weight;
        }
        $commodities = trim($commodities,",");

        $payload = [];
        $payload["labelResponseOptions"] = "URL_ONLY";
        $payload["requestedShipment"]["shipper"]["contact"] = [
            "personName" => "JUAN JOSE",
            "phoneNumber" => 659182436,
            "companyName"=> "TuboPlus"
        ];
        $payload["requestedShipment"]["shipper"]["address"] = [
            "streetLines" => ["P.I EL PLANO","CALLE A; NAVE 12"],
            "city"=> "ZARAGOZA",
            "stateOrProvinceCode"=> "",
            "postalCode"=> 50430,
            "countryCode"=> "ES"
        ];
        $payload["requestedShipment"]["recipients"]["contact"] = [
            "personName" => $name,
            "phoneNumber" => $phone,
        ];
        $payload["requestedShipment"]["recipients"]["address"] = [
            "streetLines" => $line_address,
            "city"=> $city,
            "stateOrProvinceCode"=> "",
            "postalCode"=> $zipcode,
            "countryCode"=> $country
        ];
        $payload["requestedShipment"]["shipDatestamp"] = date("Y-m-d");
        $payload["requestedShipment"]["serviceType"] = $serviceType;
        $payload["requestedShipment"]["packagingType"] = "YOUR_PACKAGING";
        $payload["requestedShipment"]["pickupType"] = "USE_SCHEDULED_PICKUP";
        $payload["requestedShipment"]["blockInsightVisibility"] = "false";
        $payload["requestedShipment"]["shippingChargesPayment"] = ["paymentType"=> "SENDER"];
        $payload["requestedShipment"]["labelSpecification"] = ["imageType"=> "PDF","labelStockType"=> "PAPER_85X11_TOP_HALF_LABEL"];

        $payload["requestedShipment"]["customsClearanceDetail"] = [
            "dutiesPayment"=> ["paymentType"=> "SENDER"],
            "commodities" => [
                $commodities
            ]
        ];

        $json = '{
        "labelResponseOptions": "URL_ONLY","requestedShipment": {"shipper": {"contact": {"personName": "JUAN JOSE","phoneNumber": 659182436,"companyName": "TuboPlus"},"address": {"streetLines": ["P.I EL PLANO","CALLE A; NAVE 12"],"city": "ZARAGOZA","stateOrProvinceCode": "", "postalCode": 50430,"countryCode": "ES"}},"recipients": [{"contact": {"personName": "'.$name.'","phoneNumber": 1234567890},"address": {"streetLines": '.$line_address.',"city": "'.$city.'","stateOrProvinceCode": "'.$state.'","postalCode": "'.$zipcode.'","countryCode": "'.$country.'"}}],"shipDatestamp": "'.date("Y-m-d").'","serviceType": "'.$serviceType.'","packagingType": "YOUR_PACKAGING","pickupType": "USE_SCHEDULED_PICKUP","blockInsightVisibility": false,"shippingChargesPayment": {"paymentType": "SENDER"},"labelSpecification": {"imageType": "PDF","labelStockType": "STOCK_4X6"},"customsClearanceDetail": {"dutiesPayment": {"paymentType": "RECIPIENT"},"commodities": ['.$commodities.']},'.$specialServiceType.'"requestedPackageLineItems": [{"weight": {"units": "LB","value": "'.$total_weight.'"}}]},"accountNumber": {"value": "'.$this->account_number.'"}}';

        //dd($transaction,$weight,$quantity,$json);
        return $json;
    }

    /**
     * Synchronizes pos categories with Woocommerce categories
     *
     * @param  int  $business_id
     * @return void
     */
    public function syncCategories($business_id, $user_id)
    {
        $last_synced = $this->getLastSync($business_id, 'categories', false);

        //Update parent categories
        $query = Category::where('business_id', $business_id)
                        ->where('category_type', 'product')
                        ->where('parent_id', 0);

        //Limit query to last sync
        if (! empty($last_synced)) {
            $query->where('updated_at', '>', $last_synced);
        }

        $categories = $query->get();

        $category_data = [];
        $new_categories = [];
        $created_data = [];
        $updated_data = [];
        foreach ($categories as $category) {
            if (empty($category->amazon_cat_id)) {
                $category_data['create'][] = [
                    'name' => $category->name,
                ];
                $new_categories[] = $category;
                $created_data[] = $category->name;
            } else {
                $category_data['update'][] = [
                    'id' => $category->amazon_cat_id,
                    'name' => $category->name,
                ];
                $updated_data[] = $category->name;
            }
        }

        if (! empty($category_data['create'])) {
            $this->syncCat($business_id, $category_data['create'], 'create', $new_categories);
        }
        if (! empty($category_data['update'])) {
            $this->syncCat($business_id, $category_data['update'], 'update', $new_categories);
        }

        //Sync child categories
        $query2 = Category::where('business_id', $business_id)
                        ->where('category_type', 'product')
                        ->where('parent_id', '!=', 0);
        //Limit query to last sync
        if (! empty($last_synced)) {
            $query2->where('updated_at', '>', $last_synced);
        }

        $child_categories = $query2->get();

        $cat_id_amazon_id = Category::where('business_id', $business_id)
                                    ->where('parent_id', 0)
                                    ->where('category_type', 'product')
                                    ->pluck('amazon_cat_id', 'id')
                                    ->toArray();

        $category_data = [];
        $new_categories = [];
        foreach ($child_categories as $category) {
            if (empty($cat_id_amazon_id[$category->parent_id])) {
                continue;
            }

            if (empty($category->amazon_cat_id)) {
                $category_data['create'][] = [
                    'name' => $category->name,
                    'parent' => $cat_id_amazon_id[$category->parent_id],
                ];
                $new_categories[] = $category;
                $created_data[] = $category->name;
            } else {
                $category_data['update'][] = [
                    'id' => $category->amazon_cat_id,
                    'name' => $category->name,
                    'parent' => $cat_id_amazon_id[$category->parent_id],
                ];
                $updated_data[] = $category->name;
            }
        }

        if (! empty($category_data['create'])) {
            $this->syncCat($business_id, $category_data['create'], 'create', $new_categories);
        }
        if (! empty($category_data['update'])) {
            $this->syncCat($business_id, $category_data['update'], 'update', $new_categories);
        }

        //Create log
        if (! empty($created_data)) {
            $this->createSyncLog($business_id, $user_id, 'categories', 'created', $created_data);
        }
        if (! empty($updated_data)) {
            $this->createSyncLog($business_id, $user_id, 'categories', 'updated', $updated_data);
        }
        if (empty($created_data) && empty($updated_data)) {
            $this->createSyncLog($business_id, $user_id, 'categories');
        }
    }

    public function getQuantity($product_id,$business_id){
        //Get Opening Stock Transactions for the product if exists
            $quantity = 0;
            $transaction = Transaction::where('business_id', $business_id)
                                ->where('opening_stock_product_id', $product_id)
                                ->where('type', 'opening_stock')
                                ->with(['purchase_lines'])
                                ->latest()->first();
            if($transaction){
                $latest_purchase_line = $transaction->purchase_lines()->latest()->first();

                $quantity = $this->productUtil->num_uf(trim($latest_purchase_line->quantity));
            }
            return $quantity;
    }
    
    /**
     * Synchronizes pos products Stocks with Amazon products Stocks
     *
     * @param  int  $business_id
     * @return []
     */
    public function syncStocks($business_id, $user_id, $sync_type, $limit = 100, $offset = 0, $nextToken = null)
    {
        $amazon = $this->amazon_client($business_id);
        $amazon_api_settings = $this->get_api_settings($business_id);
        
        if(strpos($amazon_api_settings->markete_places,',') !== false){
            $marketePlaceIds = explode(',', $amazon_api_settings->markete_places);
        }else{
            $marketePlaceIds = [$amazon_api_settings->markete_places];
        }

        $products = Product::limit($limit)->offset($offset)->get();
        $data = [];
        $listing = [];
        foreach($products as $product){
            if($product->amazon_product_id != ''){
                $sku = $product->amazon_product_id;
                $quantity = $this->getQuantity($product->id,$business_id);
                try{
                    $listingApi = $amazon->listingsItems();
                    $getListingItem = $listingApi->getListingsItem(
                        sellerId: 'AWUA403PCK88S',
                        sku: $sku,
                        marketplaceIds: $marketePlaceIds,
                    );
                    $listing = $getListingItem->json();
                    $patches = [
                        'patches' => new Dto\PatchOperation(
                            op: "replace",
                            path: "/attributes/fulfillment_availability",
                            value: [
                                "fulfillment_channel_code"=> "DEFAULT",
                                "quantity"=> $quantity
                            ]
                        )
                    ];
                    $productType = $listing['summaries'][0]['productType'];
                    
                    $listingsItemPatchRequest = new Dto\ListingsItemPatchRequest(
                        productType: $productType,
                        patches: $patches,
                    );
                    $response = $listingApi->patchListingsItem(
                        sellerId: 'AWUA403PCK88S',
                        sku: $sku,
                        listingsItemPatchRequest: $listingsItemPatchRequest,
                        marketplaceIds: $marketePlaceIds,
                    );
                    $data = $response->json();
                }catch(\Exception $e){
                    return [];
                }
            }
            //Create log
            $data = array_merge($data,$listing);
            $this->createSyncLog($business_id, $user_id, 'amazon_stock_updated', 'updated', $data);
        }

        $nextToken = ($products->count() > 0) ? $offset+1 : 'completed';
        request()->session()->put('nextToken', $nextToken);
        return $products;
    }

    
    /**
     * Synchronizes pos products with Woocommerce products
     *
     * @param  int  $business_id
     * @return []
     */
    public function syncProducts($business_id, $user_id, $sync_type, $limit = 100, $page = 0, $nextToken = null)
    {
        $amazon = $this->amazon_client($business_id);
        $amazon_api_settings = $this->get_api_settings($business_id);
        
        if(strpos($amazon_api_settings->markete_places,',') !== false){
            $marketePlaceIds = explode(',', $amazon_api_settings->markete_places);
        }else{
            $marketePlaceIds = [$amazon_api_settings->markete_places];
        }

        $data = [];
        try{
            $inventoryApi = $amazon->fbaInventory();
            $response = $inventoryApi->getInventorySummaries(
                granularityType: 'Marketplace',
                granularityId: $amazon_api_settings->markete_places,
                marketplaceIds: $marketePlaceIds,
                nextToken : $nextToken
                //startDateTime: new DateTime(Carbon::now()->subMonths(17)->format("Y-m-d"))
            );
            $data = $response->json();
        }catch(\Exception $e){
            //dd($e);
            return [];
        }
        $nextToken = isset($data['pagination']['nextToken']) ? $data['pagination']['nextToken'] : '';
        request()->session()->put('nextToken', $nextToken);
        
        $products = $data['payload']['inventorySummaries'];
        
        $last_synced = $this->getLastSync($business_id, 'all_amazon_products', false);
        
        $created_data = [];
        $updated_data = [];

        $business_location_id = $amazon_api_settings->location_id;
        
        $all_products = $products;
        $product_data = [];
        $new_products = [];
        $updated_products = [];

        foreach ($all_products as $product) {
            //Skip product if last updated is less than last sync
            $last_updated = $product['lastUpdatedTime'];
            
            if (!empty($last_synced) && strtotime($last_updated) < strtotime($last_synced)) {
                continue;
            }
            //Set common data
            $array = [
                'productName' => $product['productName'],
                'fnSku' => $product['fnSku'],
                'sellerSku' => $product['sellerSku'],
                'asin'  => $product['asin'],
                'condition'=> $product['condition'],
                'totalQuantity'=> $product['totalQuantity']
            ];
            
            $amazon_product = AmazonProduct::where('fnSku',$product['fnSku'])->first();
            if (empty($amazon_product)) {
                $is_parent = AmazonProduct::where('asin',$product['asin'])->first();
                if (!empty($is_parent)) {
                    $array['parent_id'] = $is_parent->id;
                }
                $product_data['create'][] = $array;
                $new_products[] = $product;
                $created_data[] = $product['fnSku'];
            } else {
                $product_data['update'][] = $array;
                $updated_data[] = $product['fnSku'];
                $updated_products[] = $product;
            }
        }
        $create_response = [];
        $update_response = [];
        //dd($product_data);
        if (! empty($product_data['create'])) {
            $create_response = $this->syncProd($business_id, $product_data['create'], 'create', $new_products);
        }
        if (! empty($product_data['update'])) {
            $update_response = $this->syncProd($business_id, $product_data['update'], 'update', $updated_products);
        }
        $new_amazon_product_ids = array_merge($create_response, $update_response);
        //dd($new_amazon_product_ids);
        //Create log
        if (! empty($created_data)) {
            if ($sync_type == 'new') {
                $this->createSyncLog($business_id, $user_id, 'new_amazon_products', 'created', $created_data);
            } else {
                $this->createSyncLog($business_id, $user_id, 'all_amazon_products', 'created', $created_data);
            }
        }
        if (!empty($updated_data)) {
            $this->createSyncLog($business_id, $user_id, 'all_amazon_products', 'updated', $updated_data);
        }


        if (empty($created_data) && empty($updated_data)) {
            if ($sync_type == 'new') {
                $this->createSyncLog($business_id, $user_id, 'new_amazon_products');
            } else {
                $this->createSyncLog($business_id, $user_id, 'all_amazon_products');
            }
        }

        return $all_products;
    }

    public function syncProd($business_id, $data, $type, $new_products)
    {
        $new_amazon_product_ids = [];
        $count = 0;
        foreach (array_chunk($data, 99) as $chunked_array) {
            $sync_data = [];
            $sync_data[$type] = $chunked_array;
            
                foreach ($chunked_array as $product) {
                    if($type == 'create'){
                        $new_product = new AmazonProduct();
                    }else{
                        $new_product = AmazonProduct::where('fnSku',$product['fnSku'])->first();
                    }
                    $new_product->asin = $product['asin'];
                    $new_product->fnSku = $product['fnSku'];
                    $new_product->sellerSku = $product['sellerSku'];
                    $new_product->condition = $product['condition'];
                    $new_product->productName = $product['productName'];
                    if(isset($product['parent_id'])){
                        $new_product->parent_id = $product['parent_id'];
                    }
                    $new_product->totalQuantity = $product['totalQuantity'];
                    $new_product->save();
                    
                    $new_amazon_product_ids[] = $new_product->id;
                    $count++;
                }
        }

        return $new_amazon_product_ids;
    }

    /**
     * Synchronizes pos variation templates with Woocommerce product attributes
     *
     * @param  int  $business_id
     * @return void
     */
    public function syncVariationAttributes($business_id)
    {
        $amazon = $this->amazon_client($business_id);
        $query = VariationTemplate::where('business_id', $business_id);

        $attributes = $query->get();
        $data = [];
        $new_attrs = [];
        foreach ($attributes as $attr) {
            if (empty($attr->amazon_attr_id)) {
                $data['create'][] = ['name' => $attr->name];
                $new_attrs[] = $attr;
            } else {
                $data['update'][] = [
                    'name' => $attr->name,
                    'id' => $attr->amazon_attr_id,
                ];
            }
        }

        if (! empty($data)) {
            $response = new stdClass();

            //update amazon_attr_id
            if (! empty($response->create)) {
                foreach ($response->create as $key => $value) {
                    $new_attr = $new_attrs[$key];
                    if ($value->id != 0) {
                        $new_attr->amazon_attr_id = $value->id;
                    } else {
                        // get product attributes from amazon
                        $all_attrs = new stdClass();

                        foreach ($all_attrs as $attr) {
                            if (strtolower($attr->name) == strtolower($new_attr->name)) {
                                $new_attr->amazon_attr_id = $attr->id;
                            }
                        }
                    }
                    $new_attr->save();
                }
            }
        }
    }

    /**
     * Synchronizes pos products variations with Woocommerce product variations
     *
     * @param  int  $business_id
     * @param  string  $sync_type
     * @param  array  $new_amazon_product_ids (woocommerce product id of newly created products to sync)
     * @return void
     */
    public function syncProductVariations($business_id, $sync_type = 'all', $new_amazon_product_ids = [])
    {
        //woocommerce api client object
        $amazon = $this->amazon_client($business_id);
        $amazon_api_settings = $this->get_api_settings($business_id);

        $query = Product::where('business_id', $business_id)
                        ->where('type', 'variable')
                        ->where('amazon_disable_sync', 0)
                        ->with(['variations',
                            'variations.variation_location_details',
                            'variations.product_variation',
                            'variations.product_variation.variation_template', ]);

        $query->whereIn('amazon_product_id', $new_amazon_product_ids);

        $variable_products = $query->get();
        $business_location_id = $amazon_api_settings->location_id;
        foreach ($variable_products as $product) {

            //Skip product if last updated is less than last sync
            $last_updated = $product->updated_at;

            $last_stock_updated = $this->getLastStockUpdated($business_location_id, $product->id);

            if (! empty($last_stock_updated)) {
                $last_updated = strtotime($last_stock_updated) > strtotime($last_updated) ?
                        $last_stock_updated : $last_updated;
            }
            if (! empty($last_synced) && strtotime($last_updated) < strtotime($last_synced)) {
                continue;
            }

            $variations = $product->variations;

            $variation_data = [];
            $new_variations = [];
            $updated_variations = [];
            foreach ($variations as $variation) {
                $variation_arr = [
                    'sku' => $variation->sub_sku,
                ];

                $manage_stock = false;
                if ($product->enable_stock == 1) {
                    $manage_stock = true;
                }

                if (! empty($variation->product_variation->variation_template->amazon_attr_id)) {
                    $variation_arr['attributes'][] = [
                        'id' => $variation->product_variation->variation_template->amazon_attr_id,
                        'option' => $variation->name,
                    ];
                }

                $price = $amazon_api_settings->product_tax_type == 'exc' ? $variation->default_sell_price : $variation->sell_price_inc_tax;

                if (! empty($amazon_api_settings->default_selling_price_group)) {
                    $group_prices = $this->productUtil->getVariationGroupPrice($variation->id, $amazon_api_settings->default_selling_price_group, $product->tax_id);

                    $price = $amazon_api_settings->product_tax_type == 'exc' ? $group_prices['price_exc_tax'] : $group_prices['price_inc_tax'];
                }

                //Set product stock
                $qty_available = 0;
                if ($product->enable_stock == 1) {
                    $variation_location_details = $variation->variation_location_details;
                    foreach ($variation_location_details as $vld) {
                        if ($vld->location_id == $business_location_id) {
                            $qty_available = $vld->qty_available;
                        }
                    }
                }

                if (empty($variation->amazon_variation_id)) {
                    $variation_arr['manage_stock'] = $manage_stock;
                    if (in_array('quantity', $amazon_api_settings->product_fields_for_create)) {
                        $variation_arr['stock_quantity'] = $this->formatDecimalPoint($qty_available, 'quantity');
                    } else {
                        //set manage stock and in_stock if quantity disabled
                        if (isset($amazon_api_settings->manage_stock_for_create)) {
                            if ($amazon_api_settings->manage_stock_for_create == 'true') {
                                $variation_arr['manage_stock'] = true;
                            } elseif ($amazon_api_settings->manage_stock_for_create == 'false') {
                                $variation_arr['manage_stock'] = false;
                            } else {
                                unset($variation_arr['manage_stock']);
                            }
                        }
                        if (isset($amazon_api_settings->in_stock_for_create)) {
                            if ($amazon_api_settings->in_stock_for_create == 'true') {
                                $variation_arr['in_stock'] = true;
                            } elseif ($amazon_api_settings->in_stock_for_create == 'false') {
                                $variation_arr['in_stock'] = false;
                            }
                        }
                    }

                    //Set variation images
                    //If media id is set use media id else use image src
                    if (! empty($variation->media) && count($variation->media) > 0 && in_array('image', $amazon_api_settings->product_fields_for_create)) {
                        $url = $variation->media->first()->display_url;
                        $path = $variation->media->first()->display_path;
                        $amazon_media_id = $variation->media->first()->amazon_media_id;
                        if ($this->isValidImage($path)) {
                            $variation_arr['image'] = ! empty($amazon_media_id) ? ['id' => $amazon_media_id] : ['src' => $url];
                        }
                    }

                    $variation_arr['regular_price'] = $this->formatDecimalPoint($price);
                    $new_variations[] = $variation;

                    $variation_data['create'][] = $variation_arr;
                } else {
                    $variation_arr['id'] = $variation->amazon_variation_id;
                    $variation_arr['manage_stock'] = $manage_stock;
                    if (in_array('quantity', $amazon_api_settings->product_fields_for_update)) {
                        $variation_arr['stock_quantity'] = $this->formatDecimalPoint($qty_available, 'quantity');
                    } else {
                        //set manage stock and in_stock if quantity disabled
                        if (isset($amazon_api_settings->manage_stock_for_update)) {
                            if ($amazon_api_settings->manage_stock_for_update == 'true') {
                                $variation_arr['manage_stock'] = true;
                            } elseif ($amazon_api_settings->manage_stock_for_update == 'false') {
                                $variation_arr['manage_stock'] = false;
                            } else {
                                unset($variation_arr['manage_stock']);
                            }
                        }
                        if (isset($amazon_api_settings->in_stock_for_update)) {
                            if ($amazon_api_settings->in_stock_for_update == 'true') {
                                $variation_arr['in_stock'] = true;
                            } elseif ($amazon_api_settings->in_stock_for_update == 'false') {
                                $variation_arr['in_stock'] = false;
                            }
                        }
                    }

                    //Set variation images
                    //If media id is set use media id else use image src
                    if (! empty($variation->media) && count($variation->media) > 0 && in_array('image', $amazon_api_settings->product_fields_for_update)) {
                        $url = $variation->media->first()->display_url;
                        $path = $variation->media->first()->display_path;
                        $amazon_media_id = $variation->media->first()->amazon_media_id;
                        if ($this->isValidImage($path)) {
                            $variation_arr['image'] = ! empty($amazon_media_id) ? ['id' => $amazon_media_id] : ['src' => $url];
                        }
                    }

                    //assign price
                    if (in_array('price', $amazon_api_settings->product_fields_for_update)) {
                        $variation_arr['regular_price'] = $this->formatDecimalPoint($price);
                    }

                    $variation_data['update'][] = $variation_arr;
                    $updated_variations[] = $variation;
                }
            }

            if (! empty($variation_data)) {
                //get product variation data from amazon
                $response = new stdClass();

                //update amazon_variation_id
                if (! empty($response->create)) {
                    foreach ($response->create as $key => $value) {
                        $new_variation = $new_variations[$key];
                        if ($value->id != 0) {
                            $new_variation->amazon_variation_id = $value->id;
                            $media = $new_variation->media->first();
                            if (! empty($media)) {
                                $media->amazon_media_id = ! empty($value->image->id) ? $value->image->id : null;
                                $media->save();
                            }
                        } else {
                            if (! empty($value->error->data->resource_id)) {
                                $new_variation->amazon_variation_id = $value->error->data->resource_id;
                            }
                        }
                        $new_variation->save();
                    }
                }

                //Update media id if changed from woocommerce site
                if (! empty($response->update)) {
                    foreach ($response->update as $key => $value) {
                        $updated_variation = $updated_variations[$key];
                        if ($value->id != 0) {
                            $media = $updated_variation->media->first();
                            if (! empty($media)) {
                                $media->amazon_media_id = ! empty($value->image->id) ? $value->image->id : null;
                                $media->save();
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Synchronizes Amazon Orders with POS sales
     *
     * @param  int  $business_id
     * @param  int  $user_id
     * @return void
     */
    public function syncOrders($business_id, $user_id)
    {
        $last_synced = $this->getLastSync($business_id, 'orders', false);
        $orders = $this->getAllResponse($business_id, 'orders');
        
        $amazon_sells = Transaction::where('business_id', $business_id)
                                ->whereNotNull('amazon_order_id')
                                ->with('sell_lines', 'sell_lines.product', 'payment_lines')
                                ->get();

        $new_orders = [];
        $updated_orders = [];
        
        $amazon_api_settings = $this->get_api_settings($business_id);
        $business = Business::find($business_id);

        $skipped_orders = ! empty($business->amazon_skipped_orders) ? json_decode($business->amazon_skipped_orders, true) : [];

        $business_data = [
            'id' => $business_id,
            'accounting_method' => $business->accounting_method,
            'location_id' => $amazon_api_settings->location_id,
            'pos_settings' => json_decode($business->pos_settings, true),
            'business' => $business,
        ];

        $created_data = [];
        $updated_data = [];
        $create_error_data = [];
        $update_error_data = [];
        dd($orders);
        try{
        foreach ($orders as $order) {
            //Only consider orders modified after last sync
            
            if ((!empty($last_synced) && strtotime($order['LastUpdateDate']) <= strtotime($last_synced) && !in_array($order['AmazonOrderId'], $skipped_orders)) || in_array($order['OrderStatus'], ['auto-draft','Pending','Canceled'])) {
                continue;
            }
            //Search if order already exists
            $sell = $amazon_sells->filter(function ($item) use ($order) {
                return $item->amazon_order_id == $order['AmazonOrderId'];
            })->first();
            $order_number = $order['AmazonOrderId'];
            
            $sell_status = $this->woocommerceOrderStatusToPosSellStatus($order['OrderStatus'], $business_id);
            if ($sell_status == 'draft') {
                $order_number .= ' ('.__('sale.draft').')';
            }
            if (empty($sell)) {
                $created = $this->createNewSaleFromOrder($business_id, $user_id, $order, $business_data);
                $created_data[] = $order_number;
                

                if ($created !== true) {
                    $create_error_data[] = $created;
                }
                
            } else {
                
                $updated = $this->updateSaleFromOrder($business_id, $user_id, $order, $sell, $business_data);
                $updated_data[] = $order_number;

                if ($updated !== true) {
                    $update_error_data[] = $updated;
                }
            }
            
        }
        }catch(\Exception $e){
            dd($e);
        }
        
        //Create log
        if (!empty($created_data)) {
            try{
            $this->createSyncLog($business_id, $user_id, 'orders', 'created', $created_data, $create_error_data);
            }catch(\Exception $e){
                dd($e);
            }
        }
        if (! empty($updated_data)) {
            $this->createSyncLog($business_id, $user_id, 'orders', 'updated', $updated_data, $update_error_data);
        }

        if (empty($created_data) && empty($updated_data)) {
            $error_data = $create_error_data + $update_error_data;
            $this->createSyncLog($business_id, $user_id, 'orders', null, [], $error_data);
        }
    }

    /**
     * Creates new sales in POSfrom woocommerce order list
     *
     * @param  id  $business_id
     * @param  id  $user_id
     * @param  obj  $order
     * @param  array  $business_data
     */
    public function createNewSaleFromOrder($business_id, $user_id, $order, $business_data)
    {
        $input = $this->formatOrderToSale($business_id, $user_id, $order);
        if (! empty($input['has_error'])) {
            return $input['has_error'];
        }

        $invoice_total = [
            'total_before_tax' => isset($order['OrderTotal']['Amount']) ? $order['OrderTotal']['Amount'] : 0,
            'tax' => 0,
        ];

        DB::beginTransaction();

        $transaction = $this->transactionUtil->createSellTransaction($business_id, $input, $invoice_total, $user_id, false);
        $transaction->amazon_order_id = $order['AmazonOrderId'];
        $transaction->save();
        

        //Create sell lines
        $this->transactionUtil->createOrUpdateSellLines($transaction, $input['products'], $input['location_id'], false, null, ['amazon_line_items_id' => 'line_item_id'], false);

        $this->transactionUtil->createOrUpdatePaymentLines($transaction, $input['payment'], $business_id, $user_id, false);


        if ($input['status'] == 'final') {
            
            //update product stock
            foreach ($input['products'] as $product) {
                if ($product['enable_stock']) {
                    $this->productUtil->decreaseProductQuantity(
                        $product['product_id'],
                        $product['variation_id'],
                        $input['location_id'],
                        $product['quantity']
                    );
                }
            }
            //Update payment status
            $transaction->payment_status = 'paid';
            $transaction->save();
            try {
                $this->transactionUtil->mapPurchaseSell($business_data, $transaction->sell_lines, 'purchase');
            } catch (PurchaseSellMismatch $e) {
                DB::rollBack();
                dd($e);

                $this->add_to_skipped_orders($business_data['business'], $order['AmazonOrderId']);

                return [
                    'error_type' => 'order_insuficient_product_qty',
                    'order_number' => $order['AmazonOrderId'],
                    'msg' => $e->getMessage(),
                ];
            }
        }

        $this->remove_from_skipped_orders($business_data['business'], $order['AmazonOrderId']);
        
        DB::commit();
        
        return true;
    }

    /**
     * Formats Woocommerce order response to pos sale request
     *
     * @param  id  $business_id
     * @param  id  $user_id
     * @param  obj  $order
     * @param  obj  $sell = null
     */
    public function formatOrderToSale($business_id, $user_id, $order, $sell = null)
    {
        $amazon_api_settings = $this->get_api_settings($business_id);

        //Create sell line data
        $product_lines = [];

        //For updating sell lines
        $sell_lines = [];
        if (! empty($sell)) {
            $sell_lines = $sell->sell_lines;
        }
        
        $amazon_client = $this->amazon_client($business_id);
        $order_items = [];
        try{
            $ordersApi = $amazon_client->orders();
            $response = $ordersApi->getOrderItems(
                orderId: $order['AmazonOrderId']
            );
            $data = $response->json();
            $order_items = $data['payload']['OrderItems'];
        }catch(\Exception $e){
            $order_items = [];
        }
        // $order_items = '[{"ProductInfo":{"NumberOfItems":"1"},"BuyerInfo":[],"ItemTax":{"CurrencyCode":"EUR","Amount":"7.29"},"QuantityShipped":1,"ItemPrice":{"CurrencyCode":"EUR","Amount":"42.00"},"ASIN":"B07ZTHSVCK","SellerSKU":"UG-965T-RORR","Title":"TUBOPLUS - TuboX4 Crystal + Bomba con Man\u00f3metro - Presurizador de Pelotas de Tenis y Padel","IsGift":"false","IsTransparency":false,"QuantityOrdered":1,"PromotionDiscountTax":{"CurrencyCode":"EUR","Amount":"0.00"},"PromotionDiscount":{"CurrencyCode":"EUR","Amount":"0.00"},"OrderItemId":"38488520990362"}]';
        // $order_items = json_decode($order_items,true);
        foreach ($order_items as $product_line) {
            if($product_line['QuantityShipped'] < 1){
                continue;
            }
            $product = Product::where('business_id', $business_id)
            ->where('amazon_product_id', $product_line['SellerSKU'])
            ->with(['variations'])
            ->first();
            $item_price = isset($product_line['ItemPrice']['Amount']) ? $product_line['ItemPrice']['Amount'] : 0;
            $unit_price = $item_price / $product_line['QuantityShipped'];
            $line_tax = isset($product_line['ItemTax']) ? $product_line['ItemTax']['Amount'] : 0;
            $unit_line_tax = $line_tax / $product_line['QuantityShipped'];
            $unit_price_inc_tax = $unit_price + $unit_line_tax;
            

            if (!empty($product)) {

                //Set sale line variation;If single product then first variation
                //else search for amazon_variation_id in all the variations
                if ($product->type == 'single') {
                    $variation = $product->variations->first();
                } else {
                    foreach ($product->variations as $v) {
                        if ($v->amazon_variation_id == $product_line['SellerSKU']) {
                            $variation = $v;
                        }
                    }
                }
                

                if (empty($variation)) {
                    return ['has_error' => [
                        'error_type' => 'order_product_not_found',
                        'order_number' => $order['AmazonOrderId'],
                        'product' => $product_line['Title'].' SKU:'.$product_line['SellerSKU'],
                    ],
                    ];
                    exit;
                }

                //Check if line tax exists append to sale line data
                $tax_id = null;
                // if (!empty($product_line['ItemTax'])) {
                //     foreach ($product_line->taxes as $tax) {
                //         $pos_tax = TaxRate::where('business_id', $business_id)
                //         ->where('amazon_tax_rate_id', $tax->id)
                //         ->first();

                //         if (! empty($pos_tax)) {
                //             $tax_id = $pos_tax->id;
                //             break;
                //         }
                //     }
                // }
                $product_data = [
                    'product_id' => $product->id,
                    'unit_price' => $unit_price,
                    'unit_price_inc_tax' => $unit_price_inc_tax,
                    'variation_id' => $variation->id,
                    'quantity' => $product_line['QuantityShipped'],
                    'enable_stock' => $product->enable_stock,
                    'item_tax' => $line_tax,
                    'tax_id' => $tax_id,
                    'line_item_id' => $product_line['OrderItemId'],
                ];
                //append transaction_sell_lines_id if update
                if (! empty($sell_lines)) {
                    foreach ($sell_lines as $sell_line) {
                        if ($sell_line->amazon_line_items_id ==
                            $product_line['OrderItemId']) {
                            $product_data['transaction_sell_lines_id'] = $sell_line->id;
                        }
                    }
                }

                $product_lines[] = $product_data;
            } else {
                return ['has_error' => [
                    'error_type' => 'order_product_not_found',
                    'order_number' => $order['AmazonOrderId'],
                    'product' => $product_line['Title'].' SKU:'.$product_line['SellerSKU'],
                ],
                ];
                exit;
            }
        }
        //Get customer details
        // try{
        //     $ordersApi = $amazon_client->orders();
        //     $response = $ordersApi->getOrderAddress(
        //         orderId: $order['AmazonOrderId']
        //     );
        //     $data = $response->json();
        //     $customer = $data;//$data['payload']['OrderItems'];
        // }catch(\Exception $e){
        //     dd($e);
        // }
        // dd($customer);
        $order_customer_id = '';

        $customer_details = [];
        //If Customer empty skip get guest customer details from billing address
        if (empty($order_customer_id)) {
            $name = isset($order['ShippingAddress']['Name']) ? $order['ShippingAddress']['Name'] : null;
            if(!empty($name)){
                list($f_name,$l_name) = explode(' ',$name);
            }else{
                $f_name = '';
                $l_name = '';
            }
            $customer_details = [
                'first_name' => $f_name,
                'last_name' => $l_name,
                'email' => isset($order['BuyerInfo']['BuyerEmail']) ? $order['BuyerInfo']['BuyerEmail'] : null,
                'name' => $name,
                'mobile' => isset($order['ShippingAddress']['Phone']) ? $order['ShippingAddress']['Phone'] : 'N/A',
                'address_line_1' => isset($order['ShippingAddress']['AddressLine1']) ? $order['ShippingAddress']['AddressLine1'] : null,
                'address_line_2' => isset($order['ShippingAddress']['AddressLine2']) ? $order['ShippingAddress']['AddressLine2'] : null,
                'city' => isset($order['ShippingAddress']['City']) ? $order['ShippingAddress']['City'] : null,
                'state' => isset($order['ShippingAddress']['StateOrRegion']) ? $order['ShippingAddress']['StateOrRegion'] : null,
                'country' => isset($order['ShippingAddress']['CountryCode']) ? $order['ShippingAddress']['CountryCode'] : null,
                'zip_code' => isset($order['ShippingAddress']['PostalCode']) ? $order['ShippingAddress']['PostalCode'] : null,
            ];
        } 


        if (! empty($customer_details['email'])) {
            $customer = Contact::where('business_id', $business_id)
                            ->where('email', $customer_details['email'])
                            ->OnlyCustomers()
                            ->first();
        }

        if (empty($order_customer_id) && empty($customer_details['email'])) {
            $contactUtil = new ContactUtil;
            $customer = $contactUtil->getWalkInCustomer($business_id, false);
        }

        //If customer not found create new
        if (empty($customer)) {
            $ref_count = $this->transactionUtil->setAndGetReferenceCount('contacts', $business_id);
            $contact_id = $this->transactionUtil->generateReferenceNumber('contacts', $ref_count, $business_id);

            $customer_data = [
                'business_id' => $business_id,
                'type' => 'customer',
                'first_name' => $customer_details['first_name'],
                'last_name' => $customer_details['last_name'],
                'name' => $customer_details['name'],
                'email' => $customer_details['email'],
                'contact_id' => $contact_id,
                'mobile' => $customer_details['mobile'],
                'city' => $customer_details['city'],
                'state' => $customer_details['state'],
                'country' => $customer_details['country'],
                'created_by' => $user_id,
                'address_line_1' => $customer_details['address_line_1'],
                'address_line_2' => $customer_details['address_line_2'],
                'zip_code' => $customer_details['zip_code'],
            ];

            //if name is blank make email address as name
            if (empty(trim($customer_data['name']))) {
                $customer_data['first_name'] = $customer_details['email'];
                $customer_data['name'] = $customer_details['email'];
            }
            $customer = Contact::create($customer_data);
        }


        $sell_status = $this->woocommerceOrderStatusToPosSellStatus(strtolower($order['OrderStatus']), $business_id);

        $shipping_status = $this->woocommerceOrderStatusToPosShippingStatus(strtolower($order['OrderStatus']), $business_id);
        $shipping_address = [];
        if (!empty($customer_data['name'])) {
            $shipping_address[] = $customer_data['name'];
        }
        if (isset($order['ShippingAddress']['CompanyName'])) {
            $shipping_address[] = $order['ShippingAddress']['CompanyName'];
        }
        if (!empty($customer_data['address_line_1'])) {
            $shipping_address[] = $customer_details['address_line_1'];
        }
        if (!empty($customer_data['address_line_2'])) {
            $shipping_address[] = $customer_details['address_line_2'];
        }
        if (!empty($customer_data['city'])) {
            $shipping_address[] = $customer_details['city'];
        }
        if (!empty($customer_data['state'])) {
            $shipping_address[] = $customer_details['state'];
        }
        if (!empty($customer_data['country'])) {
            $shipping_address[] = $customer_details['country'];
        }
        if (!empty($customer_data['zip_code'])) {
            $shipping_address[] = $customer_details['zip_code'];
        }
        $addresses['shipping_address'] = [
            'shipping_name' => $customer_data['name'],
            'company' => isset($order['ShippingAddress']['CompanyName']) ? $order['ShippingAddress']['CompanyName'] : '',
            'shipping_address_line_1' => $customer_data['address_line_1'],
            'shipping_address_line_2' => $customer_data['address_line_2'],
            'shipping_city' => $customer_data['city'],
            'shipping_state' => $customer_data['state'],
            'shipping_country' => $customer_data['country'],
            'shipping_zip_code' => $customer_data['zip_code'],
        ];
        $addresses['billing_address'] = [
            'billing_name' => $customer_data['name'],
            'company' => isset($order['ShippingAddress']['CompanyName']) ? $order['ShippingAddress']['CompanyName'] : '',
            'billing_address_line_1' => $customer_data['address_line_1'],
            'billing_address_line_2' => $customer_data['address_line_2'],
            'billing_city' => $customer_data['city'],
            'billing_state' => $customer_data['state'],
            'billing_country' => $customer_data['country'],
            'billing_zip_code' => $customer_data['zip_code'],
        ];


        $shipping_lines_array = [];
        // if (! empty($order->shipping_lines)) {
        //     foreach ($order->shipping_lines as $shipping_lines) {
        //         $shipping_lines_array[] = $shipping_lines->method_title;
        //     }
        // }

        $new_sell_data = [
            'business_id' => $business_id,
            'location_id' => $amazon_api_settings->location_id,
            'contact_id' => $customer->id,
            'discount_type' => 'fixed',
            'discount_amount' => 0,
            'shipping_charges' => 0,
            'final_total' => isset($order['OrderTotal']['Amount']) ? $order['OrderTotal']['Amount'] : 0,
            'created_by' => $user_id,
            'status' => $sell_status == 'quotation' ? 'draft' : $sell_status,
            'is_quotation' => $sell_status == 'quotation' ? 1 : 0,
            'sub_status' => $sell_status == 'quotation' ? 'quotation' : null,
            'payment_status' => 'paid',
            'additional_notes' => '',
            'transaction_date' => $order['PurchaseDate'],
            'customer_group_id' => $customer->customer_group_id,
            'tax_rate_id' => null,
            'sale_note' => null,
            'commission_agent' => null,
            'invoice_no' => $order['AmazonOrderId'],
            'order_addresses' => json_encode($addresses),
            'shipping_charges' => 0,
            'shipping_details' => !empty($shipping_lines_array) ? implode(', ', $shipping_lines_array) : '',
            'shipping_status' => $shipping_status,
            'shipping_address' => implode(', ', $shipping_address),
        ];

        $payment = [
            'amount' => isset($order['OrderTotal']['Amount']) ? $order['OrderTotal']['Amount'] : 0,
            'method' => 'cash',
            'card_transaction_number' => '',
            'card_number' => '',
            'card_type' => '',
            'card_holder_name' => '',
            'card_month' => '',
            'card_security' => '',
            'cheque_number' => '',
            'bank_account_number' => '',
            'note' => isset($order['PaymentMethodDetails'][0]) ? $order['PaymentMethodDetails'][0] : '',
            'paid_on' => $order['PurchaseDate'],
        ];


        if (! empty($sell) && count($sell->payment_lines) > 0) {
            $payment['payment_id'] = $sell->payment_lines->first()->id;
        }

        $new_sell_data['products'] = $product_lines;
        $new_sell_data['payment'] = [$payment];

        return $new_sell_data;
    }

    /**
     * Updates existing sale
     *
     * @param  id  $business_id
     * @param  id  $user_id
     * @param  obj  $order
     * @param  obj  $sell
     * @param  array  $business_data
     */
    public function updateSaleFromOrder($business_id, $user_id, $order, $sell, $business_data)
    {
        $input = $this->formatOrderToSale($business_id, $user_id, $order, $sell);

        if (! empty($input['has_error'])) {
            return $input['has_error'];
        }

        $invoice_total = [
            'total_before_tax' => isset($order['OrderTotal']['Amount']) ? $order['OrderTotal']['Amount'] : 0,
            'tax' => 0,
        ];

        $status_before = $sell->status;

        DB::beginTransaction();
        $transaction = $this->transactionUtil->updateSellTransaction($sell, $business_id, $input, $invoice_total, $user_id, false, false);

        //Update Sell lines
        $deleted_lines = $this->transactionUtil->createOrUpdateSellLines($transaction, $input['products'], $input['location_id'], true, $status_before, [], false);

        $this->transactionUtil->createOrUpdatePaymentLines($transaction, $input['payment'], null, null, false);

        //Update payment status
        $transaction->payment_status = 'paid';
        $transaction->save();

        //Update product stock
        $this->productUtil->adjustProductStockForInvoice($status_before, $transaction, $input, false);

        try {
            $this->transactionUtil->adjustMappingPurchaseSell($status_before, $transaction, $business_data, $deleted_lines);
        } catch (PurchaseSellMismatch $e) {
            DB::rollBack();

            return [
                'error_type' => 'order_insuficient_product_qty',
                'order_number' => $order->number,
                'msg' => $e->getMessage(),
            ];
        }

        DB::commit();

        return true;
    }

    /**
     * Creates sync log in the database
     *
     * @param  id  $business_id
     * @param  id  $user_id
     * @param  string  $type
     * @param  array  $errors = null
     */
    public function createSyncLog($business_id, $user_id, $type, $operation = null, $data = [], $errors = null)
    {
        AmazonSyncLog::create([
            'business_id' => $business_id,
            'sync_type' => $type,
            'created_by' => $user_id,
            'operation_type' => $operation,
            'data' => ! empty($data) ? json_encode($data) : null,
            'details' => ! empty($errors) ? json_encode($errors) : null,
        ]);
    }

    /**
     * Retrives last synced date from the database
     *
     * @param  id  $business_id
     * @param  string  $type
     * @param  bool  $for_humans = true
     */
    public function getLastSync($business_id, $type, $for_humans = true)
    {
        $last_sync = AmazonSyncLog::where('business_id', $business_id)
                            ->where('sync_type', $type)
                            ->max('created_at');

        //If last reset present make last sync to null
        $last_reset = AmazonSyncLog::where('business_id', $business_id)
                            ->where('sync_type', $type)
                            ->where('operation_type', 'reset')
                            ->max('created_at');
        if (! empty($last_reset) && ! empty($last_sync) && $last_reset >= $last_sync) {
            $last_sync = null;
        }

        if (! empty($last_sync) && $for_humans) {
            $last_sync = \Carbon::createFromFormat('Y-m-d H:i:s', $last_sync)->diffForHumans();
        }

        return $last_sync;
    }

    public function woocommerceOrderStatusToPosSellStatus($status, $business_id)
    {
        $default_status_array = [
            'pending' => 'draft',
            'processing' => 'final',
            'on-hold' => 'draft',
            'completed' => 'final',
            'canceled' => 'draft',
            'refunded' => 'draft',
            'failed' => 'draft',
            'shipped' => 'final',
            'unshipped'=> 'draft'
        ];
        $status = strtolower($status);

        $api_settings = $this->get_api_settings($business_id);
        $status_settings = $api_settings->order_statuses ?? null;

        $sale_status = ! empty($status_settings) ? $status_settings->$status : null;
        $sale_status = empty($sale_status) && array_key_exists($status, $default_status_array) ? $default_status_array[$status] : $sale_status;
        $sale_status = empty($sale_status) ? 'final' : $sale_status;

        return $sale_status;
    }

    public function woocommerceOrderStatusToPosShippingStatus($status, $business_id)
    {
        $api_settings = $this->get_api_settings($business_id);

        $status_settings = $api_settings->shipping_statuses ?? null;

        $shipping_status = ! empty($status_settings) ? $status_settings->$status : null;

        return $shipping_status;
    }

    /**
     * Splits response to list of 100 and merges all
     *
     * @param  int  $business_id
     * @param  string  $endpoint
     * @param  array  $params = []
     * @return array
     */
    public function getAllResponse($business_id, $endpoint, $params = [])
    {

        //woocommerce api client object
        $amazon = $this->amazon_client($business_id);
        $amazon_api_settings = $this->get_api_settings($business_id);
        
        if(strpos($amazon_api_settings->markete_places,',') !== false){
            $marketePlaceIds = explode(',', $amazon_api_settings->markete_places);
        }else{
            $marketePlaceIds = [$amazon_api_settings->markete_places];
        }
        
        $page = 1;
        $list = [];
        $all_list = [];
        $params['per_page'] = 100;
        $nextToken = false;
        do {
            $params['page'] = $page;
            try {
                //$list = $amazon->get($endpoint, $params);
                $ordersApi = $amazon->orders();
                $response = $ordersApi->getOrders(
                    createdAfter: date('Y-m-d',strtotime('-7 day')),
                    marketplaceIds: $marketePlaceIds,
                );
                $list = $response->json();
            } catch (\Exception $e) {
                return [];
            }
            //dd($list['payload']['Orders']);
            if(isset($list['payload']['Orders'])){
                $all_list = array_merge($all_list, $list['payload']['Orders']);
            }
            $page++;
        } while ($nextToken);

        return $all_list;
    }

    /**
     * Retrives all tax rates from woocommerce api
     *
     * @param  id  $business_id
     * @param  obj  $tax_rates
     */
    public function getTaxRates($business_id)
    {
        $tax_rates = $this->getAllResponse($business_id, 'taxes');

        return $tax_rates;
    }

    public function getLastStockUpdated($location_id, $product_id)
    {
        $last_updated = VariationLocationDetails::where('location_id', $location_id)
                                    ->where('product_id', $product_id)
                                    ->max('updated_at');

        return $last_updated;
    }

    private function formatDecimalPoint($number, $type = 'currency')
    {
        $precision = 4;
        $currency_precision = session('business.currency_precision', 2);
        $quantity_precision = session('business.quantity_precision', 2);

        if ($type == 'currency' && ! empty($currency_precision)) {
            $precision = $currency_precision;
        }
        if ($type == 'quantity' && ! empty($quantity_precision)) {
            $precision = $quantity_precision;
        }

        return number_format((float) $number, $precision, '.', '');
    }

    public function isValidImage($path)
    {
        $valid_extenstions = ['jpg', 'jpeg', 'png', 'gif'];

        return ! empty($path) && file_exists($path) && in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), $valid_extenstions);
    }
}
