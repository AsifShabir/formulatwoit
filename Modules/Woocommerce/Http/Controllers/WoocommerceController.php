<?php

namespace Modules\Woocommerce\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Media;
use App\Product;
use App\SellingPriceGroup;
use App\SyncCategory;
use App\SyncOrder;
use App\SyncProduct;
use App\System;
use App\TaxRate;
use App\Utils\ModuleUtil;
use App\Variation;
use App\VariationTemplate;
use App\WoocommerceProduct;
use App\WoocommerceStoreCredential;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Woocommerce\Entities\WoocommerceSyncLog;
use Modules\Woocommerce\Utils\WoocommerceUtil;
use Yajra\DataTables\Facades\DataTables;

class WoocommerceController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $woocommerceUtil;

    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param  WoocommerceUtil  $woocommerceUtil
     * @return void
     */
    public function __construct(WoocommerceUtil $woocommerceUtil, ModuleUtil $moduleUtil)
    {
        $this->woocommerceUtil = $woocommerceUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {

        $business_id = request()->session()->get('business.id');
        $store_id = request()->session()->get('store_id');

        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'woocommerce_module'))) {
            abort(403, 'Unauthorized action.');
        }

        $tax_rates = TaxRate::where('business_id', $business_id)->get();

        $woocommerce_tax_rates = ['' => __('messages.please_select')];

        $woocommerce_api_settings = $this->woocommerceUtil->get_api_settings($store_id);
        
        $alerts = [];
        //dd($woocommerce_api_settings,$business_id);
        if (empty($woocommerce_api_settings)) {
            $alerts['connection_failed'] = __('woocommerce::lang.unable_to_connect');
        }

        // update syncronizable data 
        // $this->woocommerceUtil->updateCategorySync();
        // $this->woocommerceUtil->updateProductSync();

        // $not_synced_cat_count = SyncCategory::where('store_name', $store_name)->whereNull('woocommerce_cat_id')->count();

        // if (!empty($not_synced_cat_count)) {
        //     $alerts['not_synced_cat'] = $not_synced_cat_count == 1 ? __('woocommerce::lang.one_cat_not_synced_alert') : __('woocommerce::lang.cat_not_sync_alert', ['count' => $not_synced_cat_count]);
        // }

        // $cat_last_sync = $this->woocommerceUtil->getLastSync($business_id, 'categories', false);
        // if (!empty($cat_last_sync)) {
        //     $updated_cat_count = Category::where('business_id', $business_id)
        //         ->where('updated_at', '>', $cat_last_sync)
        //         ->count();
        // }

        // if (!empty($updated_cat_count)) {
        //     $alerts['updated_cat'] = $updated_cat_count == 1 ? __('woocommerce::lang.one_cat_updated_alert') : __('woocommerce::lang.cat_updated_alert', ['count' => $updated_cat_count]);
        // }

        // $products_last_synced = $this->woocommerceUtil->getLastSync($business_id, 'all_products', false);
        // $query = SyncProduct::where('store_name', $store_name)
        //     ->whereNull('woocommerce_product_id');
        // if (!empty($woocommerce_api_settings->location_id)) {
        //     $query->ForLocation($woocommerce_api_settings->location_id);
        // }
        // $not_synced_product_count = $query->get()->count();
        // if (!empty($not_synced_product_count)) {
        //     $alerts['not_synced_product'] = $not_synced_product_count == 1 ? __('woocommerce::lang.one_product_not_sync_alert') : __('woocommerce::lang.product_not_sync_alert', ['count' => $not_synced_product_count]);
        // }
        // if (!empty($products_last_synced)) {
        //     $updated_product_count = SyncProduct::where('store_name', $store_name)
        //         ->where('updated_at', '>', $products_last_synced)
        //         ->count();
        // }

        // if (!empty($updated_product_count)) {
        //     $alerts['not_updated_product'] = $updated_product_count == 1 ? __('woocommerce::lang.one_product_updated_alert') : __('woocommerce::lang.product_updated_alert', ['count' => $updated_product_count]);
        // }

        // $orders_last_synced = $this->woocommerceUtil->getLastSync($business_id, 'orders', false);
        // $query = SyncOrder::where('store_name', $store_name)
        //     ->whereNull('woocommerce_order_id');

        // $not_synced_order_count = $query->get()->count();
        // if (!empty($not_synced_order_count)) {
        //     $alerts['not_synced_order'] = $not_synced_order_count == 1 ? __('woocommerce::lang.one_order_not_sync_alert') : __('woocommerce::lang.order_not_sync_alert', ['count' => $not_synced_order_count]);
        // }
        // if (!empty($orders_last_synced)) {
        //     $updated_order_count = SyncOrder::where('store_name', $store_name)
        //         ->where('updated_at', '>', $orders_last_synced)
        //         ->count();
        // }

        // if (!empty($updated_order_count)) {
        //     $alerts['not_updated_order'] = $updated_order_count == 1 ? __('woocommerce::lang.one_order_updated_alert') : __('woocommerce::lang.order_updated_alert', ['count' => $updated_order_count]);
        // }

        // $notAllowed = $this->woocommerceUtil->notAllowedInDemo();
        // if (empty($notAllowed)) {
        //     $response = $this->woocommerceUtil->getTaxRates($business_id);
        //     if (!empty($response)) {
        //         foreach ($response as $r) {
        //             $woocommerce_tax_rates[$r->id] = $r->name;
        //         }
        //     }
        // }


        $active_store = [];

        //Check if session has business id
        $active_store = WoocommerceStoreCredential::where('business_id', $business_id)->pluck('app_name', 'id')->toArray();
        // $active_store = BusinessLocation::where('business_id',$business_id)->pluck('name','id')->toArray();

        // Check if session has store_id
        if (!session()->has('store_id') || empty(session('store_id'))) {
            $alerts['connection_failed'] = __('woocommerce::lang.select_a_store');
        }


        return view('woocommerce::woocommerce.index')
            ->with(compact('active_store', 'tax_rates', 'woocommerce_tax_rates', 'alerts'));
    }

    /**
     * Displays form to update woocommerce api settings.
     *
     * @return Response
     */
    public function apiSettings()
    {
        $business_id = request()->session()->get('business.id');

        if (!(auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'woocommerce_module') && auth()->user()->can('woocommerce.access_woocommerce_api_settings')))) {
            abort(403, 'Unauthorized action.');
        }

        $default_settings = [
            'woocommerce_app_url' => '',
            'woocommerce_consumer_key' => '',
            'woocommerce_consumer_secret' => '',
            'location_id' => null,
            'default_tax_class' => '',
            'product_tax_type' => 'inc',
            'default_selling_price_group' => '',
            'product_fields_for_create' => ['category', 'quantity'],
            'product_fields_for_update' => ['name', 'price', 'category', 'quantity'],
        ];

        // Retrieve WooCommerce store credentials based on business_id
        $stored_credentials = WoocommerceStoreCredential::where('business_id', $business_id)->get();

        $multi_stores = [];

        if ($stored_credentials->isNotEmpty()) {
            foreach ($stored_credentials as $credential) {
                $store = [
                    'woocommerce_app_name' => $credential->app_name,
                    'woocommerce_app_url' => $credential->app_url,
                    'woocommerce_consumer_key' => $credential->consumer_key,
                    'woocommerce_consumer_secret' => $credential->consumer_secret,
                    'location_id' => $credential->location_id,
                    'enable_auto_sync' => $credential->enable_auto_sync,
                    'woocommerce_wh_oc_secret' => $credential->woocommerce_wh_oc_secret ?? '',
                    'woocommerce_wh_ou_secret' => $credential->woocommerce_wh_ou_secret ?? '',
                    'woocommerce_wh_od_secret' => $credential->woocommerce_wh_od_secret ?? '',
                    'woocommerce_wh_or_secret' => $credential->woocommerce_wh_or_secret ?? '',
                ];
                $multi_stores[] = $store;
            }
        } else {
            $multi_stores[] = [
                'woocommerce_app_name' => '',
                'woocommerce_app_url' => '',
                'woocommerce_consumer_key' => '',
                'woocommerce_consumer_secret' => '',
                'location_id' => '',
                'enable_auto_sync' => false,
                'woocommerce_wh_oc_secret' => '',
                'woocommerce_wh_ou_secret' => '',
                'woocommerce_wh_od_secret' => '',
                'woocommerce_wh_or_secret' => '',
            ];
        }

        $price_groups = SellingPriceGroup::where('business_id', $business_id)
            ->pluck('name', 'id')->prepend(__('lang_v1.default'), '');

        $business = Business::find($business_id);

        $notAllowed = $this->woocommerceUtil->notAllowedInDemo();
        if (!empty($notAllowed)) {
            $business = null;
        }

        $locations = BusinessLocation::forDropdown($business_id);
        $module_version = System::getProperty('woocommerce_version');

        $cron_job_command = $this->moduleUtil->getCronJobCommand();

        $shipping_statuses = $this->moduleUtil->shipping_statuses();

        return view('woocommerce::woocommerce.api_settings')
            ->with(compact('multi_stores', 'default_settings', 'locations', 'price_groups', 'module_version', 'cron_job_command', 'business', 'shipping_statuses'));
    }

    /**
     * Updates woocommerce api settings.
     *
     * @return Response
     */
    public function updateSettings(Request $request)
    {
        $business_id = request()->session()->get('business.id');

        if (!(auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'woocommerce_module') && auth()->user()->can('woocommerce.access_woocommerce_api_settings')))) {
            abort(403, 'Unauthorized action.');
        }

        $notAllowed = $this->woocommerceUtil->notAllowedInDemo();
        if (!empty($notAllowed)) {
            return $notAllowed;
        }

        try {
            $input = $request->except('_token');

            // Fetch all existing credentials for the business
            $existingCredentials = WoocommerceStoreCredential::where('business_id', $business_id)->get();
            $updatedCredentialIds = [];

            // Loop through each store
            foreach ($input['woocommerce_app_name'] as $key => $value) {
                $credentials = [
                    'business_id' => $business_id,
                    'app_name' => $input['woocommerce_app_name'][$key],
                    'app_url' => $input['woocommerce_app_url'][$key],
                    'consumer_key' => $input['woocommerce_consumer_key'][$key],
                    'consumer_secret' => $input['woocommerce_consumer_secret'][$key],
                    'location_id' => $input['location_id'][$key] ?? null,
                    'enable_auto_sync' => isset($input['enable_auto_sync'][$key]) ? true : false,
                ];

                // Check if credentials already exist for this store
                $existingCredential = $existingCredentials->where('app_name', $input['woocommerce_app_name'][$key])->first();

                if ($existingCredential) {
                    // Update the existing credential
                    $existingCredential->update($credentials);
                    $updatedCredentialIds[] = $existingCredential->id;
                } else {
                    // Create new credentials if they don't exist
                    $newCredential = WoocommerceStoreCredential::create($credentials);
                    $updatedCredentialIds[] = $newCredential->id;
                }
            }

            // Delete any credentials that were not updated or created during this request
            WoocommerceStoreCredential::where('business_id', $business_id)
                ->whereNotIn('id', $updatedCredentialIds)
                ->delete();

            $output = [
                'success' => 1,
                'msg' => trans('lang_v1.updated_succesfully'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => trans('messages.something_went_wrong'),
            ];
        }

        return redirect()->back()->with(['status' => $output]);
    }

    public function updateSessionLocation(Request $request)
    {
        // Clear existing session values for  store_id
        request()->session()->forget(['store_id']);

        // Get new values from the request
        $store_id = $request->input('store_id');
        request()->session()->put('store_id', $store_id);

        // Return the updated session data as JSON response
        return response()->json(request()->session()->all(), 200);
    }

    public function getProducts()
    {
        $response = $this->woocommerceUtil->getProducts();

        return response()->json([
            'success' => $response == 'success',
            'msg' => $response == 'success' ? 'Products synced successfully.' : 'Failed to sync stock.',
        ]);
    }

    public function syncStock()
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0);

        $response = $this->woocommerceUtil->syncStock();

        return response()->json([
            'success' => $response == 'success',
            'msg' => $response ? 'Stock synchronization successfully.' : 'Failed to sync stock.',
        ]);
    }

    public function getProductType(Request $request)
    {
        $wooId = $request->input('woocommerce_product_id');

        request()->session()->forget(['woocommerce_product_id']);
        request()->session()->put('woocommerce_product_id', $wooId);

        if (!$wooId) {
            return response()->json(['error' => 'woo product ID is required'], 400);
        }

        $product = WoocommerceProduct::where('woo_id', $wooId)->first();

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        return response()->json(['product_type' => $product->type]);
    }


    /**
     * Synchronizes pos categories with Woocommerce categories
     *
     * @return Response
     */
    public function syncCategories()
    {
        $business_id = request()->session()->get('business.id');

        if (!(auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'woocommerce_module') && auth()->user()->can('woocommerce.syc_categories')))) {
            abort(403, 'Unauthorized action.');
        }

        $notAllowed = $this->woocommerceUtil->notAllowedInDemo();
        if (!empty($notAllowed)) {
            return $notAllowed;
        }

        try {
            DB::beginTransaction();
            $user_id = request()->session()->get('user.id');

            $this->woocommerceUtil->syncCategories($business_id, $user_id);

            DB::commit();

            $output = [
                'success' => 1,
                'msg' => __('woocommerce::lang.synced_successfully'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            if (get_class($e) == 'Modules\Woocommerce\Exceptions\WooCommerceError') {
                $output = [
                    'success' => 0,
                    'msg' => $e->getMessage(),
                ];
            } else {
                \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

                $output = [
                    'success' => 0,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }
        }

        return $output;
    }

    /**
     * Synchronizes pos products with Woocommerce products
     *
     * @return Response
     */
    public function syncProducts()
    {
        $notAllowed = $this->woocommerceUtil->notAllowedInDemo();
        if (!empty($notAllowed)) {
            return $notAllowed;
        }

        $business_id = request()->session()->get('business.id');
        if (!(auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'woocommerce_module') && auth()->user()->can('woocommerce.sync_products')))) {
            abort(403, 'Unauthorized action.');
        }

        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0);

        try {
            $user_id = request()->session()->get('user.id');
            $sync_type = request()->input('type');

            DB::beginTransaction();

            $offset = request()->input('offset');
            $limit = 100;
            $all_products = $this->woocommerceUtil->syncProducts($business_id, $user_id, $sync_type, $limit, $offset);
            $total_products = count($all_products);

            DB::commit();
            $msg = $total_products > 0 ? __('woocommerce::lang.n_products_synced_successfully', ['count' => $total_products]) : __('woocommerce::lang.synced_successfully');
            $output = [
                'success' => 1,
                'msg' => $msg,
                'total_products' => $total_products,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            if (get_class($e) == 'Modules\Woocommerce\Exceptions\WooCommerceError') {
                $output = [
                    'success' => 0,
                    'msg' => $e->getMessage(),
                ];
            } else {
                \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

                $output = [
                    'success' => 0,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }
        }

        return $output;
    }

    /**
     * Synchronizes Woocommers Orders with POS sales
     *
     * @return Response
     */
    public function syncOrders()
    {
        $notAllowed = $this->woocommerceUtil->notAllowedInDemo();
        if (!empty($notAllowed)) {
            return $notAllowed;
        }

        $business_id = request()->session()->get('business.id');
        if (!(auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'woocommerce_module') && auth()->user()->can('woocommerce.sync_orders')))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            //DB::beginTransaction();
            $user_id = request()->session()->get('user.id');

            $this->woocommerceUtil->syncOrders($business_id, $user_id);

            //DB::commit();

            $output = [
                'success' => 1,
                'msg' => __('woocommerce::lang.synced_successfully'),
            ];
        } catch (\Exception $e) {
            //DB::rollBack();
            dd($e);

            if (get_class($e) == 'Modules\Woocommerce\Exceptions\WooCommerceError') {
                $output = [
                    'success' => 0,
                    'msg' => $e->getMessage(),
                ];
            } else {
                \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

                $output = [
                    'success' => 0,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }
        }

        return $output;
    }

    /**
     * Retrives sync log
     *
     * @return Response
     */
    public function getSyncLog()
    {
        $notAllowed = $this->woocommerceUtil->notAllowedInDemo();
        if (!empty($notAllowed)) {
            return $notAllowed;
        }

        $business_id = request()->session()->get('business.id');
        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'woocommerce_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $last_sync = [
                'categories' => $this->woocommerceUtil->getLastSync($business_id, 'categories'),
                'stock' => $this->woocommerceUtil->getLastSync($business_id, 'stock'),
                'all_products' => $this->woocommerceUtil->getLastSync($business_id, 'all_products'),
                'orders' => $this->woocommerceUtil->getLastSync($business_id, 'orders'),

            ];

            return $last_sync;
        }
    }

    /**
     * Maps POS tax_rates with Woocommerce tax rates.
     *
     * @return Response
     */
    public function mapTaxRates(Request $request)
    {
        $notAllowed = $this->woocommerceUtil->notAllowedInDemo();
        if (!empty($notAllowed)) {
            return $notAllowed;
        }

        $business_id = request()->session()->get('business.id');
        if (!(auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'woocommerce_module') && auth()->user()->can('woocommerce.map_tax_rates')))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->except('_token');
            foreach ($input['taxes'] as $key => $value) {
                $value = !empty($value) ? $value : null;
                TaxRate::where('business_id', $business_id)
                    ->where('id', $key)
                    ->update(['woocommerce_tax_rate_id' => $value]);
            }

            $output = [
                'success' => 1,
                'msg' => __('lang_v1.updated_succesfully'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->back()->with(['status' => $output]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function viewSyncLog()
    {
        $business_id = request()->session()->get('business.id');
        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'woocommerce_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $logs = WoocommerceSyncLog::where('woocommerce_sync_logs.business_id', $business_id)
                ->leftjoin('users as U', 'U.id', '=', 'woocommerce_sync_logs.created_by')
                ->select([
                    'woocommerce_sync_logs.created_at',
                    'sync_type', 'operation_type',
                    DB::raw("CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) as full_name"),
                    'woocommerce_sync_logs.data',
                    'woocommerce_sync_logs.details as log_details',
                    'woocommerce_sync_logs.id as DT_RowId',
                ]);
            $sync_type = [];
            if (auth()->user()->can('woocommerce.syc_categories')) {
                $sync_type[] = 'categories';
            }
            if (auth()->user()->can('woocommerce.sync_products')) {
                $sync_type[] = 'all_products';
                $sync_type[] = 'new_products';
            }

            if (auth()->user()->can('woocommerce.sync_orders')) {
                $sync_type[] = 'orders';
            }
            if (!auth()->user()->can('superadmin')) {
                $logs->whereIn('sync_type', $sync_type);
            }

            return Datatables::of($logs)
                ->editColumn('created_at', function ($row) {
                    $created_at = $this->woocommerceUtil->format_date($row->created_at, true);
                    $for_humans = \Carbon::createFromFormat('Y-m-d H:i:s', $row->created_at)->diffForHumans();

                    return $created_at . '<br><small>' . $for_humans . '</small>';
                })
                ->editColumn('sync_type', function ($row) {
                    $array = [
                        'categories' => __('category.categories'),
                        'all_products' => __('sale.products'),
                        'new_products' => __('sale.products'),
                        'orders' => __('woocommerce::lang.orders'),
                        'stock' => "Stock",
                    ];
                    return $array[$row->sync_type];
                })
                ->editColumn('operation_type', function ($row) {
                    $array = [
                        'created' => __('woocommerce::lang.created'),
                        'updated' => __('woocommerce::lang.updated'),
                        'reset' => __('woocommerce::lang.reset'),
                        'deleted' => __('lang_v1.deleted'),
                        'restored' => __('woocommerce::lang.order_restored'),
                    ];

                    return array_key_exists($row->operation_type, $array) ? $array[$row->operation_type] : '';
                })
                ->editColumn('data', function ($row) {
                    if (!empty($row->data)) {
                        return $row->data;
                        $data = json_decode($row->data, true);
                        
                        if(is_array($data)){
                        return implode(', ', $data) . '<br><small>' . count($data) . ' ' . __('woocommerce::lang.records') . '</small>';
                        }else{
                            return '';
                        }
                    } else {
                        return '';
                    }
                })
                ->editColumn('log_details', function ($row) {
                    $details = '';
                    if (!empty($row->log_details)) {
                        $details = $row->log_details;
                    }

                    return $details;
                })
                ->filterColumn('full_name', function ($query, $keyword) {
                    $query->whereRaw("CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) like ?", ["%{$keyword}%"]);
                })
                ->rawColumns(['created_at', 'data'])
                ->make(true);
        }

        return view('woocommerce::woocommerce.sync_log');
    }

    /**
     * Retrives details of a sync log.
     *
     * @param  int  $id
     * @return Response
     */
    public function getLogDetails($id)
    {
        $business_id = request()->session()->get('business.id');
        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'woocommerce_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $log = WoocommerceSyncLog::where('business_id', $business_id)
                ->find($id);
            $log_details = json_decode($log->details);

            return view('woocommerce::woocommerce.partials.log_details')
                ->with(compact('log_details'));
        }
    }

    /**
     * Resets synced categories
     *
     * @return json
     */
    public function resetCategories()
    {
        $business_id = request()->session()->get('business.id');
        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'woocommerce_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                Category::where('business_id', $business_id)
                    ->update(['woocommerce_cat_id' => null]);
                $user_id = request()->session()->get('user.id');
                $this->woocommerceUtil->createSyncLog($business_id, $user_id, 'categories', 'reset', null);

                $output = [
                    'success' => 1,
                    'msg' => __('woocommerce::lang.cat_reset_success'),
                ];
            } catch (\Exception $e) {
                \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

                $output = [
                    'success' => 0,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }

    /**
     * Resets synced products
     *
     * @return json
     */
    public function resetProducts()
    {
        $business_id = request()->session()->get('business.id');
        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'woocommerce_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                //Update products table
                Product::where('business_id', $business_id)
                    ->update(['woocommerce_product_id' => null, 'woocommerce_media_id' => null]);

                $product_ids = Product::where('business_id', $business_id)
                    ->pluck('id');

                $product_ids = !empty($product_ids) ? $product_ids : [];
                //Update variations table
                Variation::whereIn('product_id', $product_ids)
                    ->update([
                        'woocommerce_variation_id' => null,
                    ]);

                //Update variation templates
                VariationTemplate::where('business_id', $business_id)
                    ->update([
                        'woocommerce_attr_id' => null,
                    ]);

                Media::where('business_id', $business_id)
                    ->update(['woocommerce_media_id' => null]);

                $user_id = request()->session()->get('user.id');
                $this->woocommerceUtil->createSyncLog($business_id, $user_id, 'all_products', 'reset', null);

                $output = [
                    'success' => 1,
                    'msg' => __('woocommerce::lang.prod_reset_success'),
                ];
            } catch (\Exception $e) {
                \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

                $output = [
                    'success' => 0,
                    'msg' => 'File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage(),
                ];
            }

            return $output;
        }
    }
}
