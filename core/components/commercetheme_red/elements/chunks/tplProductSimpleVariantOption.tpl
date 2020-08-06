[[+total_products:eq=`1`:then=`
<input type="hidden" name="product" value="[[+id]]">
`:else=`
<option value="[[+id]]" [[+idx:eq=`0`:then=`selected`:else=``]]
  data-sku="[[+sku]]"
  data-stock="[[+stock]]"
  data-image="[[+image]]"
  data-price="[[+price]]"
  data-price-formatted="[[+price_rendered:htmlent]]"
>[[+name]] ([[+price_formatted]])</option>
`]]