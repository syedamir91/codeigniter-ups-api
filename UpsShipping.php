<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class UpsShipping {
	private $CI;
	private $fields = array();
	public function __construct() {
        $this->CI =& get_instance();
    }
	public function addField($field, $value) {
		$this->fields[$field] = $value;
	}
	public function processShipAccept() {
		try {
			$shipmentData = $this->getProcessShipAccept();
			$shipmentData = json_encode( $shipmentData );

			/* Curl start to call UPS shipping API */
			$ch = curl_init($this->CI->config->item('ups')['urls']['shipping']);
			curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $shipmentData);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
			if ( !($res = curl_exec($ch)) ) {
				die(date('[Y-m-d H:i e] '). "Got " . curl_error($ch) . " when processing data");
				curl_close($ch);
				exit;
			}
			curl_close($ch);
			/* Curl End */
			
			if( is_string( $res ) ) {
				$resObject = json_decode( $res );
			}
			if( isset( $resObject->Fault ) && !empty( $resObject->Fault ) ) {
				return array( $res, 403 );
			} else if( isset( $resObject->ShipmentResponse ) && !empty( $resObject->ShipmentResponse ) ) {
				return array( $res, 200 );
			}
		}
		catch(Exception $ex) {
			return array( $ex, 403 );
		}
	}
	public function getProcessShipAccept() {
		$userNameToken['Username'] = $this->CI->config->item('ups')['account']['userid'];
		$userNameToken['Password'] = $this->CI->config->item('ups')['account']['passwd'];
		$UPSSecurity['UsernameToken'] = $userNameToken;
		$accessLicenseNumber['AccessLicenseNumber'] = $this->CI->config->item('ups')['account']['access'];
		$UPSSecurity['ServiceAccessToken'] = $accessLicenseNumber;
		$request['UPSSecurity'] = $UPSSecurity;

		/* Important */
		$requestoption['RequestOption'] = 'nonvalidate';
		$request['ShipmentRequest']['Request'] = $requestoption;

		/* Important */
		$shipment['Description'] = $this->CI->config->item('ups')['shipper']['Description'];
		$shipper['Name'] = $this->CI->config->item('ups')['shipper']['Name'];
		$shipper['AttentionName'] = $this->CI->config->item('ups')['shipper']['AttentionName'];
		$shipper['ShipperNumber'] = $this->CI->config->item('ups')['account']['shipperNumber'];
		$address['AddressLine'] = $this->CI->config->item('ups')['shipper']['AddressLine'];
		$address['City'] = $this->CI->config->item('ups')['shipper']['City'];
		$address['StateProvinceCode'] = $this->CI->config->item('ups')['shipper']['StateProvinceCode'];
		$address['PostalCode'] = $this->CI->config->item('ups')['shipper']['PostalCode'];
		$address['CountryCode'] = $this->CI->config->item('ups')['shipper']['CountryCode'];
		$shipper['Address'] = $address;
		$phone['Number'] = $this->CI->config->item('ups')['shipper']['Number'];
		$shipper['Phone'] = $phone;
		$shipment['Shipper'] = $shipper;

		/* Important */
		/* Shipping to Customer Address */		
		$shipto['Name'] = $this->fields['ShipTo_Name'];
		$shipto['AttentionName'] = $this->fields['ShipTo_Name'];
		$addressTo['AddressLine'] = $this->fields['ShipTo_AddressLine'];
		$addressTo['City'] = $this->fields['ShipTo_City'];
		$addressTo['StateProvinceCode'] = $this->fields['ShipTo_StateProvinceCode'];
		$addressTo['PostalCode'] = $this->fields['ShipTo_PostalCode'];
		$addressTo['CountryCode'] = $this->fields['ShipTo_CountryCode'];
		$shipto['Address'] = $addressTo;
		$phone2['Number'] = $this->fields['ShipTo_Number'];
		$shipto['Phone'] = $phone2;
		$shipment['ShipTo'] = $shipto;

		/* Important */
		$shipmentcharge['Type'] = '01';
		$creditcard['Type'] = '06';
		$creditcard['Number'] = $this->CI->config->item('ups')['cc']['CC_Number'];
		$creditcard['SecurityCode'] = $this->CI->config->item('ups')['cc']['CC_SecurityCode'];
		$creditcard['ExpirationDate'] = $this->CI->config->item('ups')['cc']['CC_ExpirationDate'];
		$creditCardAddress['AddressLine'] = $this->CI->config->item('ups')['cc']['CC_AddressLine'];
		$creditCardAddress['City'] = $this->CI->config->item('ups')['cc']['CC_City'];
		$creditCardAddress['StateProvinceCode'] = $this->CI->config->item('ups')['cc']['CC_StateProvinceCode'];
		$creditCardAddress['PostalCode'] = $this->CI->config->item('ups')['cc']['CC_PostalCode'];
		$creditCardAddress['CountryCode'] = $this->CI->config->item('ups')['cc']['CC_CountryCode'];
		$creditcard['Address'] = $creditCardAddress;
		$billshipper['CreditCard'] = $creditcard;
		$shipmentcharge['BillShipper'] = $billshipper;
		$paymentinformation['ShipmentCharge'] = $shipmentcharge;
		$shipment['PaymentInformation'] = $paymentinformation;

		/* Important */
		$service['Code'] = $this->fields['Service_Code'];
		// $service['Description'] = 'Expedited';
		$shipment['Service'] = $service;

		/* Important */
		$packaging['Code'] = '02';
		$package['Packaging'] = $packaging;

		/* Important */
		$package_array = array();
		$weight = 0;
		foreach( $this->fields['dimensions'] as $dimension ) {
			$weight = $weight + $dimension['Weight']*$dimension['Qty'];
		}
		$punit['Code'] = 'LBS';
		$punit['Description'] = 'Pounds';
		$packageweight['Weight'] = "$weight";
		$packageweight['UnitOfMeasurement'] = $punit;
		$package['PackageWeight'] = $packageweight;

		$shipment['Package'] = array( $package );
		
		/* Important */
		$labelimageformat['Code'] = 'GIF';
		$labelimageformat['Description'] = 'GIF';
		$labelspecification['LabelImageFormat'] = $labelimageformat;
		$labelspecification['HTTPUserAgent'] = 'Mozilla/4.5';
		$shipment['LabelSpecification'] = $labelspecification;
		$request['ShipmentRequest']['Shipment'] = $shipment;
		return $request;
	}
}