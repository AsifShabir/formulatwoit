<div class="pos-tab-content">
    @if(empty($multi_stores))
    <div class="row store-row">
        <div class="col-xs-4">
            <div class="form-group">
                <label for="woocommerce_app_name">WooCommerce App Name:</label>
                <input type="text" name="woocommerce_app_name[]" class="form-control" placeholder="Enter WooCommerce App Name" required>
            </div>
        </div>
        <div class="col-xs-4">
            <div class="form-group">
                <label for="woocommerce_app_url">WooCommerce App URL:</label>
                <input type="text" name="woocommerce_app_url[]" class="form-control" placeholder="Enter WooCommerce App URL" required>
            </div>
        </div>
        <div class="col-xs-4">
            <div class="form-group">
                <label for="woocommerce_consumer_key">WooCommerce Consumer Key:</label>
                <input type="text" name="woocommerce_consumer_key[]" class="form-control" placeholder="Enter WooCommerce Consumer Key" required>
            </div>
        </div>
        <div class="col-xs-3">
            <div class="form-group">
                <label for="woocommerce_consumer_secret">WooCommerce Consumer Secret:</label>
                <input type="password" name="woocommerce_consumer_secret[]" class="form-control" placeholder="Enter WooCommerce Consumer Secret" required>
            </div>
        </div>
        <div class="col-xs-3">
            <div class="form-group">
                <label for="location_id">Business Locations:</label>
                <select name="location_id[]" class="form-control">
                    <option value="">Select Location</option>
                    @foreach($locations as $locationId => $locationName)
                    <option value="{{ $locationId }}">{{ $locationName }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-3">
            <div class="form-group">
                <label> Enable Auto Sync : </label>
                @show_tooltip(__('woocommerce::lang.auto_sync_tooltip'))<br>
                <input type="checkbox" name="enable_auto_sync[]" class="checkbox-input">
            </div>
        </div>
        <div class="col-xs-3">
            <div class="form-group">
                <label> Add API Settings :</label><br>
                <button type="button" class="btn btn-sm btn-success add-store">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        </div>
    </div>
    @else
    @foreach($multi_stores as $index => $row)
    <div class="row store-row">
        <div class="col-xs-4">
            <div class="form-group">
                <label for="woocommerce_app_name">WooCommerce App Name:</label>
                <input type="text" name="woocommerce_app_name[]" class="form-control" placeholder="Enter WooCommerce App Name" value="{{ $row['woocommerce_app_name'] }}">
            </div>
        </div>
        <div class="col-xs-4">
            <div class="form-group">
                <label for="woocommerce_app_url">WooCommerce App URL:</label>
                <input type="text" name="woocommerce_app_url[]" class="form-control" placeholder="Enter WooCommerce App URL" value="{{ $row['woocommerce_app_url'] }}">
            </div>
        </div>
        <div class="col-xs-4">
            <div class="form-group">
                <label for="woocommerce_consumer_key">WooCommerce Consumer Key:</label>
                <input type="text" name="woocommerce_consumer_key[]" class="form-control" placeholder="Enter WooCommerce Consumer Key" value="{{ $row['woocommerce_consumer_key'] }}">
            </div>
        </div>
        <div class="col-xs-3">
            <div class="form-group">
                <label for="woocommerce_consumer_secret">WooCommerce Consumer Secret:</label>
                <input type="password" name="woocommerce_consumer_secret[]" class="form-control" placeholder="Enter WooCommerce Consumer Secret" value="{{ $row['woocommerce_consumer_secret'] }}">
            </div>
        </div>
        <div class="col-xs-3">
            <div class="form-group">
                <label for="location_id">Business Locations:</label>
                <select name="location_id[]" class="form-control">
                    <option value="">Select Location</option>
                    @foreach($locations as $locationId => $locationName)
                    <option value="{{ $locationId }}" @if($row['location_id']==$locationId) selected @endif>{{ $locationName }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-3">
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="enable_auto_sync[]" class="checkbox-input" @if($row['enable_auto_sync']) checked @endif>
                    <span class="checkbox-custom"></span>
                    Enable Auto Sync
                </label>
                @show_tooltip(__('woocommerce::lang.auto_sync_tooltip'))
            </div>
        </div>
        <div class="col-xs-3">
            <div class="form-group">
                <label> Add API Settings :</label><br>
                @if($index == 0)
                <button type="button" class="btn btn-sm btn-success add-store">
                    <i class="fas fa-plus"></i>
                </button>
                @else
                <button type="button" class="btn btn-sm btn-danger remove-store">
                    <i class="fas fa-minus"></i>
                </button>
                @endif
            </div>
        </div>
    </div>
    @endforeach

    @endif
</div>