<div class="pos-tab-content">
    <div class="row">
    	<div class="col-xs-4">
            <div class="form-group">
            	{!! Form::label('miravia_app_key',  __('App Key') . ':') !!}
            	{!! Form::text('miravia_app_key', $default_settings['miravia_app_key'], ['class' => 'form-control','placeholder' => __('App Key')]); !!}
            </div>
        </div>
        <div class="col-xs-4">
            <div class="form-group">
                {!! Form::label('miravia_app_secret',  __('App Secret') . ':') !!}
                {!! Form::text('miravia_app_secret', $default_settings['miravia_app_secret'], ['class' => 'form-control','placeholder' => __('App Secret')]); !!}
            </div>
        </div>
        <div class="col-xs-4">
            <div class="form-group">
            	{!! Form::label('miravia_access_token', __('App Access Token') . ':') !!}
                <input type="password" name="miravia_access_token" value="{{$default_settings['miravia_access_token']}}" id="miravia_access_token" class="form-control">
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-xs-4">
            <div class="form-group">
                {!! Form::label('location_id',  __('business.business_locations') . ':') !!} @show_tooltip(__('woocommerce::lang.location_dropdown_help'))
                {!! Form::select('location_id', $locations, $default_settings['location_id'], ['class' => 'form-control']); !!}
            </div>
        </div>
        <div class="col-xs-4">
            <div class="checkbox">
                <label>
                    <br/>
                    {!! Form::checkbox('enable_auto_sync', 1, !empty($default_settings['enable_auto_sync']), ['class' => 'input-icheck'] ); !!} @lang('woocommerce::lang.enable_auto_sync')
                </label>
                @show_tooltip(__('woocommerce::lang.auto_sync_tooltip'))
            </div>
        </div>
    </div>
</div>