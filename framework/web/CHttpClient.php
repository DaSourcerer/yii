<?php
/**
 * CHttpClient class file.
 *
 * @author Da:Sourcerer <webmaster@dasourcerer.net>
 * @link http://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
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
 * @since 1.1.15
 */
class CHttpClient extends CApplicationComponent {
	/**
	 * HTTP line terminator
	 */
	const CRLF="\r\n";

	/**
	 * The default user agent string CHttpCLient is using in order to identify itself
	 */
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
	 * @var array a set of headers added to each request
	 */
	public $headers=array();

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
	}

	/**
	 * Prepare a HTTP GET request
	 *
	 * GET requests are the most common ones. They are used to fetch a remote resource.
	 * This type of request is supposed to be idempotent, i.e. issueing a GET request should not change the state of the
	 * targeted web application, hence consequent requests should result into the same response.
	 *
	 * @param CHttpClientRequest|string $request
	 * @return CHttpClientRequest
	 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.3
	 */
	public function get($request)
	{
		if(is_string($request))
			$request=new CHttpClientRequest($request);
		$request->method=self::METHOD_GET;
		return $request;
	}

	/**
	 * Prepare a HTTP HEAD request
	 *
	 * HEAD requests are used in order to fetch only the headers a regular response to a GET request would contain sans
	 * the body. Informations retrieved through this method are notoriously unreliable and should be treated with care.
	 * Much like GET requests, HEAD requests are supposed to be idempotent.
	 *
	 * @param CHttpClientRequest|string $request
	 * @return CHttpClientRequest
	 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4
	 */
	public function head($request)
	{
		if(is_string($request))
			$request=new CHttpClientRequest($request);
		$request->method=self::METHOD_HEAD;
		return $request;
	}

	/**
	 * Prepare a HTTP POST request
	 *
	 * POST requests are used to modify the state of a web application by passing a set of variables and/or a set of
	 * files to a specified resource intended to process the body of the request.
	 *
	 * @param CHttpClientRequest|string $request
	 * @param mixed $body
	 * @param string $mimeType
	 * @return CHttpClientRequest
	 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.5
	 */
	public function post($request, $body=null, $mimeType=null)
	{
		if(is_string($request))
			$request=new CHttpClientRequest($request);
		$request->method=self::METHOD_POST;
		return $request;
	}

	/**
	 * Prepare a HTTP PUT request
	 *
	 * PUT requests are quite rare, usually issued towards ReSTful webapplications or used through the HTTP transports
	 * of version control systems such as Mercurial, git, or Subversion. They work quite similar to POST requests with a
	 * significant semantic change: Instead of specifying an entity responsible for the processing of the requests body,
	 * the given URL is pointing at the resource that is supposed to be either altered (i.e. replaced) or created.
	 *
	 * @param CHttpClientRequest|string $request
	 * @param mixed $body
	 * @param string $mimeType
	 * @return CHttpClientRequest
	 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.6
	 */
	public function put($request, $body=null, $mimeType=null)
	{
		if(is_string($request))
			$request=new CHttpClientRequest($request);
		$request->method=self::METHOD_PUT;
		return $request;
	}

	/**
	 * Prepare a HTTP DELETE request
	 *
	 * DELETE requests instruct a webserver or webapplication to remove the resource at the given URL. The context in
	 * which they are being used is very much the same as the one for PUT requests.
	 *
	 * @param CHttpClientRequest|string $request
	 * @return CHttpClientRequest
	 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.7
	 */
	public function delete($request)
	{
		if(is_string($request))
			$request=new CHttpClientRequest($request);
		$request->method=self::METHOD_DELETE;
		return $request;
	}

	/**
	 * Send out a prepared request or prepare one and send it out via the connector.
	 *
	 * @param array|CHttpClientRequest $request the request to be sent out
	 * @return CHttpClientResponse the response to the sent request
	 */
	public function send($request)
	{
		if(is_array($request))
		{
			$r=new CHttpClientRequest;
			foreach($request as $key=>$value)
				$r->$key=$value;
			$request=$r;
		}
		$request->headers->mergeWith($this->headers);
		return $this->connector->send($request);
	}

	/**
	 * Set the connector
	 *
	 * @param array|CBaseHttpClientConnector $connector
	 */
	public function setConnector($connector)
	{
		$this->_connector=$connector;
	}

	/**
	 * Get the connector
	 *
	 * @return CBaseHttpClientConnector
	 */
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
 * @property CHttpMessageBody $body string the body of this message. Might be empty for some request and response types.
 * @property CHeaderCollection $headers a collection of headers associated with this message.
 *
 * @author Da:Sourcerer <webmaster@dasourcerer.net>
 * @package system.web
 * @since 1.1.15
 */
