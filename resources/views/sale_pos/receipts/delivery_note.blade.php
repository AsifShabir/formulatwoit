<style>
	table {
		width: 100%;
		border-collapse: collapse;
	}
	td {
		padding: 5px;
		vertical-align: top;
	}
	.logo {
		font-size: 24px;
		font-weight: bold;
	}
	.items-table td, .items-table th {
		border: 1px solid #ddd;
		padding: 8px;
	}
	.items-table th {
		background-color: #f8f8f8;
	}
	.total-row {
		background-color: #ffedeb;
		font-weight: bold;
	}
	.footer {
		font-size: 12px;
		color: #666;
	}
</style>
<table>
	<!-- Header -->
	<tbody>
		<tr>
			<td width="50%">
				<table>
					<tbody>
						<tr>
							<td class="logo">
								@if(!empty($receipt_details->logo))
								<img style="max-height: 120px; width: auto;" src="{{$receipt_details->logo}}" class="img img-responsive center-block">
								@endif
							</td>
						</tr>
					</tbody>
				</table>
			</td>
			<td width="50%">
				<table align="right">
					<tbody>
						<tr>
							<td align="right">
								<h3 style="color:#FE5B35">
									<b>@lang('lang_v1.delivery_note')</b>
								</h3>
							</td>
						</tr>
						<tr><td align="right">Date of Delivery Note: {{date("m/d/Y",strtotime($receipt_details->invoice_date))}}</td></tr>
						<tr><td align="right">Number of Delivery Note: {{$receipt_details->delivery_note_number}}</td>
						</tr>
					</tbody>
				</table>
			</td>
		</tr>
	
		<!-- Company Info -->
		<tr>
			<td colspan="2" height="20"></td>
		</tr>
		<tr>
			<td>
				<table>
					<tbody><tr><td><strong>FORMULATWOIT S.L.</strong></td></tr>
					<tr><td>AVENIDA DE LOS PEÑASCALES 14,</td></tr>
					<tr><td>TORRELODONES</td></tr>
					<tr><td>MADRID, 28250</td></tr>
					<tr><td>Spain</td></tr>
					<tr><td>NIF: ES B86840451</td></tr>
				</tbody></table>
			</td>
			<td>
				<table>
					<tbody>
						<tr>
							<td>
								<strong>{!! $receipt_details->customer_info !!}</strong>
							</td>
						</tr>
					</tbody>
				</table>
			</td>
		</tr>
	
		<!-- Items Table -->
		<tr>
			<td colspan="2" height="20"></td>
		</tr>
		<tr>
			<td colspan="2">
				<table class="items-table" width="100%">
					<tbody>
						<tr>
							<th>Product </th>
							<th>Quantity</th>
						</tr>
						@forelse($receipt_details->lines as $line)
						<tr>
							<td>
								{{$line['name']}} {{$line['product_variation']}} {{$line['variation']}} 
								@if(!empty($line['sub_sku'])), {{$line['sub_sku']}} @endif @if(!empty($line['brand'])), {{$line['brand']}} @endif @if(!empty($line['cat_code'])), {{$line['cat_code']}}@endif
								@if(!empty($line['product_custom_fields'])), {{$line['product_custom_fields']}} @endif
								@if(!empty($line['product_description']))
									<small>
										{!!$line['product_description']!!}
									</small>
								@endif 
								@if(!empty($line['sell_line_note']))
								<br>
								<small>
									{!!$line['sell_line_note']!!}
								</small>
								@endif 
								@if(!empty($line['lot_number']))<br> {{$line['lot_number_label']}}:  {{$line['lot_number']}} @endif 
								@if(!empty($line['product_expiry'])), {{$line['product_expiry_label']}}:  {{$line['product_expiry']}} @endif

								@if(!empty($line['warranty_name'])) <br><small>{{$line['warranty_name']}} </small>@endif @if(!empty($line['warranty_exp_date'])) <small>- {{@format_date($line['warranty_exp_date'])}} </small>@endif
								@if(!empty($line['warranty_description'])) <small> {{$line['warranty_description'] ?? ''}}</small>@endif

								@if($receipt_details->show_base_unit_details && $line['quantity'] && $line['base_unit_multiplier'] !== 1)
								<br><small>
									1 {{$line['units']}} = {{$line['base_unit_multiplier']}} {{$line['base_unit_name']}} <br>
									{{$line['base_unit_price']}} x {{$line['orig_quantity']}} = {{$line['line_total']}}
								</small>
								@endif
							</td>
							<td>
								{{$line['quantity']}} {{$line['units']}} 

								@if($receipt_details->show_base_unit_details && $line['quantity'] && $line['base_unit_multiplier'] !== 1)
								<br><small>
									{{$line['quantity']}} x {{$line['base_unit_multiplier']}} = {{$line['orig_quantity']}} {{$line['base_unit_name']}}
								</small>
								@endif
							</td>
						</tr>
						@empty
						<tr>
							<td colspan="5">&nbsp;</td>
						</tr>
						@endforelse
					</tbody>
				</table>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<div class="row invoice-info color-555" style="page-break-inside: avoid !important">
					<div class="col-md-6 invoice-col width-50">
						<b class="pull-left">@lang('lang_v1.above_mentioned_items_received_in_good_condition')</b>
					</div>
				</div>
				</br>
				<div class="row invoice-info color-555" style="page-break-inside: avoid !important">
					<div class="col-md-6 invoice-col width-80">
						<b class="pull-left">@lang('lang_v1.received_by') : </b>
					</div>
				</div>
				</br>
				<div class="row invoice-info color-555" style="page-break-inside: avoid !important">
					<div class="col-md-6 invoice-col width-50">
						<b class="pull-left">@lang('lang_v1.date'):</b>
					</div>
				</div>
				</br>
				<div class="row invoice-info color-555" style="page-break-inside: avoid !important">
					<div class="col-md-6 invoice-col width-50">
						<b class="pull-left">@lang('lang_v1.authorized_signatory')</b>
					</div>
				</div>

				{{-- Barcode --}}
				@if($receipt_details->show_barcode)
				<br>
				<div class="row">
						<div class="col-xs-12">
							<img class="center-block" src="data:image/png;base64,{{DNS1D::getBarcodePNG($receipt_details->invoice_no, 'C128', 2,30,array(39, 48, 54), true)}}">
						</div>
				</div>
				@endif
			</td>
		</tr>
		<!-- Footer -->
		<tr>
			<td colspan="2" height="40"></td>
		</tr>
		{{-- <tr>
			<td colspan="2">
				<table>
					<tbody>
						<tr>
							<td class="footer">
								FORMULATWOIT S.L.<br>
								SWIFT: CAIXESBBXXX - IBAN: ES86<br>
								ACCOUNT NUM CAIXABANK: 2100 1978 8102
							</td>
							<td class="footer">
								Los productos son la propiedad intelectual y
								patentes de la empresa FORMULATWOIT S,L.
								En España el plagio está penalizado por ley, en
								los artículos 270 y 272 del Código Penal.
							</td>
							<td class="footer">
								No hay RECLAMACIONES después de 2 años
								de la vida útil del producto. Destrucción sin
								autorización de FORMULATWOIT S,L será a
								cargo del comprador. Contacto: info@tubo.plu
							</td>
						</tr>
					</tbody>
				</table>
			</td>
		</tr> --}}
	</tbody>
</table>
