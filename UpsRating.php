<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class UpsRating {
	private $CI;
	private $fields = array();
	public function __construct() {
        $this->CI =& get_instance();
    }
	public function addField($field, $value) {
		$this->fields[$field] = $value;
	}
	public function processRate() {
		try {
			$rateData = $this->getProcessRate();
			$rateData = json_encode( $rateData );
			
			/* Curl start to call UPS rating API */
			$ch = curl_init($this->CI->config->item('ups')['urls']['rating']);
			curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $rateData);
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
			} else if( isset( $resObject->RateResponse ) && !empty( $resObject->RateResponse ) ) {
				return array( $res, 200 );
			}
		}
		catch(Exception $ex) {
			return array( $ex, 403 );
		}
	}
	private function getProcessRate() {
		$userNameToken['Username'] = $this->CI->config->item('ups')['account']['userid'];
		$userNameToken['Password'] = $this->CI->config->item('ups')['account']['passwd'];
		$UPSSecurity['UsernameToken'] = $userNameToken;
		$accessLicenseNumber['AccessLicenseNumber'] = $this->CI->config->item('ups')['account']['access'];
		$UPSSecurity['ServiceAccessToken'] = $accessLicenseNumber;
		$request['UPSSecurity'] = $UPSSecurity;
		
		$option['RequestOption'] = 'Shop';
		$request['RateRequest']['Request'] = $option;

		$pickuptype['Code'] = '01';
		$pickuptype['Description'] = 'Daily Pickup';
		$request['PickupType'] = $pickuptype;

		$customerclassification['Code'] = '01';
		$customerclassification['Description'] = 'Classfication';
		$request['CustomerClassification'] = $customerclassification;
		
		$shipper['Name'] = $this->CI->config->item('ups')['shipper']['Name'];
		$shipper['ShipperNumber'] = $this->CI->config->item('ups')['account']['shipperNumber'];
		$address['AddressLine'] = $this->CI->config->item('ups')['shipper']['AddressLine'];
		$address['City'] = $this->CI->config->item('ups')['shipper']['City'];
		$address['StateProvinceCode'] = $this->CI->config->item('ups')['shipper']['StateProvinceCode'];
		$address['PostalCode'] = $this->CI->config->item('ups')['shipper']['PostalCode'];
		$address['CountryCode'] = $this->CI->config->item('ups')['shipper']['CountryCode'];
		$shipper['Address'] = $address;
		$shipment['Shipper'] = $shipper;

		$shipto['Name'] = $this->fields['ShipTo_Name'];
		$addressTo['AddressLine'] = $this->fields['ShipTo_AddressLine'];
		$addressTo['City'] = $this->fields['ShipTo_City'];
		$addressTo['StateProvinceCode'] = $this->fields['ShipTo_StateProvinceCode'];
		$addressTo['PostalCode'] = $this->fields['ShipTo_PostalCode'];
		$addressTo['CountryCode'] = $this->fields['ShipTo_CountryCode'];
		$shipto['Address'] = $addressTo;
		$shipment['ShipTo'] = $shipto;

		$service['Code'] = '03';
		$service['Description'] = 'Service Code';
		$shipment['Service'] = $service;
		$package = array();
		$packaging['Code'] = '02';
		$packaging['Description'] = 'Rate';
		$package['PackagingType'] = $packaging;
		$weight = 0;
		foreach( $this->fields['dimensions'] as $dimension ) {
			$weight = $weight + ($dimension['Weight']*$dimension['Qty']);
		}
		$punit['Code'] = 'LBS';
		$punit['Description'] = 'Pounds';
		$packageweight['Weight'] = "$weight";
		$packageweight['UnitOfMeasurement'] = $punit;
		$package['PackageWeight'] = $packageweight;

		$shipment['Package'] = array( $package );
		$request['RateRequest']['Shipment'] = $shipment;
		return $request;
	}
}