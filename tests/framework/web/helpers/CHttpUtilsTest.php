<?php

class CHttpUtilsTest extends CTestCase
{
	public function testParseQueryString()
	{
		$queryVars=CHttpUtils::parseQueryString('foo=bar');
		$this->assertArrayHasKey('foo',$queryVars);
		$this->assertEquals('bar',$queryVars['foo']);

		$queryVars=CHttpUtils::parseQueryString('foo=1&bar=2');
		$this->assertArrayHasKey('foo',$queryVars);
		$this->assertArrayHasKey('bar',$queryVars);
		$this->assertEquals(1,$queryVars['foo']);
		$this->assertEquals(2,$queryVars['bar']);

		$queryVars=CHttpUtils::parseQueryString('foo=bar&bar%5B0%5D=1&bar%5B1%5D=2');
		$this->assertEquals(array(
			'foo'=>'bar',
			'bar'=>array(1,2),
		),$queryVars);
	}

	public function testBuildQueryString()
	{
		$this->assertEquals('foo=bar',CHttpUtils::buildQueryString(array('foo'=>'bar')));
		$this->assertEquals('foo%5B0%5D=1&foo%5B1%5D=2',CHttpUtils::buildQueryString(array(
			'foo'=>array(1,2)
		)));
		$this->assertEquals('foo=bar&bar%5B0%5D=1&bar%5B1%5D=2',CHttpUtils::buildQueryString(array(
			'foo'=>'bar',
			'bar'=>array(1,2),
		)));
	}

	public function testParseUrl()
	{
		$this->assertEquals(array(
			'scheme'=>'http',
			'user'=>'user',
			'pass'=>'pass',
			'host'=>'example.com',
			'path'=>'test path',
			'query'=>array(
				'foo'=>'bar'
			),
			'fragment'=>'baz'
		), CHttpUtils::parseUrl('http://user:pass@example.com/test%20path/?foo=bar#baz'));
	}

	public function testBuildUrl()
	{
		$components=array(
			'scheme'=>'http',
			'user'=>'user',
			'pass'=>'pass',
			'host'=>'example.com',
			'path'=>'test path',
			'query'=>array(
				'foo'=>'bar'
			),
			'fragment'=>'baz'
		);
		$this->assertEquals('http://user:pass@example.com/test%20path?foo=bar#baz', CHttpUtils::buildUrl($components));
	}
}