abstract class CHttpClientMessage extends CComponent
{
	/**
	 * @var CHeaderCollection
	 */
	private $_headers;

	/**
	 * @var CHttpMessageBody
	 */
	private $_body;

	/**
	 * @var float The http protocol version associated with this message. Make
	 * sure this is either 0.9, 1.0 or 1.1, as there won't be any validation
	 * for this. If you access this on a response object, don't be surprised to
	 * find some odd values.
	 */
	public $httpVersion=1.1;

	/**
	 * Set the set of headers associated with this message.
	 * @param CHeaderCollection|array $headers
	 */
	public function setHeaders($headers)
	{
		if(is_array($headers))
			$this->_headers=new CHeaderCollection($headers);
		else
			$this->_headers=$headers;
	}

	/**
	 * Get the set of headers associated with this message.
	 * @return CHeaderCollection
	 */
	public function getHeaders()
	{
		if(!$this->_headers)
			$this->_headers=new CHeaderCollection;
		return $this->_headers;
	}

	/**
	 * Set the body of this message.
	 * @param CHttpMessageBody $body
	 */
	public function setBody(CHttpMessageBody $body)
	{
		$this->_body=$body;
	}

	/**
	 * Get the body of this message.
	 * @return CHttpMessageBody
	 */
	public function getBody()
	{
		if(!$this->_body)
			$this->_body=new CHttpMessageBody;
		return $this->_body;
	}
}

/**
 * CHttpMessageBody class
 *
 * @property resource stream
 * @property CHeaderCollection $headers
 *
 * @author Da:Sourcerer <webmaster@dasourcerer.net>
 * @package system.web
 * @since 1.1.15
 */
class CHttpMessageBody extends CComponent
{
	/**
	 * @var resource
	 */
	private $_stream;

	/**
	 * @var CHeaderCollection
	 */
	private $_headers;

	/**
	 * @param mixed $content
	 * @param string $mimeType
	 */
	public function __construct($content=null, $mimeType=null)
	{
		if($content)
		{
			if($mimeType==null&&$content!=null)
			{
				if(is_string($content))
				{
					fwrite($this->stream,$content);
					$this->headers->add('Content-Type','text/plaintext');
				}
				elseif(is_stream($content))
				{
					$this->_stream=$content;
					$this->headers->add('Content-Type','text/plaintext');
				}
				elseif(is_array($content))
				{
					fwrite($this->stream,http_build_query($content));
					$this->headers->add('Content-Type','application/x-www-form-urlencoded');
				}
				elseif(is_object($content))
				{
					switch(get_class($content))
					{
						case 'DOMDocument':
							fwrite($this->stream,$content->saveXML());
							if($content->encoding)
								$this->headers->add('Content-Type','application/xml; charset='.$content->encoding);
							else
								$this->headers->add('Content-Type','application/xml');
							break;
						default:
							Yii::log(Yii::t('yii','Unknown class {class} - attempting to send as plain text',array('{class}'=>get_class($content))),CLogger::LEVEL_INFO,'system.web.CHttpMessageBody');
							fwrite($this->stream,(string)$content);
							$this->headers->add('Content-Type','text/plaintext');
							break;
					}
				}
			}
			else
			{
			}
		}
	}

	/**
	 * @return resource
	 */
	public function getStream()
	{
		if(!$this->_stream)
			$this->_stream=fopen('php://temp','rb+');
		return $this->_stream;
	}

	/**
	 * @param resource $stream
	 */
	public function setStream($stream)
	{
		$this->_stream=$stream;
	}

