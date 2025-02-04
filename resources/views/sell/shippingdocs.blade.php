@extends('layouts.app')
@section('title', __( 'Shipping Labels'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>Shipping Labels</h1>
</section>

<!-- Main content -->
<section class="content no-print">
   
    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'lang_v1.all_sales')])
        
        @if(auth()->user()->can('direct_sell.view') ||  auth()->user()->can('view_own_sell_only') ||  auth()->user()->can('view_commission_agent_sell'))
            <table class="table table-bordered table-striped ajax_view" id="sell_table">
                <thead>
                    <tr>
                        <th>url</th>
                        <th>contentType</th>
                        <th>copiesToPrint</th>
                        <th>trackingNumber</th>
                    </tr>
                </thead>
                <tbody>
                    
                    @if(is_array($shipping_docs))
                    @foreach($shipping_docs as $shipping_doc)
                    <tr>
                        <td><a href="{{$shipping_doc->url}}">Download</a></td>
                        <td>{{$shipping_doc->contentType ?? ''}}</td>
                        <td>{{$shipping_doc->copiesToPrint ?? ''}}</td>
                        <td>{{$shipping_doc->trackingNumber ?? ''}}</td>
                    </tr>
                    @endforeach
                    @else
                    <tr>
                        <td><a href="{{$shipping_docs->url}}">Download</a></td>
                        <td>{{$shipping_docs->contentType ?? ''}}</td>
                        <td>{{$shipping_docs->copiesToPrint ?? ''}}</td>
                        <td>{{$shipping_docs->trackingNumber ?? ''}}</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        @endif
    @endcomponent
</section>
@stop
@section('javascript')
<script type="text/javascript">
$(document).ready( function(){

    
});
</script>
@endsection