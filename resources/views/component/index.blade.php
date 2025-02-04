@extends('layouts.app')
@section('title', 'Components')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang( 'component.components' )
        <small>@lang( 'component.manage_your_components' )</small>
    </h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'component.all_your_components' )])
        @can('component.create')
            @slot('tool')
                <div class="box-tools">
                    <button type="button" class="btn btn-block btn-primary btn-modal" 
                        data-href="{{action([\App\Http\Controllers\ComponentController::class, 'create'])}}" 
                        data-container=".components_modal">
                        <i class="fa fa-plus"></i> @lang( 'messages.add' )</button>
                </div>
            @endslot
        @endcan
        @can('component.view')
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="components_table">
                    <thead>
                        <tr>
                            <th>@lang( 'component.components' )</th>
                            <th>@lang( 'component.stock' )</th>
                            <th>@lang( 'messages.action' )</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endcan
    @endcomponent

    <div class="modal fade components_modal" tabindex="-1" role="dialog" 
    	aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->

@endsection
