<?php

class Utils {
	
	// ----------------------------------------------------------------------------
	//
	// Fonction / Function : hmac_sha1
	//
	// RFC 2104 HMAC implementation for PHP >= 4.3.0 - Creates a SHA1 HMAC.
	// Eliminates the need to install mhash to compute a HMAC
	// Adjusted from the md5 version by Lance Rushing .
	//
	// Implémentation RFC 2104 HMAC pour PHP >= 4.3.0 - Création d'un SHA1 HMAC.
	// Elimine l'installation de mhash pour le calcul d'un HMAC
	// Adaptée de la version MD5 de Lance Rushing.
	//
	// ----------------------------------------------------------------------------
	public static function hmac_sha1 ($key, $data)
	{
		$length = 64; // block length for SHA1
		if (strlen($key) > $length) { $key = pack("H*",sha1($key)); }
		$key  = str_pad($key, $length, chr(0x00));
		$ipad = str_pad('', $length, chr(0x36));
		$opad = str_pad('', $length, chr(0x5c));
		$k_ipad = $key ^ $ipad ;
		$k_opad = $key ^ $opad;

		return sha1($k_opad  . pack("H*",sha1($k_ipad . $data)));
	}
	
	// ----------------------------------------------------------------------------
	// function HtmlEncode
	//
	// IN:  chaine a encoder / String to encode
	// OUT: Chaine encodée / Encoded string
	//
	// Description: Encode special characters under HTML format
	//                           ********************
	//              Encodage des caractères spéciaux au format HTML
	// ----------------------------------------------------------------------------
	public static function HtmlEncode ($data)
	{
		$SAFE_OUT_CHARS = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890._-";
		$result = "";
		for ($i=0; $i<strlen($data); $i++)
		{
			if (strchr($SAFE_OUT_CHARS, $data{$i})) {
				$result .= $data{$i};
			}
			else if (($var = bin2hex(substr($data,$i,1))) <= "7F"){
				$result .= "&#x" . $var . ";";
			}
			else
				$result .= $data{$i};

		}
		return $result;
	}
}
		
?>