	/**
	 * @param CHeaderCollection $headers
	 */
	public function setHeaders(CHeaderCollection $headers)
	{
		$this->_headers=$headers;
	}

	/**
	 * @return CHeaderCollection
	 */
	public function getHeaders()
	{
		if(!$this->_headers)
			$this->_headers=new CHeaderCollection;
		return $this->_headers;
	}
}

/**
 * CHttpClientResponse encapsulates a HTTP response.
 *
 * @author Da:Sourcerer <webmaster@dasourcerer.net>
 * @package system.web
 * @since 1.1.15
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
	 * Check if this response object is informational
	 *
	 * @return boolean
	 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.1
	 */
	public function isInformational()
	{
		return $this->status>=100&&$this->status<200;
	}

	/**
	 * Check if this response has been successful
	 *
	 * @return boolean
	 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.2
	 */
	public function isSuccessful()
	{
		return $this->status==304||$this->status>=200&&$this->status<300;
	}

	/**
	 * Check if this response object carries a status code indicating a HTTP redirect
	 *
	 * @return boolean
	 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.3
	 */
	public function isRedirect()
	{
		return $this->status!=304&&$this->status>=300&&$this->status<400;
	}

	/**
	 * Check if the server reported an error in response to a faulty or illegal request
	 *
	 * @return boolean
	 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.4
	 */
	public function isClientError()
	{
		return $this->status>=400&&$this->status<500;
	}

	/**
	 * Check if the server failed to deliver a response due to problems on his side
	 *
	 * @return boolean
	 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.1
	 */
	public function isServerError()
	{
		return $this->status>=500&&$this->status<600;
	}

	/**
	 * Check if the server reported an error due to a either a client <strong>or</strong>
	 * a server error
	 *
	 * @return boolean
	 */
	public function isError()
	{
		return $this->isClientError()||$this->isServerError();
	}

	/**
	 * Check if this response carries an unrecognized response code
	 *
	 * @return boolean
	 */
	public function isUnknown()
	{
		if($this->isInformationa())
			return false;
		if($this->isSuccessful())
			return false;
		if($this->isRedirect())
			return false;
		if($this->isError())
			return false;
		return true;
	}
}

/**
 * CHttpClientRequest holds a prepared HTTP request.
 *
 * The attributes {@link url} and {@link method} are the most important ones
 * as they control where and how this request should be sent to.
 *
 * @property CUrl $url
 *
 * @author Da:Sourcerer <webmaster@dasourcerer.net>
 * @package system.web
 * @since 1.1.15
 */
class CHttpClientRequest extends CHttpClientMessage
{
	/**
	 * @var CUrl
	 */
	public $url=new CUrl;

	/** @var string */
	public $method=CHttpClient::METHOD_GET;
	/** @var CHttpClient */
	public $client;

	/**
	 * @return CHttpClientResponse
	 */
	public function send()
	{
		if($this->client)
			return $this->client->send($this);
		else
			return Yii::app()->http->send($this);
	}

	public function __construct($url=null, $method=CHttpClient::METHOD_GET)
	{
		$this->url=$url;
		$this->method=$method;
	}

}

/**
 * CHeaderCollection class
 *
 * CHeaderCollection works largely just like {@link CMap} with the exception that all keys are converted to lower case
 * internally. Also, {@link add} does not overwrite existing values. If you wish to overwrite a value, use {@link set}
 * instead.
 *
 * @author Da:Sourcerer <webmaster@dasourcerer.net>
 * @package system.web
 * @since 1.1.15
 */
class CHeaderCollection extends CMap {

	/**
	 * @param mixed $key
	 * @param mixed $value
	 * @see CMap::add
	 */
	public function add($key,$value)
	{
		$key=strtolower($key);
		if($this->contains($key))
			parent::add($key,array_merge((array)$this->itemAt($key),(array)$value));
		else
			parent::add(strtolower($key),$value);
	}

	/**
	 * @param $key
	 * @param $value
	 * @see CMap::add
	 */
	public function set($key,$value)
	{
		parent::add(strtolower($key),$value);
	}

	/**
	 * @param mixed $key
	 * @return mixed
	 * @see CMap::add
	 */
	public function itemAt($key)
	{
		return parent::itemAt(strtolower($key));
	}

