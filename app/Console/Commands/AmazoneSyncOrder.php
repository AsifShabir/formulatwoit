<?php

namespace App\Console\Commands;

use App\Business;
use DB;
use Illuminate\Console\Command;
use Modules\Woocommerce\Notifications\SyncOrdersNotification;
use App\Utils\AmazonUtil;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class AmazoneSyncOrder extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'pos:AmazonSyncOrder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs all orders from Amazon to POS';

    /**
     * All Utils instance.
     */
    protected $amazonUtil;

    /**
     * Create a new command instance.
     *
     * @param  AmazonUtil  $amazonUtil
     * @return void
     */
    public function __construct(AmazonUtil $amazonUtil)
    {
        parent::__construct();

        $this->amazonUtil = $amazonUtil;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            DB::beginTransaction();
            $business_id = 1;

            $business = Business::findOrFail($business_id);
            $owner_id = $business->owner_id;

            //Set timezone to business timezone
            $timezone = $business->time_zone;
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);

            $this->amazonUtil->syncOrders($business_id, $owner_id);

            $this->notify($owner_id);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            print_r('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
        }
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
