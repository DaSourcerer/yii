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
 * @property $connector CBaseHttpClientConnector this client's connector
 * @property $cache CCache
 *
 * @author Da:Sourcerer <webmaster@dasourcerer.net>
 * @package system.web
 * @since 1.1.15
 */
class CHttpClient extends CApplicationComponent
{
	/**
	 * HTTP line terminator
	 */
	const CRLF="\r\n";

	/**
	 * The default user agent string CHttpClient is using in order to identify itself
	 */
	const USER_AGENT_STRING='Mozilla/5.0 (compatible; Yii/{version}; +http://yiiframework.com)';

	/**
	 * Empty user agent string
	 */
	const USER_AGENT_STRING_NONE=null;

	/**
	 * Full user agent string
	 */
	const USER_AGENT_STRING_FULL='Mozilla/5.0 (compatible; Yii/{version}; {connector}/{connectorVersion}; +http://yiiframework.com)';

	/**
	 * @var array a set of headers added to each request
	 */
	public $headers=array();

	/**
	 * @var string
	 */
	public $methodOverride=false;

	/**
	 * The user agent string with which CHttpClient will identify itself.
	 *
	 * @var string
	 */
	public $userAgentString=self::USER_AGENT_STRING;

	/**
	 * The id of the caching component
	 * @var string
	 */
	public $cacheID;

	private $_connector=array(
		'class'=>'CHttpClientStreamConnector',
	);

	/**
	 * @var CCache
	 */
	private $_cache;

	/**
	 * @see CApplicationComponent::init()
	 */
	public function init()
	{
		parent::init();

		if($this->userAgentString)
			$this->headers['User-Agent']=strtr($this->userAgentString,array(
				'{version}'=>Yii::getVersion(),
				'{connector}'=>$this->connector->getId(),
				'{connectorVersion}'=>$this->connector->getVersion(),
			));
	}

