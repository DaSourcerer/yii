<?php
/**
 * CHttpClient class file.
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
 * @property $headers CHeaderCollection|array A collection of headers added to each request
 * @property $connector CHttpClientConnector|array this client's connector
 * 
 * @author Da:Sourcerer <webmaster@dasourcerer.net>
 * @version $Id$
 * @package system.web
 */
class CHttpClient extends CApplicationComponent
{
	const CRLF="\r\n";
	const USER_AGENT_STRING='Mozilla/5.0 (compatible; yii/{version}; +http://yiiframework.com)';
	
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
	 * @var integer the maximum number of redirects to follow. Set to 0 in order to follow no redirects at all.
	 */
	public $maxRedirects=3;
		
	private $_headers;	
	private $_connector=array(
		'class'=>'CHttpClientConnector',
	);
	
	/**
	 * @see CApplicationComponent::init()
	 */
	public function init()
	{
		parent::init();

		if(!isset($this->_headers['User-Agent']))
			$this->_headers['User-Agent']=str_replace('{version}',Yii::getVersion(),self::USER_AGENT_STRING);
		
		if(!isset($this->_headers['Accept']))
			$this->_headers['Accept']='*/*';
	}
	
	/**
	 * Fetch a remote http resource from the given target by the given method
	 * 
	 * @param $request CHttpClientRequest|string
	 * @param $method string
	 * @return CHttpClientResponse
	 */
	public function fetch($request, $method=self::METHOD_GET)
	{
		if(!($request instanceof CHttpClientRequest))
			$request=new CHttpClientRequest($request,$method);		
		return $this->fetchInternal($request, $this->maxRedirects);
	}
	
	/**
	 * Perform the actual request and follow redirects
	 * 
	 * @param CHttpClientRequest $request
	 * @param integer $redirects
	 * @throws CException
	 */
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
				$request=CHttpClientRequest::fromRedirect($response);
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
	
	public function getHeaders()
	{
		return $this->_headers;
	}
	
	public function setConnector($connector)
	{
		$this->_connector=$connector;
	}
	
	public function getConnector()
	{
		if(is_array($this->_connector))
		{
			$this->_connector=Yii::createComponent($this->_connector);
			$this->_connector->init();
		}
		return $this->_connector;
	}
}

/**
 * CHttpClientMessage is the base class for all HTTP messages (i.e. requests and responses)  
 * 
 * @property $body string the body of this message. Might be empty for some request and response types.
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
	
	private $_body;

	public function setBody($body)
	{
		$this->_body=$body;
	}
	
	public function getBody()
	{
		return $this->_body;
	}
}

/**
 * CHttpClientResponse encapsulates a HTTP response.
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

/**
 * CHttpClientRequest holds a prepared HTTP request.
 * 
 * The attributes {@link requestUrl} and {@link method} are the most important ones
 * as they control where and how this request should be sent to.
 * 
 * By setting {@link requestUrl}, the passed URL will be briefly validated and broken up into its
 * components. The following attributes will automatically be filled with said components: {@link scheme},
 * {@link host}, {@link port}, {@link user}, {@link pass}, {@link path}, {@link query}, {@link fragment}
 * 
 * All of them can be changed freely. 
 * 
 * @property $requestUrl string the URL at which this request should be sent to
 * @property $scheme string the scheme of {@link requestUrl}. This should be either http or https as no other protocols
 * are supported. An exception will be risen if {@link requestUrl} fails to comply to that.
 * @property $host string the host part of {@link requestUrl}
 * @property $user string the username in case authorization is needed in order to access the remote resource. Take note that {@link pass} needs
 * to be set as well for this to have any effect.
 * @property $pass string the password in case authorization is needed in order to access the remote resource. Only works if {@link user} has been set, too.
 * @property $path string the path part of {@link requestUrl}
 * 
 * @author Da:Sourcerer <webmaster@dasourcerer.net>
 * @version $Id$
 * @package system.web
 */
class CHttpClientRequest extends CHttpClientMessage
{
	/**
	 * @var string the request method
	 */
	public $method=CHttpClient::METHOD_GET;
	
	/**
	 * @var integer the port to connect to
	 */
	public $port;
	
	/**
	 * @var string additional parameters passed via the query string
	 */
	public $query;
	
	/**
	 * @var string the fragment. This is only here for informational purposes.
	 */
	public $fragment;
	
