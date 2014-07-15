<?php

Yii::import('system.web.CHttpClient',true);

class CHttpClientTestCase extends CTestCase
{
	/**
	 * @var CHttpClient
	 */
	private $_client;

	public function setUp()
	{
		$this->_client=new CHttpClient;
		$this->_client->init();
		$this->_client->connector=new DummyConnector;
	}

	public function testDefaultHeaders()
	{
		$this->assertNull($this->_client->get('http://www.example.org/')->send()->request->headers['DNT']);
		$client=new CHttpClient;
		$client->headers=array('DNT'=>1);
		$client->init();
		$client->connector=new DummyConnector;
		$headers=$client->get('http://www.example.org/')->send()->request->headers->toArray();
		$this->assertNotEmpty($headers);
		$this->assertEquals(1,$headers['DNT']);
	}

	public function testUserAgentString()
	{
		$headers=$this->_client->get('http://www.example.org/')->send()->request->headers->toArray();
		$this->assertArrayHasKey('User-Agent',$headers);
		$client=new CHttpClient;
		$client->userAgentString=CHttpClient::USER_AGENT_STRING_NONE;
		$client->init();
		$client->connector=new DummyConnector;
		$headers=$client->get('http://www.example.org/')->send()->request->headers->toArray();
		$this->assertArrayNotHasKey('User-Agent',$headers);
	}

	public function testConnector()
	{
		$this->assertInstanceOf('CBaseHttpClientConnector', $this->_client->connector);
	}

	public function testGet()
	{
		$request=$this->_client->get('http://www.example.org/');
		$this->assertInstanceOf('CHttpClientRequest', $request);
		$this->assertEquals(CHttpClientRequest::METHOD_GET, $request->method);
		$this->assertEquals('http://www.example.org/', $request->url->__toString());

		$request=$this->_client->get(new CHttpClientRequest('http://www.example.org/',CHttpClientRequest::METHOD_CONNECT));
		$this->assertInstanceOf('CHttpClientRequest', $request);
		$this->assertEquals(CHttpClientRequest::METHOD_GET, $request->method);
		$this->assertEquals('http://www.example.org/', $request->url->__toString());
	}

	public function testHead()
	{
		$request=$this->_client->head('http://www.example.org/');
		$this->assertInstanceOf('CHttpClientRequest', $request);
		$this->assertEquals(CHttpClientRequest::METHOD_HEAD, $request->method);
		$this->assertEquals('http://www.example.org/', $request->url->__toString());

		$request=$this->_client->head(new CHttpClientRequest('http://www.example.org/',CHttpClientRequest::METHOD_CONNECT));
		$this->assertInstanceOf('CHttpClientRequest', $request);
		$this->assertEquals(CHttpClientRequest::METHOD_HEAD, $request->method);
		$this->assertEquals('http://www.example.org/', $request->url->__toString());
	}

	public function testDelete()
	{
		$request=$this->_client->delete('http://www.example.org/');
		$this->assertInstanceOf('CHttpClientRequest', $request);
		$this->assertEquals(CHttpClientRequest::METHOD_DELETE, $request->method);
		$this->assertEquals('http://www.example.org/', $request->url->__toString());

		$request=$this->_client->delete(new CHttpClientRequest('http://www.example.org/',CHttpClientRequest::METHOD_CONNECT));
		$this->assertInstanceOf('CHttpClientRequest', $request);
		$this->assertEquals(CHttpClientRequest::METHOD_DELETE, $request->method);
		$this->assertEquals('http://www.example.org/', $request->url->__toString());
	}

	public function testSend()
	{
		$request=new CHttpClientRequest('http://www.example.org/');
		$response=$this->_client->send($request);
		$this->assertInstanceOf('CHttpClientResponse', $response);
		$this->assertEquals(CHttpClientRequest::METHOD_GET, $this->_client->connector->getRequest()->method);
		$this->assertSame($request, $this->_client->connector->getRequest());
		$this->assertSame($response, $this->_client->connector->getResponse());

		$response=$this->_client->send(array(
			'url'=>'http://www.example.org/',
			'method'=>CHttpClientRequest::METHOD_HEAD,
			'httpVersion'=>1.0,
			'headers'=>array(
				'X-Foo'=>'bar',
			),
		));
		$request=$this->_client->connector->getRequest();

		$this->assertInstanceOf('CHttpClientResponse', $response);
		$this->assertInstanceOf('CHttpClientRequest', $request);
		$this->assertEquals('http://www.example.org/', $request->url);
		$this->assertEquals(CHttpClientRequest::METHOD_HEAD, $request->method);
		$this->assertEquals(1.0, $request->httpVersion);
		$this->assertNotEmpty($request->headers);
		$this->assertEquals('bar', $request->headers['X-Foo']);
	}

	/**
	 * @expectedException CException
	 */
	public function testSendInvalidProtocol()
	{
		$this->_client->send(array('url'=>'ftp://example.org'));
	}
}

class DummyConnector extends CBaseHttpClientConnector
{
	private $_response;
	private $_request;

	public function setResponse(CHttpClientResponse $response)
	{
		$this->_response=$response;
	}

	public function getRequest()
	{
		return $this->_request;
	}

	public function getResponse()
	{
		if(!$this->_response)
		{
			$this->_response=new CHttpClientResponse;
			$this->_response->request=$this->_request;
		}
		return $this->_response;
	}

	public function send(CHttpClientRequest $request)
	{
		$this->_request=$request;
		return $this->getResponse();
	}

	public function getId()
	{
		return 'dummy';
	}

	public function getVersion()
	{
		return '1.0';
	}
}