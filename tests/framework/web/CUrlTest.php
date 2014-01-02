<?php

class CUrlTest extends CTestCase
{
	/**
	 * @dataProvider urlProvider
	 * @covers CUrl::parseQueryString
	 * @covers CUrl::parseQueryStringHelper
	 * @covers CUrl::buildQueryString
	 * @covers CUrl::buildQueryStringHelper
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
		$this->assertEquals('bar', $url->query);
		$this->assertNotEmpty($url->params);
		$this->assertArrayHasKey('bar', $url->params);
		$this->assertNull($url->params['bar']);
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
		$this->assertEquals('xn--d1acpjx3f.xn--p1ai',$url->host);
	}

	public function testSetScheme()
	{
		$url=new CUrl;
		$this->assertEmpty($url->scheme);
		$url->scheme='http';
		$this->assertEquals('http',$url->scheme);
		$url->scheme='HTTP';
		$this->assertEquals('http',$url->scheme);
	}

	/**
	 * @dataProvider hostProvider
	 */
	public function testSetHost($host)
	{
		$url=new CUrl;
		$url->host=$host;
		$this->assertEquals('example.com',$url->host);
	}

	public function testSetPort()
	{
		$url=new CUrl('http://www.example.com');
		$this->assertEmpty($url->port);
		$url=new CUrl('http://www.example.com:80');
		$this->assertEquals(80,$url->port);
		$url=new CUrl('http://www.example.com:90');
		$this->assertEquals(90,$url->port);
	}

	/**
	 * @covers CUrl::normalizePath()
	 */
	public function testGetNormalizedPath()
	{
		$url=new CUrl('http://www.example.com');
		$this->assertEmpty($url->getNormalizedPath());
		$url->path='/';
		$this->assertEquals('/',$url->getNormalizedPath());
		$url->path='.';
		$this->assertEmpty($url->getNormalizedPath());
		$url->path='..';
		$this->assertEmpty($url->getNormalizedPath());
		$url->path='/./.././a/b/../';
		$this->assertEquals('a/',$url->getNormalizedPath());
		$url->path='/a/../b/c/d/../e/../../';
		$this->assertEquals('/b/',$url->getNormalizedPath());

		// The following examples are copied verbatim from RFC 3986, Section 5.2.4
		$url->path='/a/b/c/./../../g';
		$this->assertEquals('/a/g',$url->getNormalizedPath());
		$url->path='mid/content=5/../6';
		$this->assertEquals('mid/6',$url->getNormalizedPath());
	}

	public function testToString()
	{
		$url=new CUrl('//host');
		$this->assertEquals('//host',$url->__toString());
		$url=new CUrl('http://example.com');
		$this->assertEquals('http://example.com',$url->__toString());
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
						'bar'=>null,
					),
					'fragment'=>'baz',
				),
			),
			array(
				new CUrl('http://www.example.org/foo?bar#baz'),
			),
		);
	}

	public function hostProvider()
	{
		return array(
			array('example.com'),
			array('Example.com'),
			array('EXAMPLE.COM'),
		);
	}
}