<?php

namespace App\Console\Commands;

use App\Business;
use App\Transaction;
use App\WoocommerceStoreCredential;
use DB;
use Illuminate\Console\Command;
use Modules\Woocommerce\Notifications\SyncOrdersNotification;
use Modules\Woocommerce\Utils\WoocommerceAllUtilCron;
use Modules\Woocommerce\Utils\WoocommerceUtilCron;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class WoocommerceSyncOrder extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'pos:WoocommerceSyncOrder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs all orders from all woocommerce stores to POS';

    /**
     * All Utils instance.
     */
    protected $woocommerceUtil;

    /**
     * Create a new command instance.
     *
     * @param  AmazonUtil  $amazonUtil
     * @return void
     */
    public function __construct(WoocommerceAllUtilCron $woocommerceUtil)
    {
        parent::__construct();

        $this->woocommerceUtil = $woocommerceUtil;
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
        try {
            //DB::beginTransaction();
            $business_id = 1;

            $business = Business::findOrFail($business_id);
            $owner_id = $business->owner_id;

            $stores = WoocommerceStoreCredential::all();

            //Set timezone to business timezone
            $timezone = $business->time_zone;
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);

            $all_orders = [];
            $last_synced = $this->woocommerceUtil->getLastSync($business_id, 'orders', false);
            foreach($stores as $store){
                $store_id = $store->id; 
                $orders = $this->woocommerceUtil->getAllResponse($store_id, 'orders');
                //$all_orders[$store->id.':'.$store->app_name] = $orders;
                $all_orders = array_merge($orders,$all_orders);
            }
            // Sort the array by date_created in ascending order
            usort($all_orders, function ($a, $b) {
                return strtotime($a->date_created) <=> strtotime($b->date_created);
            });

            //dd($all_orders);

            $woocommerce_sells = Transaction::where('business_id', $business_id)
                                ->whereNotNull('woocommerce_order_id')
                                ->with('sell_lines', 'sell_lines.product', 'payment_lines')
                                ->get();

            $business_data = [
                'id' => $business->id,
                'accounting_method' => $business->accounting_method,
                'location_id' => $location_id ?? 1,
                'pos_settings' => json_decode($business->pos_settings, true),
                'business' => $business,
            ];

            $created_data = [];
            $updated_data = [];
            $create_error_data = [];
            $update_error_data = [];
            $user_id = 1;
            //dd($orders);
            foreach ($all_orders as $order) {
                
                $store_id = $this->getStoreId($order->payment_url);
                //Only consider orders modified after last sync
                if ((!empty($last_synced) && strtotime($order->date_modified) <= strtotime($last_synced)) || in_array($order->status, ['auto-draft'])) {
                    continue;
                }

                if ($order->status !== 'completed' && $order->status !== 'processing') {
                    continue;
                }

                //Search if order already exists
                $sell = $woocommerce_sells->filter(function ($item) use ($order) {
                    return $item->woocommerce_order_id == $order->id;
                })->first();
                
                
                $order_number = $order->number;
                $sell_status = $this->woocommerceUtil->woocommerceOrderStatusToPosSellStatus($order->status, $business_id);

                if ($sell_status == 'draft') {
                    $order_number .= ' ('.__('sale.draft').')';
                }
                
                if (empty($sell)) {
                    $created = $this->woocommerceUtil->createNewSaleFromOrder($business_id, $user_id, $order, $business_data, $store_id);
                    $created_data[] = $order_number;

                    if ($created !== true) {
                        $create_error_data[] = $created;
                        break;
                    }
                }

            
            }
            //dd($created_data,$create_error_data);

            //Create log
            if (! empty($created_data)) {
                $this->woocommerceUtil->createSyncLog($business_id, $user_id, 'orders',$store_id, 'created', $created_data, $create_error_data);
            }
            if (! empty($updated_data)) {
                $this->woocommerceUtil->createSyncLog($business_id, $user_id, 'orders',$store_id, 'updated', $updated_data, $update_error_data);
            }

            if (empty($created_data) && empty($updated_data)) {
                $error_data = $create_error_data + $update_error_data;
                $this->woocommerceUtil->createSyncLog($business_id, $user_id, 'orders',$store_id, null, [], $error_data);
            }

            //DB::commit();
        } catch (\Exception $e) {
            //DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            dd($e);
            print_r('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
        }

        $this->info('WooCommerce orders sync successfully');
    }

    public function getStoreId($payment_url){
        if(strpos($payment_url,'29psi') !== false){
            $app_name = '29psi';
        }elseif(strpos($payment_url,'tubo') !== false){
            $app_name = 'TuboPlus';
        }elseif(strpos($payment_url,'headpressurizers') !== false){
            $app_name = 'HeadPressurizer';
        }
        $store = WoocommerceStoreCredential::where('app_name',$app_name)->first();
        if(empty($store)){
            dd($payment_url,$app_name);
        }
        return $store->id;
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
    private function notify($user_id)
    {
        $user = \App\User::find($user_id);

        $user->notify(new SyncOrdersNotification());
    }
}
