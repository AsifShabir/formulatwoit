<div class="pos-tab-content">
    <div class="row">
    	<div class="col-xs-4">
            <div class="form-group">
            	{!! Form::label('client_id',  __('Client ID') . ':') !!}
            	{!! Form::text('client_id', $default_settings['client_id'], ['class' => 'form-control','placeholder' => __('Client ID')]); !!}
            </div>
        </div>
        <div class="col-xs-4">
            <div class="form-group">
                {!! Form::label('client_secret',  __('Client Secret') . ':') !!}
                {!! Form::text('client_secret', $default_settings['client_secret'], ['class' => 'form-control','placeholder' => __('Client Secret')]); !!}
            </div>
        </div>
        <div class="col-xs-4">
            <div class="form-group">
            	{!! Form::label('refresh_token', __('Refresh Token') . ':') !!}
                <input type="text" name="refresh_token" value="{{$default_settings['refresh_token']}}" id="refresh_token" class="form-control">
            </div>
        </div>
        <div class="col-xs-4">
            <div class="form-group">
            	{!! Form::label('markete_places', __('Markete Places') . ':') !!}
                <input type="text" name="markete_places" value="{{$default_settings['markete_places']}}" id="markete_places" class="form-control">
                <span>comma seperated</span>
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