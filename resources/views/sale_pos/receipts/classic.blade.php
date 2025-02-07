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
<!-- business information here -->
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
									<b>{!! $receipt_details->invoice_heading !!}</b>
								</h3>
							</td>
						</tr>
						<tr><td align="right">Date of Invoice: {{date("m/d/Y",strtotime($receipt_details->invoice_date))}}</td></tr>
						@if (!empty($receipt_details->due_date))
						<tr><td align="right">Fecha de vencimiento: {{$receipt_details->due_date ?? ''}}</td></tr>
						@endif
						<tr><td align="right">Number of Invoice: {{$receipt_details->invoice_no}}</td></tr>
				</tbody></table>
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
							<th>Description </th>
							<th>Quantity</th>
							<th>Price</th>
							<th>IVA %</th>
							<th>Total</th>
						</tr>
						@forelse($receipt_details->lines as $line)
						<tr>
							<td>
								@if(!empty($line['image']))
									<img src="{{$line['image']}}" alt="Image" width="50" style="float: left; margin-right: 8px;">
								@endif
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
							<td>{{$line['unit_price_before_discount']}}</td>
							<td>21.00</td>
							<td>{{$line['unit_price_inc_tax']}}</td>
						</tr>
						@empty
						<tr>
							<td colspan="5">&nbsp;</td>
							@if(!empty($receipt_details->discounted_unit_price_label))
							<td></td>
							@endif
							@if(!empty($receipt_details->item_discount_label))
							<td></td>
							@endif
						</tr>
						@endforelse
					</tbody>
				</table>
			</td>
		</tr>
		
		<!-- Totals -->
		<tr>
			<td colspan="2">
				<table width="300" align="right">
					<tbody><tr>
						<td>Sub Total</td>
						<td align="right">{{$receipt_details->subtotal}}</td>
					</tr>
					<tr>
						<td>Shipping</td>
						<td align="right">{{$receipt_details->shipping_charges}}</td>
					</tr>
					<tr>
						<td>VAT</td>
						<td align="right">{{$receipt_details->tax}}</td>
					</tr>
					<tr class="total-row">
						<td>Total</td>
						<td align="right">{{$receipt_details->total}}</td>
					</tr>
				</tbody></table>
			</td>
		</tr>
		
		<!-- Footer -->
		<tr>
			<td colspan="2" height="40"></td>
		</tr>
		<tr>
			<td colspan="2">
				<table>
					<tbody>
						<tr>
							<td class="footer">
								FORMULATWOIT S.L.<br>
								VAT: B86840451<br>
								IBAN: ES60 2100 1676 8102 0026 9108
 								BIC SWIFT CAIXESBBXXX
							</td>
							<td class="footer">
								The products are the intellectual property and
								patents of the company FORMULATWOIT S,L.
								In Spain plagiarism is penalized by law, in
								articles 270 and 272 of the Penal Code.
							</td>
							<td class="footer">
								No CLAIMS after 2 years
								of the useful life of the product. Destruction without
								authorization of FORMULATWOIT S,L will be
								buyer's charge. Contact: info@tubo.plu
							</td>
						</tr>
					</tbody>
				</table>
			</td>
		</tr>
	</tbody>
</table>

<div class="row" style="color: #000000 !important;">
	<div class="col-md-12"><hr/></div>
	@if(!empty($receipt_details->additional_notes))
	    <div class="col-xs-12">
	    	<p>{!! nl2br($receipt_details->additional_notes) !!}</p>
	    </div>
    @endif
</div>
<div class="row" style="color: #000000 !important;">
	@if(!empty($receipt_details->footer_text))
	<div class="@if($receipt_details->show_barcode || $receipt_details->show_qr_code) col-xs-8 @else col-xs-12 @endif">
		{!! $receipt_details->footer_text !!}
	</div>
	@endif
	@if($receipt_details->show_barcode || $receipt_details->show_qr_code)
		<div class="@if(!empty($receipt_details->footer_text)) col-xs-4 @else col-xs-12 @endif text-center">
			@if($receipt_details->show_barcode)
				{{-- Barcode --}}
				<img class="center-block" src="data:image/png;base64,{{DNS1D::getBarcodePNG($receipt_details->invoice_no, 'C128', 2,30,array(39, 48, 54), true)}}">
			@endif
			
			@if($receipt_details->show_qr_code && !empty($receipt_details->qr_code_text))
				<img class="center-block mt-5" src="data:image/png;base64,{{DNS2D::getBarcodePNG($receipt_details->qr_code_text, 'QRCODE', 3, 3, [39, 48, 54])}}">
			@endif
		</div>
	@endif
</div>