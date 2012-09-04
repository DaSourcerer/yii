<?php
/**
 * CHttpCLient class file.
 *
 * @author Da:Sourcerer <webmaster@dasourcerer.net>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2012 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * CHttpClient is an advanced HTTP client. Good for making HTTP requests. 
 * 
 * CHttpClient itself is mostly for higher level management. All the magic is happening in the connectors.
 * 
 * @author Da:Sourcerer <webmaster@dasourcerer.net>
 * @version $Id$
 * @package system.web
 */
class CHttpClient extends CApplicationComponent
{
	const CRLF="\r\n";
	const USER_AGENT_STRING='yii/{version} (compatible; +http://yiiframework.com)';
	
	const METHOD_GET='GET';
	const METHOD_HEAD='HEAD';
	const METHOD_POST='POST';
	const METHOD_PUT='PUT';
	const METHOD_DELETE='DELETE';
	const METHOD_TRACE='TRACE';
	const METHOD_OPTIONS='OPTIONS';
	const METHOD_CONNECT='CONNECT';
	const METHOD_PATCH='PATCH';
	
	/**
	 * 
	 * @var CHeaderCollection|array
	 */
	public $headers;
	
	/**
	 * @var integer the maximum number of redirects to follow. Set to 0 in order to follow no redirects at all.
	 */
	public $maxRedirects=3;
		
	/**
	 * @var CHttpClientConnector|array this client's connector
	 */
	public $connector;
	
	/**
	 * @see CApplicationComponent::init()
	 */
	public function init()
	{
		parent::init();

		if(!isset($this->headers['User-Agent']))
			$this->headers['User-Agent']=str_replace('{version}',Yii::getVersion(),self::USER_AGENT_STRING);
		
		if(!isset($this->headers['Accept']))
			$this->headers['Accept']='*/*';
	}
	
	/**
	 * @param $request CHttpClientRequest|string
	 * @return CHttpClientResponse
	 */
	public function fetch($request, $method=self::METHOD_GET)
	{
		if(!($request instanceof CHttpClientRequest))
			$request=new CHttpClientRequest($request,$method);		
		return $this->fetchInternal($request, $this->maxRedirects);
	}
	
	protected function fetchInternal(CHttpClientRequest $request, $redirects)
	{
		$headers=new CHeaderCollection($this->headers);
		$headers->mergeWith($request->headers);
		$request->headers=$headers;
		$response=$this->connector->perform($request);
		
		if(in_array($response->status,array(301, 302, 303)))
		{
			if($redirects>0)
			{
				$request=CHttpClientRequest::fromResponse($response);
				return $this->fetchInternal($request, --$redirects);
			}
			else
				throw new CException('Max number of redirects reached');
		}
		
		return $response;
	}
	
	public function get($request)
	{
		return $this->fetch($request);
	}
	
	
	public function head($request)
	{
		return $this->fetch($request, self::METHOD_HEAD);
	}
	
