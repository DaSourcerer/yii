<?php
/**
 * 
 * @author Da:Sourcerer
 */
class CHttpClient extends CApplicationComponent
{
	const CRLF="\r\n";
	const USER_AGENT_STRING='yii/{version} (compatible; +http://yiiframework.com)';
	
	const METHOD_GET='GET';
	const METHOD_HEAD='HEAD';
	const METHOD_POST='POST';
	
	/**
	 * 
	 * @var CHeaderCollection|array
	 */
	public $headers;
	
	/**
	 * 
	 * @var string
	 */
	public $cacheID='cache';
	
	/**
	 * 
	 * @var integer
	 */
	public $maxRedirects=3;
		
	/**
	 * 
	 * @var CHttpClientConnector|array
	 */
	public $connector;
	
	/**
	 * 
	 * @see CApplicationComponent::init()
	 */
	public function init()
	{
		if(empty($this->headers))
		{
			$headers=array(
				'User-Agent'=>str_replace('{version}',Yii::getVersion(),self::USER_AGENT_STRING),
				'Connection'=>($this->connector->useConnectionPooling?'keep-alive':'close'),
			);
			
		}
		parent::init();
	}
	
	/**
	 * @param $request CHttpClientRequest|string
	 * @return CHttpClientResponse
	 */
	public function fetch($request, $method=self::METHOD_GET)
	{
		if($request instanceof CHttpClientRequest)
		{
			$headers=new CHeaderCollection($this->headers);
			$headers->mergeWith($request->headers);
			$request->headers=$headers;
		}
		else 
			$request=new CHttpClientRequest($request,$method);
		
		return $this->fetchInternal($request, $this->maxRedirects);
	}
	
	protected function fetchInternal(CHttpClientRequest $request, $redirects)
	{
		
	}
	
	public function get($request)
	{
		
	}
	
	public function head($request)
	{
		
	}
	
	public function post($request)
	{
		
	}
	
	public function setHeaders($headers)
	{
		if(is_array($headers))
			$this->headers=new CHeaderCollection($headers);
		else
			$this->headers=$headers;
	}
	
	public function getConnector()
	{
		if(is_array($this->connector))
			$this->connector=Yii::createComponent($this->connector);
		else if(empty($this->connector))
			$this->connector=new CHttpClientConnector;
		return $this->connector;
	}
}

abstract class CHttpClientMessage extends CComponent
{
	/**
	 * The http version associated with this message.
	 * Make sure this is either 0.9, 1.0 or 1.1
	 * @var float
	 */
	public $httpVersion=1.1;
	
	/**
	 * 
	 * @var CHeaderCollection
	 */
	public $headers;
	public $cookies;
	public $body;	
}

class CHttpClientResponse extends CHttpClientMessage
{
	public $status;
	public $message;
	
	public static function fromStream($stream)
	{
		fscanf();
	}
}

class CHttpClientRequest extends CHttpClientMessage
{
	public $target;
	public $method=CHttpClient::METHOD_GET;
	
	public function __construct($target, $method=CHttpClient::METHOD_GET)
	{
		$this->target=$target;
		$this->method=$method;
	}
	
	public function setBody($body)
	{
		if($this->method==CHttpClient::METHOD_GET)
			throw new CException("Cannot set a body on a GET request");
		$this->body=$body;
	}
	
	public function __toString()
	{
		$result=sprintf('%s %s HTTP/%.1f', $this->method, $this->target, $this->httpVersion) . CHttpClient::CRLF;
		foreach($this->headers as $key=>$value)
		{
			$result.="{$key}: {$value}".CHttpClient::CRLF;
		}
		if(!empty($this->body))
			$result.=$this->body;
		$result.=CHttpClient::CRLF;
		return $result;
	}
}

class CHeaderCollection extends CMap
{
	
}

class CHttpClientConnector extends CComponent
{
	/**
	 * @var integer
	 */
	public $timeout=5;
	
	/**
	 * 
	 * @var boolean
	 */
	public $useConnectionPooling=false;
	protected static $_connections=array();
	
	public function getConnection(CHttpClientRequest $request)
	{
		$parsedUrl=parse_url($request->target);
		if($parsedUrl===false)
			throw new CException('Malformed URL: '.$request->target);
		
		if(!in_array($parsedUrl['protocol'], array('http', 'https')))
			throw new CException('Unsupported protocol: '.$parsedUrl['protocol']);
		
		if(defined(AF_INET6))
			$type=DNS_A|DNS_AAAA;
		else
			$type=DNS_A;
		
		$host=dns_get_record($parsedUrl['host'],$type);
		
		if($host===false)
			throw new CException("Could not resolve host ".$parsedUrl($host));
		
		$host=($parsedUrl['protocol']=='http'?'tcp':'https').'://'.$host;
		$port=80;
		if(isset($parsedUrl['port']))
			$port=$parsedUrl['port'];
		else if($parsedUrl['protocol']=='https')
			$port=443;
		
		if($useConnectionPooling)
		{
			$hash=$host.':'.$port;
			if(!isset(self::$_connections[$hash]))
				self::$_connections[$hash]=$this->connect($host, $port);
			return self::$_connections[$hash];
		}
		
		return $this->connect($host, $port);
	}
	
	protected function connect($host, $port)
	{
		$connection=fsockopen($host, $port, $errno, $errstr, $this->timeout);
		if($connection===false)
			throw new CException("Failed to connect to {$host} ({$errno}): {$errstr}");
		return $connection;
	}
}

class CHttpClientProxyConnector extends CHttpClientConnector
{
	public $server;
	public $port;
	public $username;
	public $password;
	public $authType='basic';
	public $type='http';
	
	public $exceptions=array(
		'localhost',
		'127.0.0.1',
		'::1',
	);
} 