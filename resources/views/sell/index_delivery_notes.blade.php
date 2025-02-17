@extends('layouts.app')
@section('title', __( 'lang_v1.all_sales'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>@lang( 'Delivery Notes' )

        <button class="btn btn-primary" data-toggle="modal" data-target="#generateinvoices" style="float: right;margin-right:20px;">Generate Bulk Delivery Notes</button>

    </h1>

</section>

<!-- Modal -->


<div class="modal fade" id="generatedelivery_note" tabindex="-1" role="dialog" aria-labelledby="generatedelivery_noteLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="generatedelivery_noteLabel">Generate Deliery Notes By:</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form action="{{route('sell.downloadBulkInvoicesPdf')}}" method="POST">
            @csrf
            <input type="hidden" name="report_type" value="delivery_note"> 
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="filter_location">Business Location:</label>
                        <select class="form-control select2" id="filter_location" style="width: 100%;" name="filter_location">
                            <option selected="selected" value="">All</option>
                            <option value="1">MANUFACTURAS TORRERO (BL001)</option>
                            <option value="2">MYPA INYECCIONES (BL0002)</option>
                            <option value="3">FORMULATWOIT S.L. (BL0004)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="filter_date_range">Date Range:</label>
                        <input placeholder="Select a date range" class="form-control" readonly="" name="filter_date_range" type="text" id="filter_date_range">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="filter_source">Sources:</label>
                        <select class="form-control select2"  id="filter_source"  style="width: 100%;" name="filter_source" >
                            <option selected="selected" value="">All</option>
                            <option value="29psi">29psi</option>
                            <option value="Amazon">Amazon</option>
                            <option value="Decathlon">Decathlon</option>
                            <option value="HeadPressurizer">HeadPressurizer</option>
                            <option value="Miravia">Miravia</option>
                            <option value="TuboPlus">TuboPlus</option>
                        </select>
                    </div>
                </div>
            </div>
            <span id="loader" style="display: none;vertical-align:middle;">
                <i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i>
                <span class="sr-only">Loading...</span>
            </span>
            <button type="submit" id="runCommandBtn" class="btn btn-primary">Generate Delivery Notes</button>
        </form>
      </div>
    </div>
  </div>
</div>



<!-- Main content -->
<section class="content no-print">
    @component('components.filters', ['title' => __('report.filters')])
        @include('sell.partials.sell_list_filters')
        @if(!empty($sources))
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('sell_list_filter_source',  __('lang_v1.sources') . ':') !!}

                    {!! Form::select('sell_list_filter_source', $sources, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all') ]); !!}
                </div>
            </div>
        @endif
    @endcomponent
    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'List Delivery Notes')])
        @can('direct_sell.access')
            @slot('tool')
                <div class="box-tools">
                    <a class="btn btn-block btn-primary" href="{{action([\App\Http\Controllers\SellController::class, 'create'],['status'=>'delivery_note'])}}">
                    <i class="fa fa-plus"></i> @lang('messages.add')</a>
                </div>
            @endslot
        @endcan

        @if(session()->has('error'))
            <div class="alert alert-danger alert-dismisable">
                <p>{{session()->get('error')}}</p>
            </div>
            {{session()->forget('error')}}
        @endif

        @if(auth()->user()->can('direct_sell.view') ||  auth()->user()->can('view_own_sell_only') ||  auth()->user()->can('view_commission_agent_sell'))
        @php
            $custom_labels = json_decode(session('business.custom_labels'), true);
         @endphp
            <table class="table table-bordered table-striped ajax_view" id="sell_table">
                <thead>
                    <tr>
                        <th>@lang('messages.action')</th>
                        <th>@lang('messages.date')</th>
                        <th>@lang('Delivery Note')</th>
                        <th>@lang('Source')</th>
                        <th>@lang('sale.customer_name')</th>
                        <th>@lang('lang_v1.contact_no')</th>
                        <th>@lang('Warehouse')</th>
                        <th>@lang('Localitation')</th>
                        <th>@lang('sale.payment_status')</th>
                        <th>@lang('lang_v1.payment_method')</th>
                        <th>@lang('sale.total_amount')</th>
                        <th>@lang('sale.total_paid')</th>
                        <th>@lang('lang_v1.sell_due')</th>
                        <th>@lang('lang_v1.sell_return_due')</th>
                        <th>@lang('lang_v1.shipping_status')</th>
                        <th>@lang('lang_v1.total_items')</th>
                        <th>@lang('lang_v1.types_of_service')</th>
                        <th>{{ $custom_labels['types_of_service']['custom_field_1'] ?? __('lang_v1.service_custom_field_1' )}}</th>
                        <th>{{ $custom_labels['sell']['custom_field_1'] ?? '' }}</th>
                        <th>{{ $custom_labels['sell']['custom_field_2'] ?? ''}}</th>
                        <th>{{ $custom_labels['sell']['custom_field_3'] ?? ''}}</th>
                        <th>{{ $custom_labels['sell']['custom_field_4'] ?? ''}}</th>
                        <th>@lang('lang_v1.added_by')</th>
                        <th>@lang('sale.sell_note')</th>
                        <th>@lang('sale.staff_note')</th>
                        <th>@lang('sale.shipping_details')</th>
                        <th>@lang('restaurant.table')</th>
                        <th>@lang('restaurant.service_staff')</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr class="bg-gray font-17 footer-total text-center">
                        <td colspan="6"><strong>@lang('sale.total'):</strong></td>
                        <td class="footer_payment_status_count"></td>
                        <td class="payment_method_count"></td>
                        <td class="footer_sale_total"></td>
                        <td class="footer_total_paid"></td>
                        <td class="footer_total_remaining"></td>
                        <td class="footer_total_sell_return_due"></td>
                        <td colspan="2"></td>
                        <td class="service_type_count"></td>
                        <td colspan="7"></td>
                    </tr>
                </tfoot>
            </table>
        @endif
    @endcomponent