	/**
	 * @param mixed $key
	 * @return mixed
	 * @see CMap::remove
	 */
	public function remove($key)
	{
		return parent::remove(strtolower($key));
	}

	/**
	 * @param mixed $key
	 * @return bool
	 * @see CMap::contains
	 */
	public function contains($key)
	{
		return parent::contains(strtolower($key));
	}

	public function __toString()
	{
		$result='';
		foreach($this->toArray() as $name=>$values)
		{
			$name=implode('-',array_map('ucfirst',explode('-',$name)));
			$values=(array)$values;
			foreach($values as $value)
			{
				$result.=$name.': '.$value.CHttpClient::CRLF;
			}
		}
		return $result.CHttpClient::CRLF;
	}
}

/**
 * CUrl is an object for URL parsing and manipulation. It is in no way related to the {@link http://curl.haxx.se cURL} library
 *
 * @property string $host
 * @property string $user
 * @property string $pass
 * @property integer $port
 * @property string $path
 * @property string $query
 *
 * @author Da:Sourcerer <webmaster@dasourcerer.net>
 * @package system.web
 * @since 1.1.15
 */
class CUrl extends CComponent
{
	const COMPONENT_SCHEME=0x01;
	const COMPONENT_USER=0x02;
	const COMPONENT_PASS=0x04;
	const COMPONENT_AUTH=0x06; // user+pass
	const COMPONENT_HOST=0x08;
	const COMPONENT_PORT=0x10;
	const COMPONENT_PATH=0x20;
	const COMPONENT_QUERY=0x40;
	const COMPONENT_FRAGMENT=0x80;

	public $scheme;
	public $params=array();
	public $fragment;

	private $_host;
	private $_user;
	private $_pass;
	private $_port;
	private $_path='/';

	private static $_componentMap=array(
		self::COMPONENT_SCHEME=>'scheme',
		self::COMPONENT_USER=>'user',
		self::COMPONENT_PASS=>'pass',
		self::COMPONENT_HOST=>'host',
		self::COMPONENT_PORT=>'port',
		self::COMPONENT_PATH=>'path',
		self::COMPONENT_QUERY=>'query',
		self::COMPONENT_FRAGMENT=>'fragment',
	);

	/**
	 * @param mixed $url
	 * @throws CException
	 */
	public function __construct($url)
	{
		if($url instanceof self)
			$url=$url->toArray();
		elseif(is_string($url))
		{
			//@todo Although parse_url() is pretty battle-hardened, there could be better ways to parse this.
			//PEAR::Net_URL2 is using a regex supposedly copied from RFC 3986, Appendix B
			if(($parsedUrl=@parse_url($url))===false)
				throw new CException(Yii::t('Failed to parse URL {url}',array('{url}'=>$url)));
			$url=$parsedUrl;
		}

		if(is_array($url))
		{
			foreach($url as $key=>$value)
				$this->$key=$value;
		}
	}

	public function setHost($host)
	{
		//@todo create a single instance of idna_convert and reuse that instead of creating a new instance on every call
		require_once(Yii::getPathOfAlias('system.vendors.Net_IDNA2.Net').DIRECTORY_SEPARATOR.'IDNA2.php');
		$idna=new Net_IDNA2();
		$this->_host=$idna->encode($host);
	}

	public function getHost()
	{
		return $this->_host;
	}

	public function setUser($user)
	{
		$this->_user=rawurldecode($user);
	}

	public function getUser()
	{
		return $this->_user;
	}

	public function setPass($pass)
	{
		$this->_pass=rawurldecode($pass);
	}

	public function getPass()
	{
		return $this->_pass;
	}

	public function setPort($port)
	{
		if($port!=getservbyname($this->scheme,'tcp'))
			$this->_port=$port;
	}

	public function getPort()
	{
		return $this->_port;
	}

	public function setPath($path)
	{
		//@todo normalize path. See http://svn.php.net/viewvc/pear/packages/Net_URL2/trunk/Net/URL2.php?revision=309223&view=markup#624
		//Also: RFC 3986, sec 6.2.2ff
		$this->_path=$path;
	}

