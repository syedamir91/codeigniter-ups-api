<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class UpsTrack {
	private $CI;
	private $fields = array();
	public function __construct() {
        $this->CI =& get_instance();
    }
	public function addField($field, $value) {
		$this->fields[$field] = $value;
	}
	public function processTrack() {
		try {
			$trackData = $this->getProcessTrack();
			$trackData = json_encode( $trackData );

			/* Curl start to call UPS tracking API */
			$ch = curl_init($this->CI->config->item('ups')['urls']['tracking']);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $trackData);
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
	public function getProcessTrack() {
		$userNameToken['Username'] = $this->CI->config->item('ups')['account']['userid'];
		$userNameToken['Password'] = $this->CI->config->item('ups')['account']['passwd'];
		$UPSSecurity['UsernameToken'] = $userNameToken;
		$accessLicenseNumber['AccessLicenseNumber'] = $this->CI->config->item('ups')['account']['access'];
		$UPSSecurity['ServiceAccessToken'] = $accessLicenseNumber;
		$request['UPSSecurity'] = $UPSSecurity;

		$TrackRequest['InquiryNumber'] = $this->fields['trackNumber'];

		$request['TrackRequest'] = $TrackRequest;
		return $request;
	}
}