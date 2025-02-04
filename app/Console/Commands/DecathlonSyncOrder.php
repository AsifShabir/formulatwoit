<?php

namespace App\Console\Commands;

use App\Business;
use App\Utils\DecathlonUtil;
use DB;
use Illuminate\Console\Command;
use Modules\Woocommerce\Notifications\SyncOrdersNotification;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class DecathlonSyncOrder extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'pos:DecathlonSyncOrder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs all orders from DecathlonSyncOrder to POS';

    /**
     * All Utils instance.
     */
    protected $decathlonUtil;

    /**
     * Create a new command instance.
     *
     * @param  DecathlonUtil  $miraviaUtil
     * @return void
     */
    public function __construct(DecathlonUtil $decathlonUtil)
    {
        parent::__construct();

        $this->decathlonUtil = $decathlonUtil;
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
            DB::beginTransaction();
            $business_id = 1;

            $business = Business::findOrFail($business_id);
            $owner_id = $business->owner_id;

            //Set timezone to business timezone
            $timezone = $business->time_zone;
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);

            $this->decathlonUtil->syncOrders($business_id, $owner_id);

            //$this->notify($owner_id);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            dd($e);
            print_r('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
        }

        $this->info('Decathlon orders sync successfully');
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
