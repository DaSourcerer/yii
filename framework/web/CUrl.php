<?php
/**
 * CUrl class file.
 *
 * @author Da:Sourcerer <webmaster@dasourcerer.net>
 * @link http://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * CUrl is an object for URL parsing and manipulation.
 *
 * The purpose of this class is the manipulation, normalization and comparison of URLs in accordance to RFC
 * {@link http://tools.ietf.org/html/rfc3986 RFC 3986}. This class has been heavily influenced by the
 * {@link http://pear.php.net/package/Net_URL2 PEAR::Net_URL2} package. In addition to normalization, conversion of
 * internationalized domain names (IDNs) as mentioned in {@link http://tools.ietf.org/html/rfc3490 RFC 3490} is being
 * handled in here.
 *
 * This class is in no way related to the {@link http://curl.haxx.se cURL} library, the {@link CUrlRule} class or the
 * {@link CUrlManager}. Due to its forgiving parsing method, it is not to be used for URL validation. Please resort to
 * {@link CUrlValidator} for that.
 *
 * @property string $scheme
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

	/**
	 * @var array
	 */
	public $params=array();

	/**
	 * @var string
	 */
	public $fragment;

	/**
	 * @var string
	 */
	private $_scheme;

	/**
	 * @var string
	 */
	private $_host;

	/**
	 * @var string
	 */
	private $_user;

	/**
	 * @var string
	 */
	private $_pass;

	/**
	 * @var integer
	 */
	private $_port;

	/**
	 * @var string
	 */
	private $_path;

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
	 * @param CUrl|array|string $url
	 * @throws CException
	 */
	public function __construct($url=null)
	{
		if($url instanceof self)
			$url=$url->toArray();
		elseif(is_string($url)) {
			//@todo Although parse_url() is pretty battle-hardened, there could be better ways to parse this.
			//PEAR::Net_URL2 is using a regex supposedly copied from RFC 3986, Appendix B
			if(($parsedUrl=@parse_url($url))===false)
				throw new CException(Yii::t('Failed to parse URL {url}',array('{url}'=>$url)));
			$url=$parsedUrl;
		}

		if(is_array($url)){
			foreach($url as $key=>$value)
				if(!empty($value))
					$this->$key=$value;
		}
	}

	/**
	 * @param string $scheme
	 */
	public function setScheme($scheme)
	{
		$this->_scheme=strtolower($scheme);
	}

	/**
	 * @return string
	 */
	public function getScheme()
	{
		return $this->_scheme;
	}

	/**
	 * Check if an URL is absolute or not
	 * @return bool
	 */
	public function isAbsolute()
	{
		return !empty($this->_scheme);
	}

	/**
	 * @param string $host
	 */
	public function setHost($host)
	{
		//@todo create a single instance of idna_convert and reuse that instead of creating a new instance on every call
		require_once(Yii::getPathOfAlias('system.vendors.Net_IDNA2.Net').DIRECTORY_SEPARATOR.'IDNA2.php');
		$idna=new Net_IDNA2;
		$this->_host=$this->urlencode($idna->encode(strtolower($host)));
	}

	/**
	 * @return string
	 */
	public function getHost()
	{
		return $this->_host;
	}

	/**
	 * @param string $user
	 */
	public function setUser($user)
	{
		$this->_user=$this->urlencode($user);
	}

	/**
	 * @return string
	 */
	public function getUser()
	{
		return $this->_user;
	}

	/**
	 * @param string $pass
	 */
	public function setPass($pass)
	{
		$this->_pass=$this->urlencode($pass);
	}

	/**
	 * @return string
	 */
	public function getPass()
	{
		return $this->_pass;
	}

	/**
	 * @param integer $port
	 */
	public function setPort($port)
	{
		if($port!=getservbyname($this->scheme,'tcp'))
			$this->_port=$port;
	}

	/**
	 * @return integer
	 */
	public function getPort()
	{
		return $this->_port;
	}

	/**
	 * @param string $path
	 */
	public function setPath($path)
	{
		//@todo: RFC 3986, sec 6.2.2ff
		// thx to hashguy on ##php@freenode for coming up with this
		$normalizedPath=array();
		foreach(explode('/',$path) as $segment) {
			if($segment=='.')
				continue;
			if($segment=='..'){
				array_pop($normalizedPath);
				continue;
			}
			$normalizedPath[]=$this->urlencode($segment);
		}

		if(!empty($normalizedPath))
			$this->_path=implode('/',$normalizedPath);
		else
			$this->_path=null;
	}

	/**
	 * @return string
	 */
	public function getPath()
	{
		return $this->_path;
	}

	/**
	 * @param string $query
	 */
	public function setQuery($query)
	{
		if(is_array($query))
			$this->params=$query;
		else
			$this->params=$this->parseQueryString($query);
	}

	/**
	 * @return string
	 */
	public function getQuery()
	{
		return $this->buildQueryString($this->params);
	}

	/**
	 * Strip specified parts from the current CUrl object and return a new one
	 *
	 * @param integer $bitmap
	 * @return CUrl
	 */
	public function strip($bitmap)
	{
		$components=$this->toArray();
		foreach(self::$_componentMap as $key=>$component)
			if($key&$bitmap)
				unset($components[$component]);

		return new CUrl($components);
	}

	/**
	 * @param int $bitmap
	 * @return CUrl
	 */
	public function filter($bitmap=0x00)
	{
		$components=$this->toArray();
		foreach(self::$_componentMap as $key=>$component)
			if(!($key&$bitmap))
				unset($components[$component]);
		return new CUrl($components);
	}

	/**
	 * @param CUrl|array|string $url
	 * @throws CException
	 * @return CUrl
	 */
	public function resolve($url)
	{
		if(!$url instanceof self)
			$url=new self($url);

		if(!$this->isAbsolute())
			throw new CException(Yii::t('yii','Cannot resolve relative URL'));

		if($url->isAbsolute())
			return new CUrl($url);

		$result=array_merge($this->toArray(),$url->strip(self::COMPONENT_PATH)->toArray());

		if($url->path{0}==='/')
			$result['path']=$url->path;

		return new CUrl($result);
	}

	/**
	 * @return array
	 */
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

	/**
	 * @return string
	 */
	public function __toString()
	{
		$result='';
		$components=$this->toArray();
		if(!empty($components['scheme']))
			$result.=$components['scheme'].':';
		if(!empty($components['user']) || !empty($components['host']))
			$result.='//';
		if(!empty($components['user'])){
			$result.=$components['user'];
			if(!empty($components['pass']))
				$result.=':'.$components['pass'];
			$result.='@';
		}
		if(!empty($components['host']))
			$result.=$components['host'];
		if(!empty($components['port']))
			$result.=':'.$components['port'];
		if(!empty($components['path'])){
			if($components['path']{0}!=='/')
				$result.='/';
			$result.=$components['path'];
		} elseif(!empty($components['query']) || !empty($components['fragment']))
			$result.='/';
		if(!empty($components['query']))
			$result.='?'.$components['query'];
		if(!empty($components['fragment']))
			$result.='#'.$components['fragment'];
		return $result;
	}

	private function urlencode($string)
	{
		$string=preg_replace_callback('/%[a-f\d]{2}/','strtoupper',$string);
		return str_replace('%7E','~',rawurlencode($string));
	}

	/**
	 * @param $string
	 * @return array
	 */
	private function parseQueryString($string)
	{
		$result=array();
		$queryParts=explode('&',$string);
		foreach($queryParts as $queryPart) {
			@list($key,$value)=explode('=',$queryPart,2);
			$key=rawurldecode($key);
			if(empty($value))
				$value=null;
			else
				$value=rawurldecode($value);
			if(preg_match_all('/\[([^\]]*)\]/',$key,$matches,PREG_SET_ORDER) > 0){
				$key=substr($key,0,strpos($key,$matches[0][0]));
				if(!isset($result[$key]))
					$result[$key]=array();
				$this->parseQueryStringHelper($result[$key],$matches,$value);
			} else
				$result[$key]=$value;
		}
		return $result;
	}

	/**
	 * @param $result
	 * @param $matches
	 * @param $value
	 */
	private function parseQueryStringHelper(&$result,$matches,$value)
	{
		$match=array_shift($matches);
		if(empty($matches))
			$result[$match[1]]=$value;
		else {
			if(!isset($match[1]))
				$result[$match[1]]=array();
			$this->parseQueryStringHelper($result[$match[1]],$matches,$value);
		}
	}

	/**
	 * @param array $params
	 * @return string
	 */
	private function buildQueryString($params)
	{
		$result=array();
		foreach($params as $key=>$value) {
			if(is_array($value))
				$result[]=$this->buildQueryStringHelper($key,$value);
			else
				if(empty($value))
					$result[]=$this->urlencode($key);
				else
					$result[]=$this->urlencode($key).'='.$this->urlencode($value);
		}
		return implode('&',$result);
	}

	/**
	 * @param string $prefix
	 * @param array $params
	 * @return string
	 */
	private function buildQueryStringHelper($prefix,$params)
	{
		$result=array();
		foreach($params as $key=>$value) {
			if(is_array($value))
				$result[]=$this->buildQueryStringHelper($prefix.'['.$key.']',$value);
			else
				$result[]=urlencode($prefix.'['.$key.']').'='.urlencode($value);
		}
		return implode('&',$result);
	}
}