/*
| -------------------------------------------------------------------
| Track
| -------------------------------------------------------------------
*/
$this->load->library('UpsTrack');
$this->upstrack->addField('trackNumber', $this->post('trackNumber'));
list($response, $status) = $this->upstrack->processTrack();
/*
| -------------------------------------------------------------------
| UPS Rating API
| -------------------------------------------------------------------
*/
/* Ship To Address */
$this->load->library('UpsRating');
$this->upsrating->addField('ShipTo_Name', $data['name']);
$this->upsrating->addField('ShipTo_AddressLine', array(
	$data['address1'], $data['address2']   
));
$this->upsrating->addField('ShipTo_City', $data['city_name']);
$this->upsrating->addField('ShipTo_StateProvinceCode', $state_shortname);
$this->upsrating->addField('ShipTo_PostalCode', $data['post_code']);
$this->upsrating->addField('ShipTo_CountryCode', $country_shortname);
/* Package Dimension and Weight */
$cart = $this->cart->contents();
$dimensions = array();
$index = 0;
foreach( $cart as $rowid => $cart_data ) {
	$dimensions[$index]['Length'] = $cart_data['options']['Length'];
	$dimensions[$index]['Width'] = $cart_data['options']['Width'];
	$dimensions[$index]['Height'] = $cart_data['options']['Height'];
	$dimensions[$index]['Weight'] = $cart_data['options']['Weight'];
	$dimensions[$index]['Qty'] = $cart_data['qty'];
	$index++;
}
$this->upsrating->addField('dimensions', $dimensions);
$this->upsrating->processRate();
/*
| -------------------------------------------------------------------
| UPS Shipping API
| -------------------------------------------------------------------
*/
$this->load->library('UpsShipping');
/* Ship To Address */
$this->upsshipping->addField('ShipTo_Name', $delivery_address[0]->name);
$this->upsshipping->addField('ShipTo_AddressLine', array(
	$delivery_address[0]->address1, $delivery_address[0]->address2
));
$this->upsshipping->addField('ShipTo_City', $delivery_address[0]->city_name);
$this->upsshipping->addField('ShipTo_StateProvinceCode', $delivery_address[0]->state_shortname);
$this->upsshipping->addField('ShipTo_PostalCode', $delivery_address[0]->post_code);
$this->upsshipping->addField('ShipTo_CountryCode', $delivery_address[0]->country_shortname);
$this->upsshipping->addField('ShipTo_Number', $delivery_address[0]->phone);
$this->upsshipping->addField('Service_Code', $order['shipping_method']);
/* Package Dimension and Weight */
$order_details = $this->orders_model->getOrderDetail( $order_id );
$dimensions = array();
$index = 0;
foreach( $order_details as $key => $order_detail ) {
	$dimensions[$index]['Length'] = $order_detail->product_depth;
	$dimensions[$index]['Width'] = $order_detail->product_width;
	$dimensions[$index]['Height'] = $order_detail->product_height;
	$dimensions[$index]['Weight'] = $order_detail->product_weight;
	$dimensions[$index]['Qty'] = $order_detail->product_quantity;
	$index++;
}
$this->upsshipping->addField('dimensions', $dimensions);
list($response, $status) = $this->upsshipping->processShipAccept();
$ups_response = json_decode( $response );

// Response:
/* track_number = $ups_response->ShipmentResponse->ShipmentResults->ShipmentIdentificationNumber;
total_charges = $ups_response->ShipmentResponse->ShipmentResults->ShipmentCharges->TotalCharges->MonetaryValue;
graphic_image = $ups_response->ShipmentResponse->ShipmentResults->PackageResults->ShippingLabel->GraphicImage;
html_image = $ups_response->ShipmentResponse->ShipmentResults->PackageResults->ShippingLabel->HTMLImage; */
