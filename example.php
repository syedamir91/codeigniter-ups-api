############################ UPS Config ############################
/ UPS Account Setting /
$config['ups']['account'] = array(
	'access' => '1D**************',
	'userid' => '*****',
	'passwd' => '*******',
	'shipperNumber' => '******',
	'mode' => 'test' // production
);
$config['ups']['urls'] = array(
	'rating' => ( $config['ups']['account']['mode'] == 'test' ) ? 'https://wwwcie.ups.com/rest/Rate' : 'https://wwwcie.ups.com/rest/Rate',
	'shipping' => ( $config['ups']['account']['mode'] == 'test' ) ? 'https://wwwcie.ups.com/rest/Ship' : 'https://onlinetools.ups.com/rest/Ship',
	'tracking' => ( $config['ups']['account']['mode'] == 'test' ) ? 'https://wwwcie.ups.com/rest/Track' : 'https://onlinetools.ups.com/rest/Track'
);
/ UPS Shipper /
$config['ups']['shipper'] = array(
	'Description' => '***',
	'Name' => '***',
	'AttentionName' => '***',
	'AddressLine' => array(
		'3809 Branch Ave',
		'Iverson Mall'
	),
	'City' => '***',
	'StateProvinceCode' => '***',
	'PostalCode' => '***',
	'CountryCode' => 'US',
	'Number' => '**********'
);
/ UPS Serivce Type /
$config['ups']['services'] = array(
	'01' => 'UPS Next Day Air',
	'02' => 'UPS 2nd Day Air',
	'03' => 'UPS Ground',
	'07' => 'UPS Worldwide Express',
	'08' => 'UPS Worldwide Expedited',
	'11' => 'UPS Standard',
	'12' => 'UPS 3 Day Select',
	'13' => 'UPS Next Day Air Saver',
	'14' => 'UPS Next Day Air Early A.M.',
	'54' => 'UPS Worldwide Express Plus',
	'59' => 'UPS 2nd Day Air AM',
	'65' => 'UPS World Wide Saver'
);
############################ UPS Tracking API ############################
/ Track /
$this->load->library('UpsTrack');
$this->upstrack->addField('trackNumber', $this->post('trackNumber'));
list($response, $status) = $this->upstrack->processTrack();
############################ UPS Rating API ############################
/ Ship To Address /
$this->load->library('UpsRating');
$this->upsrating->addField('ShipTo_Name', $data['name']);
$this->upsrating->addField('ShipTo_AddressLine', array(
	$data['address1'], $data['address2']   
));
$this->upsrating->addField('ShipTo_City', $data['city_name']);
$this->upsrating->addField('ShipTo_StateProvinceCode', $state_shortname);
$this->upsrating->addField('ShipTo_PostalCode', $data['post_code']);
$this->upsrating->addField('ShipTo_CountryCode', $country_shortname);
/ Package Dimension and Weight /
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
############################ UPS Shipping API ############################  
$this->load->library('UpsShipping');
/ Ship To Address /
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
/ Package Dimension and Weight /
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

Response:
track_number = $ups_response->ShipmentResponse->ShipmentResults->ShipmentIdentificationNumber;
total_charges = $ups_response->ShipmentResponse->ShipmentResults->ShipmentCharges->TotalCharges->MonetaryValue;
graphic_image = $ups_response->ShipmentResponse->ShipmentResults->PackageResults->ShippingLabel->GraphicImage;
html_image = $ups_response->ShipmentResponse->ShipmentResults->PackageResults->ShippingLabel->HTMLImage;