	public function getPath()
	{
		return $this->_path;
	}

	public function setQuery($query)
	{
		if(is_array($query))
			$this->params=$query;
		else
			//@todo parse_str() is doing some unhealthy things. Replace with userland function.
			parse_str($query,$this->params);
	}

	public function getQuery()
	{
		return http_build_query($this->params);
	}

	public function strip($bitmap)
	{
		$components=$this->toArray();
		foreach(self::$_componentMap as $key=>$component)
		{
			if($key&$bitmap)
				unset($components[$component]);
		}
		return new CUrl($components);
	}

	public function filter($bitmap=0x00)
	{
		$components=$this->toArray();
		foreach(self::$_componentMap as $key=>$component)
		{
			if(!($key&$bitmap))
				unset($components[$component]);
		}
		return new CUrl($components);
	}

	public function toArray()
	{
		return array(
			'scheme'=>$this->scheme,
			'user'=>$this->_user,
			'pass'=>$this->_pass,
			'host'=>$this->_host,
			'port'=>$this->_port,
			'path'=>$this->_path,
			'query'=>$this->query,
			'fragment'=>$this->fragment,
		);
	}

	public function __toString()
	{
		$result='';
		$components=$this->toArray();
		if(isset($components['scheme']) && !empty($components['scheme']));
			$result.=$components['scheme'].'://';
		if(isset($components['user']) && !empty($components['user']))
		{
			$result.=rawurlencode($components['user']);
			if(isset($components['pass']) && !empty($components['pass']))
				$result.=':'.rawurlencode($components['pass']);
			$result.='@';
		}
		if(isset($components['host']) && !empty($components['host']))
			$result.=$components['host'];
		if(isset($components['path']) && !empty($components['path']))
		{
			$pathComponents=explode('/',$components['path']);
			$path=array();
			foreach($pathComponents as &$pathComponent)
				if(empty($pathComponent))
					continue;
				else
					$path[]=rawurlencode($pathComponent);
			$path=implode('/',$path);
			if($path{0}!='/')
				$path='/'.$path;
			$result.=$path;
		}
		else if((isset($components['query']) && !empty($components['query'])) || (isset($components['fragment']) && !empty($components['fragment'])))
			$result.='/';
		if(isset($components['query']) && !empty($components['query']))
		{
			$result.='?'.$components['query'];
		}
		if(isset($components['fragment']) && !empty($components['fragment']))
			$result.='#'.$components['fragment'];
		return $result;
	}
}

/**
 * Base class for all connectors
 *
 * Connectors establish http connections and do their part in resolving host names,
 * issuing requests and parsing the results.
 *
 * Please take note that the capabilities of different connectors might vary: They are free to advertise different
 * capabilities to servers and modify requests to their liking. They are thus not easily interchangeable.
 *
 * @author Da:Sourcerer <webmaster@dasourcerer.net>
 * @package system.web
 * @since 1.1.15
 */
abstract class CBaseHttpClientConnector extends CComponent
{
	/**
	 * @var integer
	 */
	public $timeout=5;

	/**
	 * Perform the actual HTTP request and return the response
	 *
	 * @param CHttpClientRequest $request
	 * @throws CException
	 * @return CHttpClientResponse
	 */
	abstract function send(CHttpClientRequest $request);
}

/**
 * CHttpClientConnector establishes network connectivity and does everything
 * to push and pull stuff over the wire.
 *
 * @author Da:Sourcerer <webmaster@dasourcerer.net>
 * @package system.web
 * @since 1.1.15
 */
class CHttpClientConnector extends CBaseHttpClientConnector
{
	/**
	 * @var array options for connections with SSL peers.
	 * See http://www.php.net/manual/en/context.ssl.php
	 */
	public $ssl=array();

	public $persistent=true;

	private $_streamContext;

	/**
	 * @var array a set of additional headers set and managed by this connector
	 */
	private $_headers=array(
		'TE'=>'chunked, trailers',
	);

