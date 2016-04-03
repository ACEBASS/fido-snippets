<?php
include('Crypt/RSA.php');

class fido_authenticator
{
	public static function validate_signature($pk,$clientData,$authnrData,$signature,$challenge)
	{
		$c = fido_authenticator::rfc4648_base64_url_decode($clientData);
		$a = fido_authenticator::rfc4648_base64_url_decode($authnrData);
		$s = fido_authenticator::rfc4648_base64_url_decode($signature);

		// Make sure the challenge in the client data matches the expected challenge
		$j = json_decode(trim($c));
		if($j->{'challenge'}!=$challenge) return false;

		// Hash data with sha-256
		$hash = new Crypt_Hash('sha256');
		$h = $hash->hash($c);

		// Load public key
		$rsa = new Crypt_RSA();
		fido_authenticator::loadJWK($rsa,$pk);
		$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
		$rsa->setHash('sha256');

		// Verify signature is correct for authnrData + hash
		return $rsa->verify($a . $h,$s);
	}

	private static function loadJWK($rsa,$pk)
	{
		$jpk = json_decode($pk);
		if($jpk->{'kty'}!='RSA' || $jpk->{'alg'}!='RS256') throw new Exception('Invalid key type.');
		$n = fido_authenticator::rfc4648_base64_url_decode($jpk->{'n'});
		$e = fido_authenticator::rfc4648_base64_url_decode($jpk->{'e'});
		$raw = array("n"=>new Math_BigInteger($n,256),"e"=>new Math_BigInteger($e,256));
		$rsa->loadKey($raw);
	}

	private static function rfc4648_base64_url_decode($url)
	{
	  $url = str_replace('-', '+', $url); // 62nd char of encoding
	  $url = str_replace('_', '/', $url); // 63rd char of encoding

	  switch (strlen($url) % 4) // Pad with trailing '='s
	  {
	     case 0:
	        // No pad chars in this case
	        break;
	     case 2:
	        // Two pad chars
	        $url .= "==";
	        break;
	     case 3:
	        // One pad char
	        $url .= "=";
	        break;
	     default:
	        $url = FALSE;
	  }

	  if($url) $url = base64_decode($url);
	  return $url;
	}
}

?>