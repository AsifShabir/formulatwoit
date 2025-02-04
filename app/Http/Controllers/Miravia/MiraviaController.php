<?php

namespace App\Http\Controllers\Miravia;

use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Media;
use App\MiraviaProduct;
use App\MiraviaSyncLog;
use App\Product;
use App\SellingPriceGroup;
use App\System;
use App\TaxRate;
use App\Utils\ModuleUtil;
use App\Variation;
use App\VariationTemplate;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Woocommerce\Entities\WoocommerceSyncLog;
use App\Utils\MiraviaUtil;
use Yajra\DataTables\Facades\DataTables;

class MiraviaController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $miraviaUtil;

    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param  miraviaUtil  $miraviaUtil
     * @return void
     */
    public function __construct(MiraviaUtil $miraviaUtil, ModuleUtil $moduleUtil)
    {
        $this->miraviaUtil = $miraviaUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $alerts = [];
    
        try {
            $businessId = request()->session()->get('business.id');
            $miraviaApiSettings = $this->miraviaUtil->get_api_settings($businessId);
    
            if ($miraviaApiSettings && $miraviaApiSettings->miravia_access_token) {
                $response = $this->miraviaUtil->getMiraviaClient($businessId);
    
                if ($response !== 'success') {
                    $alerts['connection_failed'] = __('woocommerce::lang.unable_to_connect');
                }
            } else {
                $alerts['connection_failed'] = __('woocommerce::lang.unable_to_connect');
            }
        } catch (\Exception $e) {
            $alerts['connection_failed'] = __('woocommerce::lang.unable_to_connect');
        }
    
        return view('miravia.index', compact('alerts'));
    }    

    public function getProducts()
    {
        $response = $this->miraviaUtil->getProducts();

        return response()->json([
            'success' => $response == 'success',
            'msg' => $response == 'success' ? 'Products synchronization successfully.' : 'Failed to sync stock.',
        ]);
    }

    public function getOrders()
    {
        $response = $this->miraviaUtil->getOrders();

        return response()->json([
            'success' => $response == 'success',
            'msg' => $response == 'success' ? 'Orders synchronization successfully.' : 'Failed to sync stock.',
        ]);
    }


    public function syncStock()
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0);

        $response = $this->miraviaUtil->syncStock();
        $total_products = count($response);
        $success = !empty($response); // Assuming success if there is a response

        return response()->json([
            'success' => $success,
            'total_products' => $total_products,
            'msg' => $success ? 'Stock synchronization successfully.' : 'Failed to sync stock.',
        ]);
    }


    /**
     * Displays form to update woocommerce api settings.
     *
     * @return Response
     */
    public function apiSettings()
    {
        $business_id = request()->session()->get('business.id');

        $default_settings = [
            'miravia_app_key' => '',
            'miravia_app_secret' => '',
            'miravia_access_token' => '',
            'location_id' => null,
            'default_tax_class' => '',
            'product_tax_type' => 'inc',
            'default_selling_price_group' => '',
            'product_fields_for_create' => ['category', 'quantity'],
            'product_fields_for_update' => ['name', 'price', 'category', 'quantity'],
        ];

        $price_groups = SellingPriceGroup::where('business_id', $business_id)
            ->pluck('name', 'id')->prepend(__('lang_v1.default'), '');

        $business = Business::find($business_id);

        if (!empty($business->miravia_api_settings)) {
            $default_settings = json_decode($business->miravia_api_settings, true);
            if (empty($default_settings['product_fields_for_create'])) {
                $default_settings['product_fields_for_create'] = [];
            }

            if (empty($default_settings['product_fields_for_update'])) {
                $default_settings['product_fields_for_update'] = [];
            }
        }

        $locations = BusinessLocation::forDropdown($business_id);
        $module_version = System::getProperty('woocommerce_version');

        $cron_job_command = $this->moduleUtil->getCronJobCommand();

        $shipping_statuses = $this->moduleUtil->shipping_statuses();

        return view('miravia.api_settings')
            ->with(compact('default_settings', 'locations', 'price_groups', 'module_version', 'cron_job_command', 'business', 'shipping_statuses'));
    }

    /**
     * Updates woocommerce api settings.
     *
     * @return Response
     */
    public function updateSettings(Request $request)
    {
        $business_id = request()->session()->get('business.id');

        try {
            $input = $request->except('_token');

            $input['product_fields_for_create'] = !empty($input['product_fields_for_create']) ? $input['product_fields_for_create'] : [];
            $input['product_fields_for_update'] = !empty($input['product_fields_for_update']) ? $input['product_fields_for_update'] : [];
            $input['order_statuses'] = !empty($input['order_statuses']) ? $input['order_statuses'] : [];
            $input['shipping_statuses'] = !empty($input['shipping_statuses']) ? $input['shipping_statuses'] : [];

            $business = Business::find($business_id);
            $business->miravia_api_settings = json_encode($input);
            // $business->woocommerce_wh_oc_secret = $input['woocommerce_wh_oc_secret'];
            // $business->woocommerce_wh_ou_secret = $input['woocommerce_wh_ou_secret'];
            // $business->woocommerce_wh_od_secret = $input['woocommerce_wh_od_secret'];
            // $business->woocommerce_wh_or_secret = $input['woocommerce_wh_or_secret'];
            $business->save();

            $output = [
                'success' => 1,
                'msg' => trans('lang_v1.updated_succesfully'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => trans('messages.something_went_wrong'),
            ];
        }

        return redirect()->back()->with(['status' => $output]);
    }

    /**
     * Get the product type for the given Miravia product ID.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProductType(Request $request)
    {
        $miraviaProductId = $request->input('miravia_product_id');
        
        request()->session()->forget(['miravia_product_id']);
        request()->session()->put('miravia_product_id', $miraviaProductId);

        if (!$miraviaProductId) {
            return response()->json(['error' => 'Miravia product ID is required'], 400);
        }

        $product = MiraviaProduct::where('miravia_id', $miraviaProductId)->first();

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        return response()->json(['product_type' => $product->type]);
    }

    /**
     * Get the SKUs for the given Miravia product ID.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProductSkus(Request $request)
    {
        $miraviaProductId = $request->input('miravia_product_id');

        if (!$miraviaProductId) {
            return response()->json(['error' => 'Miravia product ID is required'], 400);
        }

        $product = MiraviaProduct::where('miravia_id', $miraviaProductId);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Assuming there's a relationship between MiraviaProduct and SKUs
        $skus = $product->pluck('sku_id', 'name');

        return response()->json($skus);
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

        // $notAllowed = $this->miraviaUtil->notAllowedInDemo();
        // if (! empty($notAllowed)) {
        //     return $notAllowed;
        // }

        try {
            DB::beginTransaction();
            $user_id = request()->session()->get('user.id');

            $this->miraviaUtil->syncCategories($business_id, $user_id);

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
        $notAllowed = $this->miraviaUtil->notAllowedInDemo();
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
            $all_products = $this->miraviaUtil->syncProducts($business_id, $user_id, $sync_type, $limit, $offset);
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
        $notAllowed = $this->miraviaUtil->notAllowedInDemo();
        if (!empty($notAllowed)) {
            return $notAllowed;
        }

        $business_id = request()->session()->get('business.id');
        if (!(auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'woocommerce_module') && auth()->user()->can('woocommerce.sync_orders')))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();
            $user_id = request()->session()->get('user.id');

            $this->miraviaUtil->syncOrders($business_id, $user_id);

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
     * Retrives sync log
     *
     * @return Response
     */
    public function getSyncLog()
    {
        $notAllowed = $this->miraviaUtil->notAllowedInDemo();
        if (!empty($notAllowed)) {
            return $notAllowed;
        }

        $business_id = request()->session()->get('business.id');
        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'woocommerce_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $last_sync = [
                'categories' => $this->miraviaUtil->getLastSync($business_id, 'categories'),
                'new_products' => $this->miraviaUtil->getLastSync($business_id, 'new_products'),
                'all_products' => $this->miraviaUtil->getLastSync($business_id, 'all_products'),
                'orders' => $this->miraviaUtil->getLastSync($business_id, 'orders'),

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
        $notAllowed = $this->miraviaUtil->notAllowedInDemo();
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
                    ->update(['miravia_tax_rate_id' => $value]);
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
            $logs = MiraviaSyncLog::where('miravia_sync_logs.business_id', $business_id)
                ->leftjoin('users as U', 'U.id', '=', 'miravia_sync_logs.created_by')
                ->select([
                    'miravia_sync_logs.created_at',
                    'sync_type', 'operation_type',
                    DB::raw("CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) as full_name"),
                    'miravia_sync_logs.data',
                    'miravia_sync_logs.details as log_details',
                    'miravia_sync_logs.id as DT_RowId',
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
            //dd($logs->get());
            return Datatables::of($logs)
                ->editColumn('created_at', function ($row) {
                    $created_at = $this->miraviaUtil->format_date($row->created_at, true);
                    $for_humans = \Carbon::createFromFormat('Y-m-d H:i:s', $row->created_at)->diffForHumans();
                    return $created_at . '<br><small>' . $for_humans . '</small>';
                })
                ->editColumn('sync_type', function ($row) {
                    $array = [
                        'categories' => __('category.categories'),
                        'all_products' => __('sale.products'),
                        'products' => __('sale.products'),
                        'new_products' => __('sale.products'),
                        'orders' => __('woocommerce::lang.orders'),
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
                        $data = json_decode($row->data, true);
                        return implode(', ', $data) . '<br><small>' . count($data) . ' ' . __('woocommerce::lang.records') . '</small>';
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

        return view('miravia.sync_log');
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
            $log = MiraviaSyncLog::where('business_id', $business_id)
                ->find($id);
            $log_details = json_decode($log->details);

            return view('miravia.partials.log_details')
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
                    ->update(['miravia_cat_id' => null]);
                $user_id = request()->session()->get('user.id');
                $this->miraviaUtil->createSyncLog($business_id, $user_id, 'categories', 'reset', null);

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
                    ->update(['miravia_product_id' => null, 'miravia_media_id' => null]);

                $product_ids = Product::where('business_id', $business_id)
                    ->pluck('id');

                $product_ids = !empty($product_ids) ? $product_ids : [];
                //Update variations table
                Variation::whereIn('product_id', $product_ids)
                    ->update([
                        'miravia_variation_id' => null,
                    ]);

                //Update variation templates
                VariationTemplate::where('business_id', $business_id)
                    ->update([
                        'miravia_attr_id' => null,
                    ]);

                Media::where('business_id', $business_id)
                    ->update(['miravia_media_id' => null]);

                $user_id = request()->session()->get('user.id');
                $this->miraviaUtil->createSyncLog($business_id, $user_id, 'all_products', 'reset', null);

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
