<?php

Yii::import('system.web.CHttpClient',true);

class CHttpClientRequestTest extends CTestCase
{
	public function testConstructor()
	{
		$request=new CHttpClientRequest;
		$this->assertEquals(CHttpClientRequest::METHOD_GET,$request->method);
		$this->assertInstanceOf('CUrl',$request->url);
		$this->assertInstanceOf('CHeaderCollection',$request->headers);
	}

	/**
	 * @covers CHttpClientRequest::getHeaders
	 * @covers CHttpClientRequest::setHeaders
	 */
	public function testSetHeader()
	{
		$request=new CHttpClientRequest;
		$this->assertSame($request,$request->setHeader('X-Foo','bar'));
		$this->assertTrue($request->headers->contains('X-Foo'));
	}

	public function testAddHeader()
	{
		$request=new CHttpClientRequest;
		$this->assertSame($request,$request->addHeader('X-Foo','bar'));
		$this->assertTrue($request->headers->contains('X-Foo'));
	}

	/**
	 * @depends testSetHeader
	 */
	public function testRemoveHeader()
	{
		$request=new CHttpClientRequest;
		$request->setHeader('X-Foo','bar');
		$this->assertSame($request,$request->removeHeader('X-Foo'));
		$this->assertFalse($request->headers->contains('X-Foo'));
	}

	public function testGetRequestLine()
	{
		$request=new CHttpClientRequest('http://example.com',CHttpClientRequest::METHOD_GET);
		$this->assertStringStartsWith(CHttpClientRequest::METHOD_GET,$request->getRequestLine());
		$this->assertStringEndsWith(CHttpClient::CRLF,$request->getRequestLine());
	}
}