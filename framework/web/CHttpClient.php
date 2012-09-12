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
 * @property $connector CHttpClientConnector this client's connector
 * 
 * @author Da:Sourcerer <webmaster@dasourcerer.net>
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

	/**
	 * @var array A collection of headers added to each request
	 */
	public $headers;

	private $_connector=array(
		'class'=>'CHttpClientConnector',
	);
	
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
	 * Perform the actual request by delegating it to the connector
	 * and follow possible redirects
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

	/**
	 * Issue a GET request at the location specified by <code>$request</code>
	 * This is a convenience method for {@link fetch}
	 * @param CHttpClientRequest|string $request
	 * @return CHttpClientResponse
	 * @see fetch
	 */
	public function get($request)
	{
		return $this->fetch($request);
	}

	/**
	 * Issue a HEAD request at the location specified by <code>$request</code>
	 * This is a convenience method for {@link fetch}
	 * @param CHttpClientRequest|string $request
	 * @return CHttpClientResponse
	 * @see fetch
	 */
	public function head($request)
	{
		return $this->fetch($request, self::METHOD_HEAD);
	}

	/**
	 * Issue a POST request at the location specified by <code>$request</code>
	 * This is a convenience method for {@link fetch}
	 * @param CHttpClientRequest|string $request
	 * @return CHttpClientResponse
	 * @see fetch
	 */
	public function post($request)
	{
		return $this->fetch($request, self::METHOD_POST);
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
	 * @var array a collection of cookies
	 */
	public $cookies=array();
	
	private $_body;

	public function setBody($body)
	{
		$this->_body=$body;
	}
	
	public function getBody()
	{
		return $this->_body;
	}

	public function __construct()
	{
		$this->headers= new CHeaderCollection;
	}
}

/**
 * CHttpClientResponse encapsulates a HTTP response.
 * 
 * @author Da:Sourcerer <webmaster@dasourcerer.net>
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

	/**
	 * Check if this response object carries a status code indicating a HTTP redirect
	 * @return boolean
	 */
	public function isRedirect()
	{
		return ($this->status>=301&&$this->status<=303);
	}
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
	
	public function __construct($requestUrl=null, $method=CHttpClient::METHOD_GET)
	{
		if(!is_null($requestUrl))
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
		$result=$this->scheme.'://';
		if(!empty($this->_user))
		{
			$result.=$this->_user;
			if(!empty($this->_pass))
				$result.=':'.$this->_pass;
			$result.='@';
		}
		$result.=$this->_host;
		if(!empty($this->port))
			$result.=':'.$this->port;
		$result.=$this->_path;
		if(!empty($this->query))
			$result.='?'.$this->query;
		if(!empty($this->fragment))
			$result.='#'.$this->fragment;
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
	
	public function setUser($user)
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
		if($this->_path[0]!='/')
			$this->_path='/'.$this->_path;
	}
	
	public function getPath()
	{
		return $this->_path;
	}
	
	public function setBody($body)
	{
		if($this->method==CHttpClient::METHOD_GET)
			throw new CException("Cannot set body on a GET request");
		$this->body=$body;
		$this->headers['Content-Length']=function_exists('mb_strlen')?mb_strlen($this->body,Yii::app()->charset):strlen($this->body);
	}
	
	/**
	 * Keep the authorization header in sync with {@link user} and {@link pass}.
	 */
	protected function updateAuthenticationHeader()
	{
		if(!empty($this->_user) && !empty($this->_pass))
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
		
		$request=new CHttpClientRequest($response->headers['Location']);
		$request->cookies=$response->cookies;
		return $request;
	}

	public function __toString()
	{
		$requestUrl=$this->path;
		if($this->query)
			$requestUrl.='?'.$this->query;
		$result=sprintf('%s %s HTTP/%.1f', strtoupper($this->method), $requestUrl, $this->httpVersion).CHttpClient::CRLF;
		foreach($this->headers as $header=>$value)
			$result.="{$header}: {$value}".CHttpClient::CRLF;
		foreach($this->cookies as $cookie)
			$result.='Cookie: '.$cookie.CHttpClient::CRLF;
		$result.=CHttpClient::CRLF;
		if(!empty($this->body))
			$result.=$this->body;
		$result.=CHttpClient::CRLF;
		return $result;
	}
}

/**
 * CHeaderCollection
 *
 * @author Da:Sourcerer <webmaster@dasourcerer.net>
 * @package system.web
 */
class CHeaderCollection extends CMap {}

/**
 * Base class for all connectors
 *
 * Connectors establish http connections and do their part in resolving host names,
 * issuing requests and parsing the results.
 *
 * Please take note that the capabilities of different connectors might vary: They are free to advertise different
 * capabilities to servers and modify requests to their liking. They are thus not easily interchangeable.
 *
 * @property $cache CCache
 *
 * @author Da:Sourcerer <webmaster@dasourcerer.net>
 * @package system.web
 */
abstract class CBaseHttpClientConnector extends CComponent
{
	/**
	 * @var integer
	 */
	public $timeout=5;

