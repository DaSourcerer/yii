<?php
Yii::import('system.web.CHttpClient');

class CHttpClientRequestTest extends CTestCase
{
	public function testConstructor()
	{
		$request=new CHttpClientRequest('http://www.example.org/', CHttpClient::METHOD_HEAD);
		$this->assertEquals('http://www.example.org/', $request->requestUrl);
		$this->assertEquals(CHttpClient::METHOD_HEAD, $request->method);
	}

	/**
	 * @expectedException CException
	 * @dataProvider invalidUrls
	 */
	public function testThrowsExceptionOnInvalidUrls($url)
	{
		$this->markTestSkipped('Re-enable if URL validation works better');
		$request=new CHttpClientRequest($url);
	}


	/**
	 * @expectedException CException
	 * @dataProvider unsupportedSchemes
	 */
	public function testThrowsExceptionOnUnsupportedSchemes($scheme)
	{
		$request=new CHttpClientRequest;
		$request->scheme=$scheme;
	}

	/**
	 * @covers CHttpClientRequest::updateAuthenticationHeader
	 */
	public function testAuthHeader()
	{
		$request=new CHttpClientRequest;
		$this->assertEmpty($request->headers);
		$request->pass='bar';
		$this->assertEmpty($request->headers);
		$request->user='foo';
		$this->assertNotEmpty($request->headers);
		$this->assertArrayHasKey('Authorization', $request->headers);
		$this->assertStringStartsWith('Basic ',$request->headers['Authorization']);
		$this->assertStringEndsWith(base64_encode('foo:bar'), $request->headers['Authorization']);
		$request=new CHttpClientRequest;
		$request->user='foo';
		$this->assertEmpty($request->headers);
		$request->pass='bar';
		$this->assertNotEmpty($request->headers);
		$this->assertArrayHasKey('Authorization', $request->headers);
	}

	public function testSetRequestUrl()
	{
		$request=new CHttpClientRequest;
		$request->requestUrl='http://example.org/path?foo=bar#baz';
		$this->assertEquals('http://example.org/path?foo=bar#baz',$request->requestUrl);
		$this->assertEquals('http', $request->scheme);
		$this->assertEmpty($request->user);
		$this->assertEmpty($request->pass);
		$this->assertEquals('example.org', $request->host);
		$this->assertEmpty($request->port);
		$this->assertEquals('/path', $request->path);
		$this->assertEquals('foo=bar', $request->query);
		$this->assertEquals('baz', $request->fragment);
	}

	public function testSetBody()
	{
		$request=new CHttpClientRequest('http://example.org', CHttpClient::METHOD_POST);
		$this->assertEmpty($request->body);
		$request->body='foo';
		$this->assertEquals('foo', $request->body);
		$this->assertNotEmpty($request->headers);
		$this->assertArrayHasKey('Content-Length', $request->headers);
		$this->assertEquals(3, $request->headers['Content-Length']);
	}

	public function testAutomagicHostHeader()
	{
		$request= new CHttpClientRequest;
		$request->httpVersion=0.9;
		$request->requestUrl='http://example.org';
		$this->assertEmpty($request->headers);

		$request= new CHttpClientRequest;
		$request->httpVersion=1.0;
		$request->requestUrl='http://example.org';
		$this->assertEmpty($request->headers);

		$request= new CHttpClientRequest;
		$request->httpVersion=1.1;
		$request->requestUrl='http://example.org';
		$this->assertNotEmpty($request->headers);
		$this->assertArrayHasKey('Host', $request->headers);
		$this->assertEquals('example.org', $request->headers['Host']);
	}

	public function testToString()
	{
		$request=new CHttpClientRequest('http://example.org');
		$this->assertStringStartsWith('GET / HTTP/1.1', (string)$request);
		$this->assertStringEndsWith(CHttpClient::CRLF.CHttpClient::CRLF, (string)$request);

		$request=new CHttpClientRequest('http://example.org/foo', CHttpClient::METHOD_POST);
		$request->httpVersion=1.0;
		$request->body='bar=baz';
		$this->assertStringStartsWith('POST /foo HTTP/1.0', (string)$request);
		$this->assertStringEndsWith(CHttpClient::CRLF, (string)$request);
	}

	public static function invalidUrls()
	{
		return array(
			array('http:example.org'),
			array('example.org'),
			array('http//example.org'),
			array('http/example.org'),
			array(null),
			array(''),
			array('l#
			+asföäa#sf'),
			array('<![CDATA[This is so really not an URL it\'s *GOT* to fail.'),
		);
	}

	public static function unsupportedSchemes()
	{
		return array(
			array('ftp'),
			array('irc'),
			array('gopher'),
		);
	}
}