	/**
	 * Prepare a HTTP GET request
	 *
	 * GET requests are the most common ones. They are used to fetch a remote resource.
	 * This type of request is supposed to be idempotent, i.e. issuing a GET request should not change the state of the
	 * targeted web application, hence consequent requests should result into the same response.
	 *
	 * @param CHttpClientRequest|string $request
	 * @return CHttpClientRequest
	 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.3
	 */
	public function get($request)
	{
		return $this->craftRequest($request,CHttpClientRequest::METHOD_GET);
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
		return $this->craftRequest($request,CHttpClientRequest::METHOD_HEAD);
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
	public function post($request,$body=null,$mimeType=null)
	{
		$request=$this->craftRequest($request,CHttpClientRequest::METHOD_POST);
		if($body instanceof CHttpMessageBody)
			$request->body=$body;
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
	public function put($request,$body=null,$mimeType=null)
	{
		$request=$this->craftRequest($request,CHttpClientRequest::METHOD_PUT);
		if($body instanceof CHttpMessageBody)
			$request->body=$body;
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
		return $this->craftRequest($request,CHttpClientRequest::METHOD_DELETE);
	}

	/**
	 * Send out a prepared request or prepare one and send it out via the connector.
	 *
	 * @param array|CHttpClientRequest $request the request to be sent out
	 * @return CHttpClientResponse the response to the sent request
	 * @throws CException
	 */
	public function send($request)
	{
		if(is_array($request)){
			$r=new CHttpClientRequest;
			foreach($request as $key=>$value)
				$r->$key=$value;
			$request=$r;
		}
		if(!in_array($request->url->scheme,array('http','https')))
			throw new CException(Yii::t('yii','Unsupported protocol: {scheme}',array('{scheme}'=>$request->url->scheme)));
		$request->headers->mergeWith($this->headers);
		$request->client=$this;
		if($this->methodOverride)
			$request->overrideMethod($this->methodOverride);
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
		if(is_array($this->_connector)){
			$this->_connector=Yii::createComponent($this->_connector);
			$this->_connector->init();
		}
		return $this->_connector;
	}

	/**
	 * @param $request
	 * @param $method
	 * @return CHttpClientRequest
	 */
	protected function craftRequest($request,$method)
	{
		if(is_string($request))
			$result=new CHttpClientRequest($request);
		elseif(is_array($request)) {
			$result=new CHttpClientRequest;
			foreach($request as $key=>$value)
				$result->$key=$value;
		} elseif($request instanceof CHttpClientRequest)
			$result=$request;
		else
			$result=new CHttpClientRequest;
		$result->method=$method;
		$result->client=$this;
		return $result;
	}

	/**
	 * @return CCache
	 * @see $cacheID
	 */
	public function getCache()
	{
		if($this->_cache===null){
			$this->_cache=Yii::app()->getComponent($this->cacheID);
			//For the console
			if($this->_cache===null)
				$this->_cache=new CDummyCache;
		}
		return $this->_cache;
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
	public function __construct($content=null,$mimeType=null)
	{
		if($content){
			if($mimeType==null && $content!=null){
				if(is_string($content)){
					fwrite($this->stream,$content);
					$this->headers->add('Content-Type','text/plaintext');
				} elseif(is_stream($content)) {
					$this->_stream=$content;
					$this->headers->add('Content-Type','text/plaintext');
				} elseif(is_array($content)) {
					fwrite($this->stream,http_build_query($content));
					$this->headers->add('Content-Type','application/x-www-form-urlencoded');
				} elseif(is_object($content)) {
					switch(get_class($content)) {
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
			} else {
			}
		}
	}

	/**
	 * @return resource
	 */
	public function getStream()
	{
		if(!$this->_stream)
			$this->_stream=fopen('php://temp','w+');
		return $this->_stream;
	}

	/**
	 * @param resource $stream
	 */
	public function setStream($stream)
	{
		$this->_stream=$stream;
	}

	public function isEmpty()
	{
		return !$this->_stream;
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
	 * @var CHttpClientRequest the original request to this response
	 */
	public $request;

	/**
	 * Check if this response object is informational
	 *
	 * @return boolean
	 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.1
	 */
	public function isInformational()
	{
		return $this->status >= 100 && $this->status < 200;
	}

	/**
	 * Check if this response has been successful
	 *
	 * @return boolean
	 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.2
	 */
	public function isSuccessful()
	{
		return $this->status==304 || $this->status >= 200 && $this->status < 300;
	}

	/**
	 * Check if this response object carries a status code indicating a HTTP redirect
	 *
	 * @return boolean
	 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.3
	 */
	public function isRedirect()
	{
		return $this->status!=304 && $this->status >= 300 && $this->status < 400;
	}

	/**
	 * Check if the server reported an error in response to a faulty or illegal request
	 *
	 * @return boolean
	 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.4
	 */
	public function isClientError()
	{
		return $this->status >= 400 && $this->status < 500;
	}

	/**
	 * Check if the server failed to deliver a response due to problems on his side
	 *
	 * @return boolean
	 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.1
	 */
	public function isServerError()
	{
		return $this->status >= 500 && $this->status < 600;
	}

	/**
	 * Check if the server reported an error due to a either a client <strong>or</strong>
	 * a server error
	 *
	 * @return boolean
	 */
	public function isError()
	{
		return $this->isClientError() || $this->isServerError();
	}

	/**
	 * Check if this response carries an unrecognized response code
	 *
	 * @return boolean
	 */
	public function isUnknown()
	{
		if($this->isInformational())
			return false;
		if($this->isSuccessful())
			return false;
		if($this->isRedirect())
			return false;
		if($this->isError())
			return false;
		return true;
	}

	/**
	 * Check if this response can be cached
	 *
	 * @return bool
	 */
	public function isCacheable()
	{
		if(!$this->request->isCacheable())
			return false;
		if(!isset($this->headers['ETag']) && !isset($this->headers['Last-Modified']))
			return false;
		return true;
	}

	/**
	 * Check if this response has been cached
	 *
	 * @return bool
	 */
	public function isCached()
	{
		return $this->status==304;
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
	const METHOD_GET='GET';
	const METHOD_HEAD='HEAD';
	const METHOD_POST='POST';
	const METHOD_PUT='PUT';
	const METHOD_DELETE='DELETE';
	const METHOD_TRACE='TRACE';
	const METHOD_OPTIONS='OPTIONS';
	const METHOD_CONNECT='CONNECT';
	const METHOD_PATCH='PATCH';
	/** @var string */
	public $method=self::METHOD_GET;
	/** @var CHttpClient */
	public $client;

	/**
	 * Client certificate location
	 * @var string
	 */
	public $clientCertificate;

	/**
	 * @var string
	 */
	public $clientCertificatePassphrase;

	private $_url;

	/**
	 * @var bool
	 */
	private $_cacheable=true;

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

	public function __construct($url=null,$method=self::METHOD_GET)
	{
		$this->url=$url;
		$this->method=$method;
	}

	/**
	 * Set the target URL for this request
	 *
	 * @param CUrl|array|string $url
	 */
	public function setUrl($url)
	{
		if($url instanceof CUrl)
			$this->_url=$url;
		else
			$this->_url=new CUrl($url);
	}

	/**
	 * Get the target URL for this request
	 *
	 * @return CUrl
	 */
	public function getUrl()
	{
		return $this->_url;
	}

	/**
	 *
	 * @param CHttpClientResponse $response
	 * @return CHttpClientRequest
	 * @throws CException
	 */
	public static function fromRedirect(CHttpClientResponse $response)
	{
		if(!isset($response->headers['Location']))
			throw new CException(Yii::t('yii','Got a redirect without new location'));
		$request=new CHttpClientRequest($response->headers['Location'],$response->request->method);
		$request->headers=$response->request->headers;
		$request->body=$response->request->body;
		$request->client=$response->request->client;
		return $request;
	}

	/**
	 * Get the HTTP request line for this request
	 * @return string
	 */
	public function getRequestLine()
	{
		$path=$this->url->path;
		if(!empty($this->url->params))
			$path.='?'.$this->url->query;
		$path='/'.ltrim($path,'/');
		if($this->httpVersion<1)
			return sprintf('GET %s',$path).CHttpClient::CRLF;
		else
			return sprintf('%s %s HTTP/%.1f',$this->method,$path,$this->httpVersion).CHttpClient::CRLF;
	}

	/**
	 * Add a header to this request
	 *
	 * @param $key The key of the header to add
	 * @param $value The value of the header to add
	 * @return CHttpClientRequest This request
	 */
	public function addHeader($key,$value)
	{
		$this->headers->add($key,$value);
		return $this;
	}

	/**
	 * Set a header on this request
	 *
	 * The difference to {@link addHeader()} is that existing headers will be overwritten
	 *
	 * @param $key The key of the header to set
	 * @param $value The value of the header to set
	 * @return CHttpClientRequest This request
	 */
	public function setHeader($key,$value)
	{
		$this->headers->set($key,$value);
		return $this;
	}

	/**
	 * Remove a header from this request
	 *
	 * @param $key The key of the header to be removed
	 * @return CHttpClientRequest This request
	 */
	public function removeHeader($key)
	{
		$this->headers->remove($key);
		return $this;
	}

	/**
	 * Disable HTTP caching for this request
	 *
	 * @return CHttpClientRequest This request
	 */
	public function disableCaching()
	{
		$this->_cacheable=false;
		return $this;
	}

	public function setClientCertificate($cert,$passphrase)
	{
		$this->clientCertificate=$cert;
		$this->clientCertificatePassphrase=$passphrase;
		return $this;
	}

	public function overrideMethod($header='X-HTTP-Method-Override')
	{
		if(!in_array(strtoupper($this->method),array(self::METHOD_GET,self::METHOD_HEAD,self::METHOD_POST)))
		{
			$this->headers->set($header,$this->method);
			$this->method=self::METHOD_POST;
		}
		return $this;
	}

	/**
	 * Check if the response to this request can be cached
	 *
	 * @return bool true if the response to this request can be cached
	 */
	public function isCacheable()
	{
		if($this->httpVersion < 1)
			return false;
		if(!in_array(strtoupper($this->method),array(self::METHOD_GET,self::METHOD_HEAD)))
			return false;
		return $this->_cacheable;
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
class CHeaderCollection extends CMap
{

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
		foreach($this->toArray() as $name=>$values) {
			$name=implode('-',array_map('ucfirst',explode('-',$name)));
			$values=(array)$values;
			foreach($values as $value)
				$result.=$name.': '.$value.CHttpClient::CRLF;
		}
		return $result.CHttpClient::CRLF;
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
	 * Maximum number of seconds for timeouts
	 * @var integer
	 */
	public $timeout=5;

	/**
	 * Maximum number of HTTP redirects to follow
	 * @var integer
	 */
	public $maxRedirects=5;

	/**
	 * Perform the actual HTTP request and return the response
	 *
	 * @param CHttpClientRequest $request
	 * @throws CException
	 * @return CHttpClientResponse
	 */
	abstract public function send(CHttpClientRequest $request);

	/**
	 * Return a descriptive id for this connector to be used in the user agent string
	 *
	 * @return string
	 */
	abstract public function getId();

	/**
	 * Return a version number for this connector to be used in the user agent string
	 *
	 * @return string
	 */
	abstract public function getVersion();
}

/**
 * CHttpClientStreamConnector establishes network connectivity and does everything
 * to push and pull stuff over the wire.
 *
 * @author Da:Sourcerer <webmaster@dasourcerer.net>
 * @package system.web
 * @since 1.1.15
 */
class CHttpClientStreamConnector extends CBaseHttpClientConnector
{
	/**
	 * @var array options for connections with SSL peers.
	 * See http://www.php.net/manual/en/context.ssl.php
	 */
	public $ssl=array();

	public $persistent=true;

	public $bufferSize=32767;

	public $maxLineLength=8192;

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
			array_push($supportedEncodings,'gzip','deflate');

		if(extension_loaded('bz2'))
			$supportedEncodings[]='bzip2';

		if(!empty($supportedEncodings))
			$this->_headers['Accept-Encoding']=implode(', ',$supportedEncodings);
	}

	public function getStreamContext()
	{
		if($this->_streamContext===null){
			$this->_streamContext=stream_context_create();
			$sslOptions=array_merge(array(
				'cafile'=>Yii::getPathOfAlias('system.web').DIRECTORY_SEPARATOR.'cacert.pem',
				'verify_peer'=>true,
				'verify_depth'=>5,
			),$this->ssl);
			foreach($sslOptions as $option=>$value) {
				if(!stream_context_set_option($this->_streamContext,'ssl',$option,$value))
					throw new CException(Yii::t('yii','Failed to set SSL option {option}',array('{option}'=>$option)));
			}
		}
		return $this->_streamContext;
	}

	public function getConnection(CHttpClientRequest $request)
	{
		$remoteSocket=($request->url->scheme=='https'?'ssl':'tcp').'://'.$request->url->host.':';

		if(isset($request->url->port))
			$remoteSocket.=$request->url->port;
		else
			$remoteSocket.=($request->url->scheme=='https'?443:80);

		$flags=STREAM_CLIENT_CONNECT;
		if($this->persistent)
			$flags|=STREAM_CLIENT_PERSISTENT;

		$streamContext=$this->streamContext;

		if($request->clientCertificate && !stream_context_set_option($streamContext,'ssl','local_cert',$request->clientCertificate))
			throw new CExcpetion(Yii::t('yii','Failed to set client certificate'));
		else if($request->clientCertificate && $request->clientCertificatePassphrase && !stream_context_set_option($streamContext,'ssl','passphrase',$request->clientCertificatePassphrase))
			throw new CExcpetion(Yii::t('yii','Failed to set client certificate passphrase'));

		if(($connection=stream_socket_client($remoteSocket,$errno,$errstr,$this->timeout,$flags,$streamContext))===false)
			throw new CException(Yii::t('yii','Failed to connect to {url} ({errno}): {errstr}',array('{url}'=>$remoteSocket,'{errno}'=>$errno,'{errstr}'=>$errstr)));

		stream_set_write_buffer($connection,$this->bufferSize);
		stream_set_read_buffer($connection,$this->bufferSize);
		return $connection;
	}


	public function send(CHttpClientRequest $request)
	{
		return $this->sendInternal($request,0);
	}

	protected function sendInternal(CHttpClientRequest $request,$redirects)
	{
		$connection=$this->getConnection($request);

		$request->headers->mergeWith($this->_headers);
		$this->sendRequest($connection,$request);

		$response=$this->readResponse($connection,$request);

		if($response->isRedirect()){
			if($redirects++>$this->maxRedirects)
				throw new CException(Yii::t('yii','Maximum number of HTTP redirects reached'));
			$response=$this->sendInternal(CHttpClientRequest::fromRedirect($response),$redirects);
		}

		if($response->isCacheable()){
			$cacheHeaders=array();
			if(isset($response->headers['ETag']))
				$cacheHeaders['etag']=trim($response->headers['ETag']);
			if(isset($response->headers['Last-Modified']))
				$cacheHeaders['last-modified']=strtotime($response->headers['Last-Modified']);
			$request->client->cache->set('system.web.CHttpClient#'.$request->url->strip(CUrl::COMPONENT_FRAGMENT)->__toString(),$cacheHeaders);
		}

		return $response;
	}


	/**
	 * @param $connection
	 * @param CHttpClientRequest $request
	 * @return CHttpClientResponse
	 * @throws CException
	 */
	protected function readResponse($connection,CHttpClientRequest $request)
	{
		$response=new CHttpClientResponse;
		$response->request=$request;
		if(($statusLine=stream_get_line($connection,$this->maxLineLength,"\n"))===false)
			throw new CException(Yii::t('yii','Failed to read status line from connection'));

		if(strpos($statusLine,'HTTP/')!==0){
			Yii::log(Yii::t('yii','Received non-http response line - assuming HTTP/0.9'),CLogger::LEVEL_WARNING,'system.web.CHttpClientStreamConnector');
			$response->httpVersion=0.9;
			$response->status=200;

			fwrite($response->body->stream,$statusLine);
			$this->copyStream($connection,$response->body->stream);
			fclose($connection);

			return $response;
		}

		$statusLine=substr($statusLine,5);
		@list($response->httpVersion,$response->status,$response->message)=preg_split('/[ \t]+/',$statusLine,3);
		$response->httpVersion=(float)$response->httpVersion;
		$response->status=(int)$response->status;
		$response->message=trim($response->message);

		$headers='';
		while(($line=stream_get_line($connection,$this->maxLineLength,"\n"))!==false && !feof($connection) && trim($line)!='')
			$headers.=$line."\n";

		$this->parseHeaders($headers,$response->headers);

		$chunked=isset($response->headers['Transfer-Encoding']) && stripos($response->headers['Transfer-Encoding'],'chunked')!==false;
		$closeConnection=!$this->persistent || (isset($response->headers['Connection']) && stripos($response->headers['Connection'],'close')!==false);

		if($chunked) {
			while(($chunkSize=$this->readChunkSize($connection))>0) {
				$this->copyStream($connection,$response->body->stream,$chunkSize);
				fseek($connection,2,SEEK_CUR);
			}
			$trailers='';
			while(trim($trailer=stream_get_line($connection,$this->maxLineLength,"\n"))!='')
				$trailers.=$trailer."\n";
			if($trailers!='')
				$this->parseHeaders($trailers,$response->headers);

		} else if(isset($response->headers['Content-Length']))
			$this->copyStream($connection,$response->body->stream,$response->headers['Content-Length']);
		else if($closeConnection)
			$this->copyStream($connection,$response->body->stream);
		else
			throw new CException(Yii::t('yii','Unable to determine message size - cannot proceed on persistent connection.'));


		if($closeConnection)
			fclose($connection);

		$filters=array();
		if(isset($response->headers['Content-Encoding'])){
			switch(strtolower($response->headers['Content-Encoding'])) {
				case 'identity':
					break;
				case 'bzip2':
					$filters[]=stream_filter_append($response->body->stream,'bzip2.decompress',STREAM_FILTER_READ);
					rewind($response->body->stream);
					break;
				case 'gzip':
				case 'deflate':
					$offset=0;
					if(stream_get_contents($response->body->stream,3,0)==="\x1f\x8b\x08")
						$offset=10;
					fseek($response->body->stream,$offset,SEEK_SET);
					$filters[]=stream_filter_append($response->body->stream,'zlib.inflate',STREAM_FILTER_READ);
					break;
				default:
					Yii::log(Yii::t('Unknown content encoding {encoding} - ignoring',array('{encoding}'=>$response->headers['Content-Encoding'])),CLogger::LEVEL_WARNING,'system.web.CHttpClientStreamConnector');
			}
		}
		else
			rewind($response->body->stream);

		return $response;
	}

	protected function sendRequest($connection,CHttpClientRequest $request)
	{
		$requestString=$request->getRequestLine();
		if($request->httpVersion >= 1){
			$host=$request->url->host;
			if($request->url->port&&$request->url->port!=$request->url->getDefaultPort())
				$host.=':'.$request->url->port;
			$request->headers->set('Host',$host);
			$request->headers->set('Connection',($this->persistent)?'keep-alive':'close');
			if(!in_array(strtoupper($request->method),array(CHttpClientRequest::METHOD_GET,CHttpClientRequest::METHOD_HEAD)))
				$request->headers->set('Date',gmdate('D, d M Y H:i:s').' GMT');
			if(isset($request->url->user) && isset($request->url->pass))
				$request->headers->set('Authorization','Basic '.base64_encode($request->url->user.':'.$request->url->pass));
			if($request->isCacheable() && ($cacheHeaders=$request->client->cache->get('system.web.CHttpClient#'.$request->url->strip(CUrl::COMPONENT_FRAGMENT)->__toString()))!==false){
				if(isset($cacheHeaders['etag']))
					$request->headers->set('If-None-Match',$cacheHeaders['etag']);
				if(isset($cacheHeaders['last-modified']))
					$request->headers->set('If-Modified-Since',gmdate('D, d M Y H:i:s',$cacheHeaders['last-modified']).' GMT');
			}
			$requestString.=$request->headers;
		}

		stream_set_write_buffer($connection,0);
		$this->writeToStream($connection,$requestString);
		stream_set_write_buffer($connection,$this->bufferSize);

		if(!$request->body->isEmpty())
			$this->copyStream($request->body->stream,$connection);
	}

	protected function parseHeaders($headers,CHeaderCollection $collection)
	{
		//Per RFC2616, sec 19.3, we are required to treat \n like \r\n
		$headers=str_replace("\r\n","\n",$headers);
		//Unfold headers
		$headers=trim(preg_replace('/\n[ \t]+/',' ',$headers));
		$headers=explode("\n",$headers);

		foreach($headers as $line) {
			@list($header,$value)=explode(':',$line,2);
			$collection->add(trim($header),trim($value));
		}
	}

	public function getId()
	{
		return 'stream';
	}

	public function getVersion()
	{
		return phpversion();
	}

	protected function copyStream($source,$destination,$length=null)
	{
		$lengthLeft=$length;
		while(!feof($source) && $lengthLeft>0 && ($buffer=fread($source,($length===null?$this->bufferSize:min($lengthLeft,$this->bufferSize))))!==false)
		{
			$lengthLeft-=strlen($buffer);
			$this->writeToStream($destination,$buffer);
		}
		if($lengthLeft>0)
			throw new CException(Yii::t('yii','Premature end of stream, failed to read {length} bytes',array('{length}'=>$lengthLeft)));
	}

	protected function writeToStream($stream,$string)
	{
		$bytesLeft=strlen($string);
		$pos=0;
		while($bytesLeft>0)
		{
			$length=min($this->bufferSize,$bytesLeft);
			if(($written=fwrite($stream,substr($string,$pos,$length)))===false)
				throw new CException(Yii::t('yii','Failed to write {length} bytes to stream - possible network error',array('{length}'=>$length)));
			$pos+=$written;
			$bytesLeft-=$written;
		}
	}

	/**
	 * @param $connection
	 * @return integer
	 * @throws CException
	 */
	protected function readChunkSize($connection)
	{
		if(($chunkLine=stream_get_line($connection,$this->maxLineLength,CHttpClient::CRLF))===false)
			throw new CException(Yii::t('yii','Could not read chunkline from stream'));
		@list($chunkSize,$chunkExt)=explode(';',$chunkLine,2);
		if(!empty($chunkExt))
			Yii::log(Yii::t('yii','Found chunk extension in stream: {chunkext}',array('{chunkext}'=>$chunkExt)),CLogger::LEVEL_INFO,'system.web.CHttpClientStreamConnector');
		$chunkSize=trim($chunkSize);
		if(!ctype_xdigit($chunkSize))
			throw new CException(Yii::t('yii','Found an invalid chunksize in stream: {chunkSize}',array('{chunkSize}'=>$chunkSize)));
		return hexdec($chunkSize);
	}
}