	/**
	 * @var string
	 */
	public $cacheID='cache';
	private $_cache;

	/**
	 * Perform the actual HTTP request and return the response
	 *
	 * @param CHttpClientRequest $request
	 * @throws CException
	 * @return CHttpClientResponse
	 */
	abstract function perform(CHttpClientRequest $request);

	/**
	 * @return CCache
	 * @see cacheID
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
}

/**
 * CHttpClientConnector establishes network connectivity and does everything
 * to push and pull stuff over the wire.
 * 
 * @property $useConnectionPooling boolean controls if the connector should try to re-use existing
 * connections within a single script run. This is mostly useful for console
 * commands or if a proxy is being used. Please note that this will directly
 * effect the <code>Connection</code> HTTP header.
 *
 *
 * @author Da:Sourcerer <webmaster@dasourcerer.net>
 * @package system.web
 */
class CHttpClientConnector extends CBaseHttpClientConnector
{
	private $_useConnectionPooling=false;
	private $_streamContext;

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

		$this->_streamContext=stream_context_create();
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
			
			$this->cache->set($key, $host, $records[0]['ttl']);
		}
		
		$port=80;
		if(isset($request->port))
			$port=$request->port;
		else if($request->scheme=='https')
			$port=443;
		
		return $this->connect($host, $port, $request->scheme=='https');
	}

	public function perform(CHttpClientRequest $request)
	{
		$connection=$this->getConnection($request);
		
		$streamFilters=array();
		$request->headers=CMap::arrayMerge($request->headers, $this->_headers);
		$requestString=(string)$request;
		$requestStringLength=(function_exists('mb_strlen')?mb_strlen($requestString,Yii::app()->charset):strlen($requestString));
		$written=fwrite($connection, $requestString);
				
		if($written!=$requestStringLength)
			Yii::log("Wrote {$written} instead of {$requestStringLength} bytes to stream - possible network error", 'notice', 'system.web.CHttpClientConnector');
		
		$response=new CHttpClientResponse;
		list($httpVersion, $status, $response->message) = explode(' ', fgets($connection), 3);
		sscanf($httpVersion, 'HTTP/%f', $response->httpVersion);
		$response->status=intval($status);

		if($response->httpVersion>0.9)
		{
			$line='';
			while(($line=fgets($connection))!==false && $line!=CHttpClient::CRLF && !feof($connection))
			{
				@list($header,$content)=explode(':',$line);
				$content=trim($content);
				if(strtolower($header)=='set-cookie')
					$response->cookies[]=$content;
				else
					$response->headers[$header]=$content;
			}
			
			if(isset($response->headers['Transfer-Encoding']) && $response->headers['Transfer-Encoding']=='chunked')
				$streamFilters[]=stream_filter_append($connection, 'dechunk', STREAM_FILTER_READ);
		}
		
		while(!feof($connection))
			$response->body.=fgets($connection);

		if(isset($response->headers['Content-Encoding']))
		{
			switch(strtolower($response->headers['Content-Encoding']))
			{
				case 'gzip':
				case 'x-gzip':
					$response->body=$this->gzdecode($response->body);
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
						$response->body=$this->gzdecode($response->body);
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
	
	protected function connect($host, $port, $ssl=false)
	{
		if($this->_useConnectionPooling)
		{
			$key=$ssl.'/'.$host.'/'.$port;
			if(!isset(self::$_connections[$key]) || !is_resource(self::$_connections[$key]))
			{
				$connection=@stream_socket_client(($ssl?'ssl':'tcp').'://'.$host, $port, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $this->_streamContext);
				if($connection===false)
					throw new CException("Failed to connect to {$host} ({$errno}): {$errstr}");
				self::$_connections[$key]=$connection;
			}
			return self::$_connections[$key];
		}

		$connection=@stream_socket_client(($ssl?'ssl':'tcp').'://'.$host, $port, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $this->_streamContext);
		if($connection===false)
			throw new CException("Failed to connect to {$host} ({$errno}): {$errstr}");
		return $connection;
	}

	protected function gzdecode($data)
	{
		if(function_exists('gzdecode'))
			return gzdecode($data);
		else
			return gzinflate(substr($data,10));
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
		$this->_username=$username;
		$this->updateAuthorizationHeader();
	}
	
	public function getUsername()
	{
		return $this->_username;
	}
	
	public function setPassword($password)
	{
		$this->_password=$password;
		$this->updateAuthorizationHeader();
	}
	
	public function getPassword()
	{
		return $this->_password;
	}
	
	protected function updateAuthorizationHeader()
	{
		if(!empty($this->_username) && !empty($this->_password))
			$this->_headers['Proxy-Authorization']=$this->authType.' '.base64_encode($this->_username.':'.$this->_password);
	}
	
	protected function connect($host, $port, $ssl=false)
	{
		if(in_array($host, $this->exceptions))
			return parent::connect($host, $port, $ssl);
		return parent::connect($this->server, $this->port, $this->ssl);
	}
} 