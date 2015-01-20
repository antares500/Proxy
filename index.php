<?php //proxy 0.0.1 - 17/dic/2014
/*
#ejemplo
$proxy = new proxy('https://urldeprueba.com/recurso','urldeprueba.com');
*/

//demo class
class proxy {
	
	public
		$agent	= 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)',
		$call,
		$ckfile,
		$base,
		$domain
	;
	
	function __construct($base, $domain){
		$this->base		= $base;
		$this->domain	= $domain;
		
		session_start();
		ob_start();
		
		//furl
		$this->call		= substr($_SERVER['REQUEST_URI'], strlen(substr($_SERVER['PHP_SELF'],0,-9)) );
		$this->ckfile	= 'simpleproxy-cookie-'.session_id();
		
		$response = $this->call();
		if($response[1]===true){	echo $response[0];	}
		else {
			$response = $response[0];
			
			//clean duplicate header that seems to appear on fastcgi with output buffer on some servers!!
			$response	= str_replace("HTTP/1.1 100 Continue\r\n\r\n","",$response);
			$ar			= explode("\r\n\r\n", $response, 2); 
			$header		= $ar[0];
			$body		= $ar[1];
			
			//handle headers - simply re-outputing them
			$header_ar = split(chr(10),$header); 
			foreach($header_ar as $k=>$v){
				if(!preg_match("/^Transfer-Encoding/",$v)){
					$v = str_replace($this->base,$mydomain,$v); //header rewrite if needed
					header(trim($v));
				}
			}
			
			//rewrite all hard coded urls to ensure the links still work!
			$body = str_replace($this->base,$mydomain,$body);
			
			echo $body;
		}
	}
	
	function call(){
		$url = $this->base.($this->call?'/'.$this->call:'');
		
		// Open the cURL session
		$curlSession = curl_init();
		
		curl_setopt($curlSession,	CURLOPT_URL,			$url);
		curl_setopt($curlSession,	CURLOPT_HEADER,			1);
		
		if($_SERVER['REQUEST_METHOD'] == 'POST'){
			curl_setopt($curlSession, CURLOPT_POST,			1);
			curl_setopt($curlSession, CURLOPT_POSTFIELDS,	$_POST);
		}
		
		curl_setopt($curlSession, CURLOPT_FOLLOWLOCATION,	false ); //disable redirects
		curl_setopt($curlSession, CURLOPT_USERAGENT,		$this->agent); //emula un navegador
		
		curl_setopt($curlSession, CURLOPT_RETURNTRANSFER,	1);
		curl_setopt($curlSession, CURLOPT_TIMEOUT,			30);
		curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST,	1);
		curl_setopt($curlSession, CURLOPT_COOKIEJAR,		$this->ckfile); 
		curl_setopt($curlSession, CURLOPT_COOKIEFILE,		$this->ckfile);
		
		//handle other cookies cookies
		foreach($_COOKIE as $k=>$v){
			if(is_array($v)){	$v = serialize($v);	}
			curl_setopt($curlSession,CURLOPT_COOKIE,"$k=$v; domain=.".$this->domain." ; path=/");
		}
		
		//Send the request and store the result in an array
		$response = curl_exec($curlSession);
		
		// Check that a connection was made
		if (curl_error($curlSession)){	$response = curl_error($curlSession);	$result=false;	} // If it wasn't...
		else { $result = true;	}
		curl_close ($curlSession);
		
		return array($response, $result);
	}
}

?>