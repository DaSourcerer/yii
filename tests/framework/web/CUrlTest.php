<?php

Yii::import('system.web.CHttpClient',true);

class CUrlTest extends CTestCase
{
	/**
	 * @dataProvider urlProvider
	 */
	public function testConstructor($url)
	{
		$url=new CUrl($url);
		$this->assertEquals('http', $url->scheme);
		$this->assertEquals('www.example.org', $url->host);
		$this->assertEmpty($url->user);
		$this->assertEmpty($url->pass);
		$this->assertEmpty($url->port);
		$this->assertEquals('/foo', $url->path);
		$this->assertEquals('bar=', $url->query);
		$this->assertNotEmpty($url->params);
		$this->assertArrayHasKey('bar', $url->params);
		$this->assertEmpty($url->params['bar']);
		$this->assertEquals('baz', $url->fragment);
	}

	public function testConstructorWithExistingCUrl()
	{
		$url=new CUrl('http://www.example.org');
		$newUrl=new CUrl($url);
		$this->assertNotSame($url, $newUrl);
		$this->assertEquals($url, $newUrl);
	}

	public function testIdnHost()
	{
		$url=new CUrl('http://яндекс.рф');
		$this->assertEquals('xn--d1acpjx3f.xn--p1ai', $url->host);
	}

	public function urlProvider()
	{
		return array(
			array(
				'http://www.example.org/foo?bar#baz',
			),
			array(
				array(
					'scheme'=>'http',
					'host'=>'www.example.org',
					'path'=>'/foo',
					'query'=>'bar',
					'fragment'=>'baz',
				),
			),
			array(
				array(
					'scheme'=>'http',
					'host'=>'www.example.org',
					'path'=>'/foo',
					'params'=>array(
						'bar'=>'',
					),
					'fragment'=>'baz',
				),
			),
			array(
				new CUrl('http://www.example.org/foo?bar#baz'),
			),
		);
	}
}