</section>
<!-- /.content -->
<div class="modal fade payment_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

<div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

<!-- This will be printed -->
<!-- <section class="invoice print_section" id="receipt_section">
</section> -->

@stop

@section('javascript')
<script type="text/javascript">
$(document).ready( function(){
    //Date range as a button
    $('input[name="filter_date_range"]').daterangepicker(
        dateRangeSettings,
        function (start, end) {
            $('input[name="filter_date_range"]').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            sell_table.ajax.reload();
        }
    );
    $('#sell_list_filter_date_range').daterangepicker(
        dateRangeSettings,
        function (start, end) {
            $('#sell_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            sell_table.ajax.reload();
        }
    );
    $('#sell_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
        $('#sell_list_filter_date_range').val('');
        sell_table.ajax.reload();
    });

    sell_table = $('#sell_table').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[1, 'desc']],
        "ajax": {
            "url": "/sells?list=delivery_notes",
            "data": function ( d ) {
                if($('#sell_list_filter_date_range').val()) {
                    var start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                    var end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                    d.start_date = start;
                    d.end_date = end;
                }
                d.is_direct_sale = 1;

                d.location_id = $('#sell_list_filter_location_id').val();
                d.customer_id = $('#sell_list_filter_customer_id').val();
                d.payment_status = $('#sell_list_filter_payment_status').val();
                d.created_by = $('#created_by').val();
                d.sales_cmsn_agnt = $('#sales_cmsn_agnt').val();
                d.service_staffs = $('#service_staffs').val();

                if($('#shipping_status').length) {
                    d.shipping_status = $('#shipping_status').val();
                }

                if($('#sell_list_filter_source').length) {
                    d.source = $('#sell_list_filter_source').val();
                }

                if($('#only_subscriptions').is(':checked')) {
                    d.only_subscriptions = 1;
                }

                d = __datatable_ajax_callback(d);
            }
        },
        scrollY:        "75vh",
        scrollX:        true,
        scrollCollapse: true,
        columns: [
            { data: 'action', name: 'action', orderable: false, "searchable": false},
            { data: 'transaction_date', name: 'transaction_date'  },
            { data: 'delivery_note_number', name: 'delivery_note_number'},
            { data: 'source', name: 'source'},
            { data: 'conatct_name', name: 'conatct_name'},
            { data: 'mobile', name: 'contacts.mobile'},
            { data: 'business_location', name: 'bl.name'},
            { data: 'main_location', name: 'main_location'},
            { data: 'payment_status', name: 'payment_status'},
            { data: 'payment_methods', orderable: false, "searchable": false},
            { data: 'final_total', name: 'final_total'},
            { data: 'total_paid', name: 'total_paid', "searchable": false},
            { data: 'total_remaining', name: 'total_remaining'},
            { data: 'return_due', orderable: false, "searchable": false},
            { data: 'shipping_status', name: 'shipping_status'},
            { data: 'total_items', name: 'total_items', "searchable": false},
            { data: 'types_of_service_name', name: 'tos.name', @if(empty($is_types_service_enabled)) visible: false @endif},
            { data: 'service_custom_field_1', name: 'service_custom_field_1', @if(empty($is_types_service_enabled)) visible: false @endif},
            { data: 'custom_field_1', name: 'transactions.custom_field_1', @if(empty($custom_labels['sell']['custom_field_1'])) visible: false @endif},
            { data: 'custom_field_2', name: 'transactions.custom_field_2', @if(empty($custom_labels['sell']['custom_field_2'])) visible: false @endif},
            { data: 'custom_field_3', name: 'transactions.custom_field_3', @if(empty($custom_labels['sell']['custom_field_3'])) visible: false @endif},
            { data: 'custom_field_4', name: 'transactions.custom_field_4', @if(empty($custom_labels['sell']['custom_field_4'])) visible: false @endif},
            { data: 'added_by', name: 'u.first_name'},
            { data: 'additional_notes', name: 'additional_notes'},
            { data: 'staff_note', name: 'staff_note'},
            { data: 'shipping_details', name: 'shipping_details'},
            { data: 'table_name', name: 'tables.name', @if(empty($is_tables_enabled)) visible: false @endif },
            { data: 'waiter', name: 'ss.first_name', @if(empty($is_service_staff_enabled)) visible: false @endif },
        ],
        "fnDrawCallback": function (oSettings) {
            __currency_convert_recursively($('#sell_table'));
        },
        "footerCallback": function ( row, data, start, end, display ) {
            var footer_sale_total = 0;
            var footer_total_paid = 0;
            var footer_total_remaining = 0;
            var footer_total_sell_return_due = 0;
            for (var r in data){
                footer_sale_total += $(data[r].final_total).data('orig-value') ? parseFloat($(data[r].final_total).data('orig-value')) : 0;
                footer_total_paid += $(data[r].total_paid).data('orig-value') ? parseFloat($(data[r].total_paid).data('orig-value')) : 0;
                footer_total_remaining += $(data[r].total_remaining).data('orig-value') ? parseFloat($(data[r].total_remaining).data('orig-value')) : 0;
                footer_total_sell_return_due += $(data[r].return_due).find('.sell_return_due').data('orig-value') ? parseFloat($(data[r].return_due).find('.sell_return_due').data('orig-value')) : 0;
            }

            $('.footer_total_sell_return_due').html(__currency_trans_from_en(footer_total_sell_return_due));
            $('.footer_total_remaining').html(__currency_trans_from_en(footer_total_remaining));
            $('.footer_total_paid').html(__currency_trans_from_en(footer_total_paid));
            $('.footer_sale_total').html(__currency_trans_from_en(footer_sale_total));

            $('.footer_payment_status_count').html(__count_status(data, 'payment_status'));
            $('.service_type_count').html(__count_status(data, 'types_of_service_name'));
            $('.payment_method_count').html(__count_status(data, 'payment_methods'));
        },
        createdRow: function( row, data, dataIndex ) {
            $( row ).find('td:eq(6)').attr('class', 'clickable_td');
        }
    });

    $(document).on('change', '#sell_list_filter_location_id, #sell_list_filter_customer_id, #sell_list_filter_payment_status, #created_by, #sales_cmsn_agnt, #service_staffs, #shipping_status, #sell_list_filter_source',  function() {
        sell_table.ajax.reload();
    });

    $('#only_subscriptions').on('ifChanged', function(event){
        sell_table.ajax.reload();
    });

    $('#generateLabelDatePicker').daterangepicker({
        singleDatePicker: true,
        locale: {
           format: 'YYYY-MM-DD'
        }   
    });


    $('#runCommandBtn').on('click', function() {
        $('#runCommandBtnGLS').prop('disabled',true);
        $('#runCommandBtn').prop('disabled',true);
        $('#loader').show();
        var date = $("#generateLabelDatePicker").val();
        $.ajax({
            url: '{{ route('run.generateshipping.command') }}',
            method: 'POST',
            data: {
                'type': 'fedex',
                'date': date,
                _token: '{{ csrf_token() }}'  // Laravel CSRF protection
            },
            success: function(response) {
                $('#runCommandBtnGLS').prop('disabled',false);
                $('#runCommandBtn').prop('disabled',false);
                $('#loader').hide();
                var resp = JSON.parse(response);
                console.log(resp);
                if (resp.msg) {
                    alert(resp.msg);
                }
                if(resp.url){
                    window.location.href=resp.url;
                }
            },
            error: function() {
                alert('Failed to run the command.');
            }
        });
    });

    $('#runCommandBtnGLS').on('click', function() {
        $('#runCommandBtnGLS').prop('disabled',true);
        $('#runCommandBtn').prop('disabled',true);
        $('#loader').show();
        var date = $("#generateLabelDatePicker").val();
        $.ajax({
            url: '{{ route('run.generateshipping.command') }}',
            method: 'POST',
            data: {
                'type': 'gls',
                'date': date,
                _token: '{{ csrf_token() }}'  // Laravel CSRF protection
            },
            success: function(response) {
                $('#runCommandBtnGLS').prop('disabled',false);
                $('#runCommandBtn').prop('disabled',false);
                $('#loader').hide();
                var resp = JSON.parse(response);
                console.log(resp);
                if (resp.msg) {
                    alert(resp.msg);
                }
                if(resp.url){
                    window.location.href=resp.url;
                }
            },
            error: function() {
                alert('Failed to run the command.');
            }
        });
    });




    






});
</script>
<script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
@endsection