	private $_requestUrl;
	private $_scheme;
	private $_host;
	private $_user;
	private $_pass;
	private $_path='/';
	
	public function __construct($requestUrl, $method=CHttpClient::METHOD_GET)
	{
		$this->requestUrl=$requestUrl;
		$this->method=$method;
	}
	
	public function setRequestUrl($url)
	{
		$parsedUrl=parse_url($url);
		if($parsedUrl===false)
			throw new CException('Malformed URL: '.$url);
		
		foreach($parsedUrl as $key=>$value)
			$this->$key=$value;
		$this->requestUrl=$url;
	}
	
	public function getRequestUrl()
	{
		return $this->_requestUrl;
	}
	
	public function setScheme($scheme)
	{
		if(!in_array($scheme, array('http', 'https')))
			throw new CException('Unsupported protocol: '.$scheme);
		$this->_scheme=$scheme;
	}
	
	public function getScheme()
	{
		return $this->_scheme;
	}
	
	public function setHost($host)
	{
		$this->_host=$host;
		if($this->httpVersion==1.1)
			$this->headers['Host']=$host;
	}
	
	public function getHost()
	{
		return $this->_host;
	}
	
	public function setUser()
	{
		$this->_user=$user;
		$this->updateAuthenticationHeader();
	}
	
	public function getUser()
	{
		return $this->_user;
	}
	
	public function setPass($pass)
	{
		$this->_pass=$pass;
		$this->updateAuthenticationHeader();
	}
	
	public function getPass()
	{
		return $this->_pass;
	}
	
	public function setPath($path)
	{
		$this->_path=$path;
		if(empty($this->_path))
			$this->_path='/';
	}
	
	public function getPath()
	{
		return $this->_path;
	}
	
	public function setBody($body)
	{
		if($this->method==CHttpClient::METHOD_GET)
			throw new CException("Cannot set body on a GET request");
		$this->_body=$body;
		$this->headers['Content-Length']=function_exists('mb_strlen')?mb_strlen($this->_body,Yii::app()->charset):strlen($this->_body);
	}
	
	/**
	 * Keep the authorization header in sync with {@link user} and {@link pass}.
	 */
	protected function updateAuthenticationHeader()
	{
		if($this->user && $this->pass)
			$this->headers['Authorization']='Basic '.base64_encode($this->user.':'.$this->pass);
	}
	
	/**
	 * Create a new request from a redirect response
	 * 
	 * @param CHttpClientResponse $response
	 * @return CHttpClientRequest
	 * @throws CException
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
class CHeaderCollection extends CMap {}

/**
 * CHttpClientConnector establishes network connectivity and does everything
 * to push and pull stuff over the wire.
 * 
 * @property $useConnectionPooling boolean controls if the connector should try to re-use existing
 * connections within a single script run. This is mostly useful for console
 * commands or if a proxy is being used. Please note that this will directly
 * effect the <code>Connection</code> HTTP header.
 * 
 * @author Da:Sourcerer <webmaster@dasourcerer.net>
 * @version $Id$
 * @package system.web
 */
class CHttpClientConnector extends CComponent
{
	/**
	 * @var string
	 */
	public $cacheID='cache';
	
	/**
	 * @var integer
	 */
	public $timeout=5;
	
	private $_useConnectionPooling=false;
	private $_cache;
	
	protected static $_connections=array();
	/**
	 * @var array a set of additional headers set and managed by this connector
	 */
	private $_headers=array();
	
	public function init()
	{
		if(in_array('dechunk', stream_get_filters()))
			$this->_headers['TE']='chunked';

		$encodings=array();
		if(extension_loaded('zlib'))
			array_push($encodings, 'gzip', 'x-gzip', 'deflate');
		if(extension_loaded('bz2'))
			array_push($encodings, 'bzip2', 'x-bzip2');
		
		if(!empty($encodings))
			$this->_headers['Accept-Encoding']=implode(', ', $encodings);
		
		
		$this->_headers['Connection']=$this->_useConnectionPooling?'keep-alive':'close';
	}
	
	public function setUseConnectionPooling($useConnectionPooling)
	{
		$this->_useConnectionPooling=$useConnectionPooling;
		$this->_headers['Connection']=$this->_useConnectionPooling?'keep-alive':'close';
	}
	