	public function post($request)
	{
		return $this->fetch($request, self::METHOD_POST);
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

/**
 * CHttpClientMessage is the base class for all HTTP messages (i.e. requests and responses)  
 * 
 * @author Da:Sourcerer <webmaster@dasourcerer.net>
 * @version $Id$
 * @package system.web
 */
abstract class CHttpClientMessage extends CComponent
{
	/**
	 * @var float The http protocol version associated with this message. Make
	 * sure this is either 0.9, 1.0 or 1.1, as there won't be any validation
	 * for this. If you access this on a response object, don't be surprised to
	 * find some odd values.
	 */
	public $httpVersion=1.1;
	
	/**
	 * @var CHeaderCollection a collection of headers
	 */
	public $headers;
	
	/**
	 * @var CCookieCollection a collection of cookies
	 */
	public $cookies;
	
	/**
	 * @var mixed the body of this message. Might be empty for some request and response types.
	 */
	public $body;	
}

/**
 * CHttpClientResponse
 * 
 * @author Da:Sourcerer <webmaster@dasourcerer.net>
 * @version $Id$
 * @package system.web
 */
class CHttpClientResponse extends CHttpClientMessage
{
	/**
	 * @var integer the HTTP status code
	 */
	public $status;
	
	/**
	 * @var string the HTTP status message. Take note that not every server sends this, so stick to {@link status}
	 */
	public $message;
}

class CHttpClientRequest extends CHttpClientMessage
{
	public $requestUrl;
	public $method=CHttpClient::METHOD_GET;
	
	public $scheme;
	public $host;
	public $port;
	public $user;
	public $pass;
	public $path;
	public $query;
	public $fragment;
	
	public function __construct($requestUrl, $method=CHttpClient::METHOD_GET)
	{
		$this->requestUrl=$requestUrl;
		$this->method=$method;
	}
	
	public function setRequestUrl($url)
	{
		$parsedUrl=parse_url($url);
		if($parsedUrl===false)
			throw new CException('Malformed URL: '.$request->target);
		
		foreach($parsedUrl as $key=>$value)
			$this->$key=$value;
		$this->requestUrl=$url;
	}
	
	public function setScheme($scheme)
	{
		if(!in_array($scheme, array('http', 'https')))
			throw new CException('Unsupported protocol: '.$scheme);
	}
	
	public function setHost($host)
	{
		$this->host=$host;
		if($this->httpVersion==1.1)
			$this->headers['Host']=$host;
	}
	
	public function setUser()
	{
		$this->user=$user;
		$this->updateAuthenticationHeader();
	}
	
	public function setPass($pass)
	{
		$this->pass=$pass;
		$this->updateAuthenticationHeader();
	}
	
	public function setBody($body)
	{
		if($this->method==CHttpClient::METHOD_GET)
			throw new CException("Cannot set body on a GET request");
		$this->body=$body;
		$this->headers['Content-Length']=function_exists('mb_strlen')?mb_strlen($this->body,Yii::app()->charset):strlen($this->body);
	}
	
	protected function updateAuthenticationHeader()
	{
		if($this->user && $this->pass)
			$this->headers['Authorization']='Basic '.base64_encode($this->user.':'.$this->pass);
	}
	
	/**
	 * 
	 * @param CHttpClientResponse $response
	 * @return CHttpClientRequest
	 */
	public static function fromRedirect(CHttpClientResponse $response)
	{
		if(!isset($response->headers['Location']))
			throw new CException('No redirect location!');
		
		return new CHttpClientRequest($response->headers['Location']);
	}
}

/**
 * CHeaderCollection
 *
 * @author Da:Sourcerer <webmaster@dasourcerer.net>
 * @version $Id$
 * @package system.web
 */
class CHeaderCollection extends CList {}

class CHttpClientConnector extends CComponent
{
	/**
	 * 
	 * @var string
	 */
	public $cacheID='cache';
	
	/**
	 * @var integer
	 */
	public $timeout=5;
	
	/**
	 * @var boolean Controls if the connector should try to re-use existing
	 * connections within a single script run. This is mostly useful for console
	 * commands or if a proxy is being used. Please note that this will directly
	 * effect the <code>Connection</code> HTTP header.
	 */
	public $useConnectionPooling=false;
	
	protected static $_connections=array();
	/**
	 * @var array a set of additional headers set and managed by this connector
	 */
	private $_headers=array();
	
	public function init()
	{
		parent::init();
		$encodings=array();
		$filters=stream_get_filters();
		if(in_array('zlib.*', $filters))
			push_array($encodings, 'gzip', 'x-gzip', 'deflate');
		if(in_array('bzip2.*', $filters))
			push_array($encodings, 'bzip2', 'x-bzip2');
		
		if(!empty($encodings))
			$this->_headers['Accept-Encoding']=implode(', ', $encodings);
		
		if(in_array('dechunk', $filters))
			$this->_headers['TE']='chunked';
	}
	
	public function setUseConnectionPooling($useConnectionPooling)
	{
		$this->useConnectionPooling=$useConnectionPooling;
		if($this->useConnectionPooling)
			$this->_headers['Connection']='keep-alive';
		else
			$this->_headers['Connection']='close';
	}
	
	public function getConnection(CHttpClientRequest $request)
	{
		$key='system.web.CHttpClientConnector.dns#'.$request->host;
		if(($host=$this->getCache()->get($key))===false)
		{
			if(defined(AF_INET6))
				$type=DNS_A|DNS_AAAA;
			else
				$type=DNS_A;
		
			$records=dns_get_record($request->host,$type);
			
			if(empty($records))
				throw new CException("Could not resolve host ".$request->host);
			
			if($records[0]['type']==='AAAA')
				$host='['.$records[0]['ipv6'].']';
			else
				$host=$records[0]['ip'];
			
			$this->getCache()->set($key, $host, $records[0]['ttl']);
		}
		
		$port=80;
		if(isset($request->port))
			$port=$request->port;
		else if($request->scheme=='https')
			$port=443;
		
		return $this->connect($host, $port, $request->scheme=='https');
	}
	
	public function getCache()
	{
		return Yii::app()->getComponent($this->cacheID);
	}
	
	public function perform(CHttpClientRequest $request)
	{
		$connection=$this->getConnection($request);
		
		$requestString=$this->craftRequestLine($request).CHttpClient::CRLF;
		$headers=$request->headers;
		$headers->mergeWith($this->_headers);
		foreach($headers as $header=>$value)
			$requestString.="{$header}: {$value}".CHttpClient::CRLF;
		
		if($request->body)
			$requestString.=CHttpClient::CRLF.$request->body;
		$requestString.=CHttpClient::CRLF;
		
		$requestStringLength=(function_exists('mb_strlen')?mb_strlen($requestString,Yii::app()->charset):strlen($requestString));
		$written=fwrite($connection, $requestString);
		
		if($written!=$requestStringLength)
			Yii::log("Wrote {$written} instead of {$requestStringLength} bytes to stream - possible network error", 'notice', 'system.web.CHttpClientConnector');
		
		$response=new CHttpClientResponse;
		fscanf($connection, 'HTTP/%.1f %d %s', $response->httpVersion, $response->status, $response->message);
		if($response->httpVersion>0.9)
		{
			while($line=fgets($connection) && $line!=CHttpClient::CRLF && !feof($connection))
			{
				list($header,$content)=explode(':',$line);
				$response->headers[$header]=trim($content);
			}
			
			if(isset($response->headers['Transfer-Encoding']) && $response->headers['Transfer-Encoding']=='chunked')
				stream_filter_append($connection, 'dechunk');
			
			if(isset($response->headers['Content-Encoding']))
			{
				switch($response->headers['Content-Encoding'])
				{
					case 'gzip':
					case 'x-gzip':
						stream_filter_append($connection, 'zlib.decode');
						break;
					case 'bzip2':
					case 'x-bzip2':
						stream_filter_append($connection, 'bzip2.decompress');
						break;
					case 'deflate':
						stream_filter_append($connection, 'zlib.inflate');
						break;
					default:
						throw new CException('Unknown content encoding: '.$response->headers['Content-Encoding']);
				}
			}
		}
		
		while(!feof($connection))
			$response->body.=fgets($connection);
		
		return $response;
	}
	
	protected function craftRequestLine(CHttpClientRequest $request)
	{
		$requestUrl=$request->path;
		if($request->query)
			$requestUrl.='?'.$request->query;
		return sprintf('%s %s HTTP/%.1f', $request->method, $requestUrl, $request->httpVersion);
	}
	
	protected function connect($host, $port, $ssl=false)
	{
		if($this->useConnectionPooling)
		{
			$key=$ssl.':'.$host.':'.$port;
			if(!isset(self::$_connections[$key]))
			{
				$connection=@fsockopen(($ssl?'ssl':'tcp').'://'.$host, $port, $errno, $errstr, $this->timeout);
				if($connection===false)
					throw new CException("Failed to connect to {$host} ({$errno}): {$errstr}");
				self::$_connections[$key]=$connection;
			}
			return self::$_connections[key];
		}
		
		$connection=@fsockopen(($ssl?'ssl':'tcp').'://'.$host, $port, $errno, $errstr, $this->timeout);
		if($connection===false)
			throw new CException("Failed to connect to {$host} ({$errno}): {$errstr}");
		return $connection;
	}
}

class CHttpClientProxyConnector extends CHttpClientConnector
{
	public $server;
	public $port=8080;
	public $username;
	public $password;
	public $authType='Basic';
	public $ssl=false;
	
	/**
	 * @var array a list of IPs which should not be reached through the proxy.
	 * Remember to place IPv6 addresses into square brackets.
	 */
	public $exceptions=array(
		'127.0.0.1',
		'[::1]',
	);
	
	public function setUsername($username)
	{
		$this->username=$username;
		$this->updateAuthorizationHeader();
	}
	
	public function setPassword($password)
	{
		$this->password=$password;
		$this->updateAuthorizationHeader();
	}
	
	protected function updateAuthorizationHeader()
	{
		if($this->username && $this->password)
			$this->_headers['Proxy-Authorization']=$this->authType.' '.base64_encode($this->username.':'.$this->password);
	}
	
	protected function connect($host, $port, $ssl=false)
	{
		if(in_array($host, $this->exceptions))
			return parent::connect($host, $port, $ssl);
		return parent::connect($this->server, $this->port, $this->ssl);
	}
} 