	public function init()
	{
		$supportedEncodings=array();
		if(extension_loaded('zlib'))
			array_push($supportedEncodings, 'gzip', 'deflate');

		if(extension_loaded('bz2'))
			$supportedEncodings[]='bzip2';

		if(!empty($supportedEncodings))
			$this->_headers['Accept-Encoding']=implode(', ',$supportedEncodings);

		stream_filter_register('yiidechunk','CDechunkFilter');
	}

	public function getStreamContext()
	{
		if($this->_streamContext===null)
		{
			$this->_streamContext=stream_context_create();
			if(!empty($this->proxy))
			{
				$proxy=new CUrl(array_merge(array(
					'scheme'=>'tcp',
					'host'=>'localhost',
					'port'=>8080,
				),$this->proxy));

			}

			foreach($this->ssl as $option=>$value)
			{
				if(!stream_context_set_option($this->_streamContext, 'ssl', $option, $value))
					throw new CException(Yii::t('yii','Failed to set SSL option {option}', array('{option}'=>$option)));
			}
			if(!isset($this->ssl['cafile']))
				stream_context_set_option($this->_streamContext, 'ssl', 'cafile', Yii::getPathOfAlias('system.web').'cacert.pem');
		}
		return $this->_streamContext;
	}

	public function getConnection(CUrl $url)
	{
		$url=$url->filter(CUrl::COMPONENT_SCHEME|CUrl::COMPONENT_HOST|Curl::COMPONENT_PORT);

		if($url->scheme=='https')
		{
			$url->scheme='ssl';
			if(!isset($url->port))
				$url->port=443;
		}
		else
		{
			$url->scheme='tcp';
			if(!isset($url->port))
				$url->port=80;
		}

		$flags=STREAM_CLIENT_CONNECT;
		if($this->persistent)
			$flags|=STREAM_CLIENT_PERSISTENT;

		$connection=@stream_socket_client($url, $errno, $errstr, $this->timeout, $flags, $this->streamContext);
		if($connection===false)
			throw new CException("Failed to connect to {$url} ({$errno}): {$errstr}");
		return $connection;
	}

	public function send(CHttpClientRequest $request)
	{
		$connection=$this->getConnection($request->url);

		$request->headers->mergeWith($this->_headers);
		$this->write($connection, $request);

		$response=$this->readResponse($connection);

		return $response;
	}

	/**
	 * @param $connection
	 * @return CHttpClientResponse
	 * @throws CException
	 */
	protected function readResponse($connection)
	{
		$response=new CHttpClientResponse;
		if(!($statusLine=@fgets($connection)))
			throw new CException(Yii::t('yii','Failed to read from connection'));

		if(strpos($statusLine, 'HTTP/')!==0)
		{
			Yii::log(Yii::t('yii','Received non-http/1.x response line - assuming HTTP/0.9'),CLogger::LEVEL_WARNING,'system.web.CHttpClientConnector');
			$response->httpVersion=0.9;
			$response->status=200;

			fwrite($response->body->stream,$statusLine);
			stream_copy_to_stream($connection,$response->body->stream);

			return $response;
		}

		$statusLine=substr($statusLine, 5);
		@list($response->httpVersion, $response->status, $response->message)=preg_split('[ \t]+',$statusLine,3);
		$response->httpVersion=(float)$response->httpVersion;
		$response->status=(int)$response->status;

		$headers='';
		while(($line=fgets($connection))!==false && !feof($connection) && trim($line)!='')
			$headers.=$line;

		//Per RFC2616, sec 19.3, we are required to treat \n like \r\n
		$headers=str_replace("\r\n","\n",$headers);
		//Unfold headers
		$headers=preg_replace('/\n[ \t]+/', ' ', $headers);
		$headers=explode("\n", $headers);

		foreach($headers as $line)
		{
			@list($header, $value)=explode(':', $line, 2);
			$response->headers->add(trim($header), trim($value));
		}

		$filters=array();
		$trailers='';

		if(isset($response->headers['Transfer-Encoding'])&&strtolower($response->headers['TransferEncoding'])=='chunked')
			$filters[]=stream_filter_append($connection,'yiidechunk',STREAM_FILTER_READ,array('trailers'=>$trailers));

		if(isset($response->headers['Content-Encoding']))
		{
			switch(strtolower($response->headers['Content-Encoding']))
			{
				case 'identity':
					break;
				case 'bzip2':
					$filters[]=stream_filter_append($connection,'bzip2.decompress',STREAM_FILTER_READ);
					break;
				case 'gzip':
					fseek($connection,10,SEEK_CUR);
				case 'deflate':
					$filters[]=stream_filter_append($connection,'zlib.inflate',STREAM_FILTER_READ);
					break;
				default:
					Yii::log(Yii::t('Unknown content encoding {encoding} - ignoring',array('{encoding}'=>$response->headers['Content-Encoding'])),CLogger::LEVEL_WARNING,'system.web.CHttpClientConnector');
			}
		}

		stream_copy_to_stream($connection, $response->body->stream);

		foreach($filters as $filter)
		{
			stream_filter_remove($filter);
		}

		return $response;
	}