	public function getConnection(CHttpClientRequest $request)
	{
		$key='system.web.CHttpClientConnector.dns#'.$request->host;
		if(($host=$this->cache->get($key))===false)
		{
			if(defined(AF_INET6))
				$type=DNS_A|DNS_AAAA;
			else
				$type=DNS_A;
		
			$records=dns_get_record($request->host,$type);
			
			if(empty($records))
				throw new CException("Could not resolve host {$request->host} for URL {$request->requestUrl}");
			
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
	
	/**
	 * @return CCache
	 */
	public function getCache()
	{
		if($this->_cache===null)
		{
			$this->_cache=Yii::app()->getComponent($this->cacheID);
			// Fix for the console
			if($this->_cache===null)
				$this->_cache=new CDummyCache;
		}
		return $this->_cache;
	}
	
	/**
	 * 
	 * @param CHttpClientRequest $request
	 * @throws CException
	 * @return CHttpClientResponse
	 */
	public function perform(CHttpClientRequest $request)
	{
		$connection=$this->getConnection($request);
		
		$streamFilters=array();
		
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
		list($httpVersion, $status, $response->message) = explode(' ', fgets($connection), 3);
		sscanf($httpVersion, 'HTTP/%f', $respone->httpVersion);
		$response->status=intval($status);

		if($response->httpVersion>0.9)
		{
			$line='';
			while(($line=fgets($connection))!==false && $line!=CHttpClient::CRLF && !feof($connection))
			{
				@list($header,$content)=explode(':',$line);
				$response->headers[$header]=trim($content);
			}
			
			if(isset($response->headers['Transfer-Encoding']) && $response->headers['Transfer-Encoding']=='chunked')
				$streamFilters[]=stream_filter_append($connection, 'dechunk', STREAM_FILTER_READ);
		}
		
		while(!feof($connection))
			$response->body.=fgets($connection);

		if(isset($response->headers['Content-Encoding']))
		{
			switch($response->headers['Content-Encoding'])
			{
				case 'gzip':
				case 'x-gzip':
					$response->body=gzdecode($response->body);
					break;
				case 'bzip2':
				case 'x-bzip2':
					$response->body=bzdecompress($response->body);
					break;
				case 'deflate':
                    // Is this really DEFLATE? Some servers seem to advertise RFC 1952 encoded data here, so let's check
                    // for a zlib header first.
                    if(ord($response->body[0]) == 0x78 && in_array(ord($response->body[1]), array(0x01, 0x5e, 0x9c, 0xda)))
					    $response->body=gzuncompress($response->body);
                    else
                        $response->body=gzdecode($response->body);
					break;
				case 'identity';
					break;
				default:
					throw new CException('Unknown content encoding: '.$response->headers['Content-Encoding']);
			}
		}
		
		foreach($streamFilters as $streamFilter)
			stream_filter_remove($streamFilter);
		
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
		if($this->_useConnectionPooling)
		{
			$key=$ssl.':'.$host.':'.$port;
			if(!isset(self::$_connections[$key]))
			{
				$connection=@fsockopen(($ssl?'ssl':'tcp').'://'.$host, $port, $errno, $errstr, $this->timeout);
				if($connection===false)
					throw new CException("Failed to connect to {$host} ({$errno}): {$errstr}");
				self::$_connections[$key]=$connection;
			}
			return self::$_connections[$key];
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
	public $authType='Basic';
	public $ssl=false;
	
	private $_username;
	private $_password;
	
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
	
	public function getUsername()
	{
		return $this->_username;
	}
	
	public function setPassword($password)
	{
		$this->password=$password;
		$this->updateAuthorizationHeader();
	}
	
	public function getPassword()
	{
		return $this->_password;
	}
	
	protected function updateAuthorizationHeader()
	{
		if($this->username && $this->password)
			$this->_headers['Proxy-Authorization']=$this->authType.' '.base64_encode($this->username.':'.$this->password);
	}
	
	protected function craftRequestLine(CHttpClientRequest $request)
	{
		$requestUrl= $request->scheme.'://'.$request->host;
		if($request->port)
			$requestUrl.=':'.$request->port;
		$requestUrl.=$request->path;
		if($request->query)
			$requestUrl.='?'.$request->query;
		return sprintf('%s %s HTTP/%.1f', $request->method, $requestUrl, $request->httpVersion);
	}
	
	protected function connect($host, $port, $ssl=false)
	{
		if(in_array($host, $this->exceptions))
			return parent::connect($host, $port, $ssl);
		return parent::connect($this->server, $this->port, $this->ssl);
	}
} 