<?php
class CHttpClient extends CApplicationComponent
{
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
	 * @var integer
	 */
	public $timeout=5;
	
	
	/**
	 * @var boolean
	 */
	public $useConnectionPooling=false;
	
	/**
	 *@var array 
	 */
	private $_connections=array();
	
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
				'Connection'=>($this->useConnectionPooling?'keep-alive':'close'),
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
}

abstract class CHttpClientMessage extends CComponent
{
	public $httpVersion=1.1;
	public $headers;
	public $cookies;
	public $body;	
}

class CHttpClientResponse extends CHttpClientMessage
{
	
}

class CHttpClientRequest extends CHttpClientMessage
{
	public $target;
}

class CHeaderCollection extends CMap
{
	
}