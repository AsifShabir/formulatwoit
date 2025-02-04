<div class="pos-tab-content">
    <!-- Multi Stores -->
    @if (count($multi_stores) > 1)
    <!-- 
    <div class="row">
        <div class="col-sm-offset-8 col-sm-4 col-xs-12">
            <div class="form-group">
                <label for="store_id">Multiple Stores:</label>
                <select name="store_id[]" class="form-control" id="store_id">
                    <option value="">Select Name</option>
                    @foreach ($multi_stores as $index => $store)
                    <option value="{{ $index }}" {{ request()->session()->get('store_id') == $index ? 'selected' : '' }}>
                        {{ $store['woocommerce_app_name'] }}
                    </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div> -->

    @endif

    <div class="row">
        @foreach($multi_stores as $key => $store)
        <div class="col-lg-3 col-md-3 col-sm-12">
            <h4>Order Created</h4>
            <div class="form-group">
                <label for="woocommerce_wh_oc_secret">Webhook Secret:</label>
                <input type="text" name="woocommerce_wh_oc_secret[]" value="{{ !empty($store['woocommerce_wh_oc_secret']) ? $store['woocommerce_wh_oc_secret'] : '' }}" class="form-control" placeholder="Webhook Secret">
            </div>

            <div class="form-group">
                <strong>Webhook Delivery URL:</strong>
                <p>{{ action([\Modules\Woocommerce\Http\Controllers\WoocommerceWebhookController::class, 'orderCreated'], ['business_id' => $key]) }}</p>
            </div>
        </div>

        <div class="col-lg-3 col-md-3 col-sm-12">
            <h4>Order Updated</h4>
            <div class="form-group">
                <label for="woocommerce_wh_ou_secret">Webhook Secret:</label>
                <input type="text" name="woocommerce_wh_ou_secret[]" value="{{ !empty($store['woocommerce_wh_ou_secret']) ? $store['woocommerce_wh_ou_secret'] : '' }}" class="form-control" placeholder="Webhook Secret">
            </div>

            <div class="form-group">
                <strong>Webhook Delivery URL:</strong>
                <p>{{ action([\Modules\Woocommerce\Http\Controllers\WoocommerceWebhookController::class, 'orderUpdated'], ['business_id' => $key]) }}</p>
            </div>
        </div>

        <div class="col-lg-3 col-md-3 col-sm-12">
            <h4>Order Deleted</h4>
            <div class="form-group">
                <label for="woocommerce_wh_od_secret">Webhook Secret:</label>
                <input type="text" name="woocommerce_wh_od_secret[]" value="{{ !empty($store['woocommerce_wh_od_secret']) ? $store['woocommerce_wh_od_secret'] : '' }}" class="form-control" placeholder="Webhook Secret">
            </div>

            <div class="form-group">
                <strong>Webhook Delivery URL:</strong>
                <p>{{ action([\Modules\Woocommerce\Http\Controllers\WoocommerceWebhookController::class, 'orderDeleted'], ['business_id' => $key]) }}</p>
            </div>
        </div>

        <div class="col-lg-3 col-md-3 col-sm-12">
            <h4>Order Restored</h4>
            <div class="form-group">
                <label for="woocommerce_wh_or_secret">Webhook Secret:</label>
                <input type="text" name="woocommerce_wh_or_secret[]" value="{{ !empty($store['woocommerce_wh_or_secret']) ? $store['woocommerce_wh_or_secret'] : '' }}" class="form-control" placeholder="Webhook Secret">
            </div>

            <div class="form-group">
                <strong>Webhook Delivery URL:</strong>
                <p>{{ action([\Modules\Woocommerce\Http\Controllers\WoocommerceWebhookController::class, 'orderRestored'], ['business_id' => $key]) }}</p>
            </div>
        </div>
        @endforeach
    </div>


</div>