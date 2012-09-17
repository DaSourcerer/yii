<?php
/**
 * CHttpUtils class file
 */

/**
 * @package system.web.helpers
 */
class CHttpUtils
{
	const URL_STRIP_NONE=0x00;
	const URL_STRIP_SCHEME=0x01;
	const URL_STRIP_USER=0x02;
	const URL_STRIP_PASS=0x04;
	const URL_STRIP_AUTH=0x06;
	const URL_STRIP_HOST=0x08;
	const URL_STRIP_PORT=0x10;
	const URL_STRIP_PATH=0x20;
	const URL_STRIP_QUERY=0x40;
	const URL_STRIP_FRAGMENT=0x80;

	/**
	 * @param string $string
	 * @return string
	 */
	public static function parseQueryString($string)
	{
		$result=array();
		$queryParts=explode('&',$string);
		foreach($queryParts as $queryPart)
		{
			list($key,$value)=explode('=',$queryPart);
			$key=urldecode($key);
			$value=urldecode($value);
			$result[$key]=$value;
		}
		return $result;
	}

	/**
	 * @param array $data
	 * @return string
	 */
	public static function buildQueryString($data)
	{
		$result=array();
		foreach($data as $key=>$value)
		{
			if(is_array($value))
				$result[]=self::buildQueryStringInternal($key,$value);
			else
				$result[]=urlencode($key).'='.urlencode($value);
		}
		return implode('&',$result);
	}

	private static function buildQueryStringInternal($prefix, $data)
	{
		$result=array();
		foreach($data as $key=>$value)
		{
			if(is_array($value))
				$result[]=self::buildQueryStringInternal($prefix.'['.$key.']',$value);
			else
				$result[]=urlencode($prefix.'['.$key.']').'='.urlencode($value);
		}
		return implode('&',$result);
	}

	public static function parseUrl($url, $flags=self::URL_STRIP_NONE)
	{
		$components=self::stripUrlComponents(@parse_url($url), $flags);
		if(isset($components['user']))
			$components['user']=rawurldecode($components['user']);
		if(isset($components['pass']))
			$components['pass']=rawurldecode($components['pass']);
		if(isset($components['path']))
		{
			$pathComponents=explode('/',$components['path']);
			array_walk($pathComponents, 'urldecode');
			$components['path']=$pathComponents;
		}
		if(isset($components['query']))
			$components['query']=self::parseQueryString($components['query']);
		return $components;
	}

	public static function buildUrl($components, $flags=self::URL_STRIP_NONE)
	{
		$components=self::stripUrlComponents($components, $flags);
		$result='';
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
			if($components['path']{0}!='/')
				$result.='/';
			$result.=$components['path'];
		}
		else if((isset($components['query']) && !empty($components['query'])) || (isset($components['fragment']) && !empty($components['fragment'])))
			$result.='/';
		if(isset($components['query']) && !empty($components['query']))
		{
			if(is_array($components['query']))
				$result.='?'.self::buildQueryString($components['query']);
			else
				$result.='?'.$components['query'];
		}
		if(isset($components['fragment']) && !empty($components['fragment']))
			$result.='#'.$components['fragment'];
		return $result;
	}

	/**
	 * @param array $components
	 * @param integer $flags
	 * @return array
	 */
	private static function stripUrlComponents($components, $flags)
	{
		if($flags & self::URL_STRIP_SCHEME)
			unset($components['scheme']);
		if($flags & self::URL_STRIP_USER)
			unset($components['user']);
		if($flags & self::URL_STRIP_PASS)
			unset($components['pass']);
		if($flags & self::URL_STRIP_HOST)
			unset($components['host']);
		if($flags & self::URL_STRIP_PORT)
			unset($components['port']);
		if($flags & self::URL_STRIP_PATH)
			unset($components['path']);
		if($flags & self::URL_STRIP_QUERY)
			unset($components['query']);
		if($flags & self::URL_STRIP_FRAGMENT)
			unset($components['fragment']);

		return $components;
	}
}