	protected function write($connection, CHttpClientRequest $request)
	{
		$requestStringLength = strlen((string)$request);
		$written = fwrite($connection, $request);

		if ($written != $requestStringLength)
			Yii::log(Yii::t('yii','Wrote {written} instead of {length} bytes to stream - possible network error',array('{written}'=>$written,'{length}'=>$requestStringLength)),CLogger::LEVEL_WARNING,'system.web.CHttpClientConnector');
	}
}

/**
 * Class CDechunkFilter
 *
 * @author Da:Sourcerer <webmaster@dasourcerer.net>
 * @package system.web
 * @since 1.1.15
 * @link http://dancingmammoth.com/2009/08/29/php-stream-filters-unchunking-http-streams/
 */
class CDechunkFilter extends php_user_filter {
	const STATE_CHUNKLINE=0x00;
	const STATE_DATACHUNK=0x01;
	const STATE_TRAILER=0x02;

	private $_state=self::STATE_CHUNKLINE;
	private $_chunkSize;

	/**
	 * @param resource $in
	 * @param resource $out
	 * @param integer $consumed
	 * @param boolean $closing
	 * @return integer
	 */
	public function filter($in, $out, &$consumed, $closing)
	{
		while($bucket=stream_bucket_make_writeable($in))
		{
			$offset=0;
			$outbuffer='';
			while($offset<$bucket->datalen)
			{
				switch($this->_state)
				{
					case self::STATE_CHUNKLINE:
						//@todo check for incomplete chunklines
						$newOffset=strpos($bucket->data, "\r\n",$offset);
						$chunkLine=substr($bucket->data, $offset, $newOffset-$offset);
						@list($chunkSize,$chunkExt)=explode(';', $chunkLine, 2);
						if(!empty($chunkExt))
							Yii::log(Yii::t('yii','Found chunk extension in stream: {chunkext}',array('{chunkext}'=>$chunkExt)),CLogger::LEVEL_INFO,'system.web.CDechunkFilter');
						$chunkSize=trim($chunkSize);
						if(!ctype_xdigit($chunkSize))
							return PSFS_ERR_FATAL;

						$this->_chunkSize=hexdec($chunkSize);

						if($this->_chunkSize==0)
							$this->_state=self::STATE_TRAILER;
						else
							$this->_state=self::STATE_DATACHUNK;

						$offset=$newOffset+2;
						break;
					case self::STATE_DATACHUNK:
						$outbuffer.=substr($bucket->data, $offset, $this->_chunkSize);
						$offset+=($this->_chunkSize+2);
						if($offset>$bucket->datalen)
						{
							$this->_chunkSize=$offset-$bucket->datalen-2;
							$this->_state=self::STATE_DATACHUNK;
						}
						else
							$this->_state=self::STATE_CHUNKLINE;
						break;
					case self::STATE_TRAILER:
						if(isset($this->params['trailers']))
						{
							if($closing)
								$this->params['trailers'].=substr($bucket->data, $offset, $bucket->datalen-$offset-2);
							else
								$this->params['trailers'].=substr($bucket->data, $offset, $bucket->datalen-$offset);
						}
						$offset=$bucket->datalen;
						break;
				}
			}
			$consumed+=$bucket->datalen;
			$bucket->data=$outbuffer;
			stream_bucket_append($out,$bucket);
		}
		return PSFS_PASS_ON;
	}
}