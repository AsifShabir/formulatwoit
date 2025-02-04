
<div class="col-sm-12">
<h4>@lang('product.add_variation'):* <button type="button" class="btn btn-primary" id="add_variation" data-action="add" data-product_id="">+</button></h4>
</div>
<div class="col-sm-12">
    <div class="table-responsive">
    <table class="table table-bordered add-product-price-table table-condensed" id="product_variation_form_part">
        <thead>
          <tr>
            <th class="col-sm-2">@lang('lang_v1.variation')</th>
            <th class="col-sm-10">@lang('product.variation_values')</th>
          </tr>
        </thead>
        <tbody>
            @if($action == 'add')
                @include('product.partials.product_variation_row', ['row_index' => 0,'amazon_variations' => $amazon_variations, 'miravia_variations' => $miravia_variations, 'woo_variations' => $woo_variations])
            @else

                @forelse ($product_variations as $product_variation)
                    @include('product.partials.edit_product_variation_row', ['row_index' => $action == 'edit' ? $product_variation->id : $loop->index, 'amazon_variations' => $amazon_variations, 'miravia_variations' => $miravia_variations, 'woo_variations' => $woo_variations])
                @empty
                    
                    @include('product.partials.product_variation_row', ['row_index' => 0, 'amazon_variations' => $amazon_variations, 'miravia_variations' => $miravia_variations, 'woo_variations' => $woo_variations])
                @endforelse

            @endif
            
        </tbody>
    </table>
    </div>
</div>