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
 */
class CHttpClient extends CApplicationComponent {
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

	/** @var array a set of headers added to each request */
	public $headers=array();

	/** @see CApplicationComponent::init() */
	public function init()
	{
		parent::init();

		if(!isset($this->headers['User-Agent']))
			$this->headers['User-Agent']=str_replace('{version}',Yii::getVersion(),self::USER_AGENT_STRING);
	}

	/**
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
	 * @param CHttpClientRequest|string $request
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
	 * @param mixed $request
	 * @return CHttpClientResponse
	 */
	public function send($request)
	{

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

	/** @var CHttpMessageBody */
	public $body;

	public function __construct()
	{
		$this->headers=new CHeaderCollection;
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

	public function isInformational()
	{
		return ($this->status>=100&&$this->status<200);
	}

	public function isSuccessful()
	{
		return ($this->status==304||$this->status>=200&&$this->status<300);
	}

	/**
	 * Check if this response object carries a status code indicating a HTTP redirect
	 * @return boolean
	 */
	public function isRedirect()
	{
		return ($this->status!=304&&$this->status>=300&&$this->status<400);
	}

	public function isError()
	{
		return ($this->status>=400&&$this->status<500);
	}

	public function isServerError()
	{
		return $this->status>=500;
	}
}

/**
 * CHttpClientRequest holds a prepared HTTP request.
 *
 * The attributes {@link url} and {@link method} are the most important ones
 * as they control where and how this request should be sent to.
 */
class CHttpClientRequest extends CHttpClientMessage
{
	/** @var string */
	public $method=CHttpClient::METHOD_GET;
	/** @var CUrl */
	public $url;
	/** @var CHttpClient */
	public $client;

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
	}
}

class CHeaderCollection extends CMap {}

/**
 * CUrl is an object for URL parsing and manipulation. It is in no way related to the {@link http://curl.haxx.se cURL} library
 *
 * @property string $host
 * @property string $user
 * @property string $pass
 * @property integer $port
 * @property string $path
 * @property string $query
 */
class CUrl extends CComponent
{
	const COMPONENT_SCHEME=0x01;
	const COMPONENT_USER=0x02;
	const COMPONENT_PASS=0x04;
	const COMPONENT_AUTH=0x06; // user+auth
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
		if(is_string($url))
		{
			if(($parsedUrl=@parse_url($url))===false)
				throw new CException(Yii::t('Failed to parse URL {url}',array('{url}'=>$url)));
			$url=$parsedUrl;
		}
		foreach($url as $key=>$value)
			$this->$key=$value;
	}

	public function setHost($host)
	{
		//@todo create a single instance of idna_convert and reuse that instead of creating a new instance on every call
		require_once(Yii::getPathOfAlias('system.vendors.idna_convert').DIRECTORY_SEPARATOR.'idna_convert.class.php');
		$idnaConvert=new idna_convert();
		$this->_host=$idnaConvert->encode($host);
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