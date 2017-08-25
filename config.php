/*
| -------------------------------------------------------------------
| UPS Account Setting
| -------------------------------------------------------------------
*/
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
/*
| -------------------------------------------------------------------
| UPS Shipper
| -------------------------------------------------------------------
*/
$config['ups']['shipper'] = array(
	'Description' => '***',
	'Name' => '***',
	'AttentionName' => '***',
	'AddressLine' => array(
		'***',
		'***'
	),
	'City' => '***',
	'StateProvinceCode' => '***',
	'PostalCode' => '***',
	'CountryCode' => '**',
	'Number' => '**********'
);

/*
| -------------------------------------------------------------------
| UPS Serivce Type
| -------------------------------------------------------------------
*/
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
