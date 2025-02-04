<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action([\App\Http\Controllers\ComponentController::class, 'store']), 'method' => 'post', 'id' => $quick_add ? 'quick_add_component_form' : 'component_add_form' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'component.add_component' )</h4>
    </div>

    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('title', __( 'component.component_name' ) . ':*') !!}
          {!! Form::text('title', null, ['class' => 'form-control', 'required', 'placeholder' => __( 'component.component_name' ) ]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('stock', __( 'component.stock' ) . ':') !!}
          {!! Form::text('stock', null, ['class' => 'form-control','placeholder' => __( 'component.stock' )]); !!}
      </div>

        @if($is_repair_installed)
          <div class="form-group">
             <label>
                {!!Form::checkbox('use_for_repair', 1, false, ['class' => 'input-icheck']) !!}
                {{ __( 'repair::lang.use_for_repair' )}}
            </label>
            @show_tooltip(__('repair::lang.use_for_repair_help_text'))
          </div>
        @endif

    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->