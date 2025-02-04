<?php

namespace App\Console\Commands;

use App\Business;
use App\BusinessLocation;
use App\Notifications\LowStockNotification;
use App\Product;
use App\PurchaseLine;
use App\Transaction;
use Illuminate\Console\Command;
use Notification;

class LowStockReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:lowstockreminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email reminder to wearhouses on low stock';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $business_id = 1;

        $business = Business::findOrFail($business_id);
        $owner_id = $business->owner_id;

        //Set timezone to business timezone
        $timezone = $business->time_zone;
        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);

        $products = Product::all();
        foreach($products as $product){
            $product_id = $product->id;
            $transaction = Transaction::where('business_id', $business_id)
                                ->where('opening_stock_product_id', $product_id)
                                ->where('type', 'opening_stock')
                                ->with(['purchase_lines'])
                                ->latest()->first();
            if($transaction){
                $latest_purchase_line = $transaction->purchase_lines()->latest()->first();
                $location = $transaction->location()->first();

                $quantity = $this->num_uf(trim($latest_purchase_line->quantity));
                $sold = $this->num_uf(trim($latest_purchase_line->quantity_sold));
                $remaining_quantity = $quantity - $sold;
                $alert_quantity = $this->num_uf(trim($product->alert_quantity));
                if($product->enable_stock && $remaining_quantity <= $alert_quantity){
                    $this->sendEmailReminder($product, $business,$location,$remaining_quantity);
                }
            }
        }

        return Command::SUCCESS;
    }

    public function sendEmailReminder($product, $business, $location,$remaining_quantity){
        $data['email_settings'] = $business->email_settings;
        $data['email_body'] = '<p>Dear,</p>

                    <p>Following product is low in stock at '.$location->name.'<br />
                    Product: '.$product->name.'<br />
                    Remaining Quantity: '.$remaining_quantity.'</p>

                    <p>Thank you.</p>

                    <p>'.$business->name.'</p>

                    <p>&nbsp;</p>';
        $data['subject'] = "Low Stock Reminder";
        $data['to_email'] = $business->stock_reminder_email ??"pedidosinyeccion@mypainyeccion.com";
        try{
            Notification::route('mail', $data['to_email'])
                        ->notify(new LowStockNotification($data));
        }catch(\Exception $e){
            return $e->getMessage();
        }
    }

    public function num_uf($input_number, $currency_details = null)
    {
        $thousand_separator = '';
        $decimal_separator = '';

        if (! empty($currency_details)) {
            $thousand_separator = $currency_details->thousand_separator;
            $decimal_separator = $currency_details->decimal_separator;
        } else {
            $thousand_separator = session()->has('currency') ? session('currency')['thousand_separator'] : '';
            $decimal_separator = session()->has('currency') ? session('currency')['decimal_separator'] : '';
        }

        $num = str_replace($thousand_separator, '', $input_number);
        $num = str_replace($decimal_separator, '.', $num);

        return (float) $num;
    }

    
}