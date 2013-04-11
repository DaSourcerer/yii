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

	public function testConnector()
	{
		$this->assertInstanceOf('CBaseHttpClientConnector', $this->_client->connector);
	}

	public function testGet()
	{
		$request=$this->_client->get('http://www.example.org/');
		$this->assertInstanceOf('CHttpClientRequest', $request);
		$this->assertEquals(CHttpClient::METHOD_GET, $request->method);
		$this->assertEquals('http://www.example.org/', $request->url);

		$request=$this->_client->get(new CHttpClientRequest('http://www.example.org/', CHttpClient::METHOD_CONNECT));
		$this->assertInstanceOf('CHttpClientRequest', $request);
		$this->assertEquals(CHttpClient::METHOD_GET, $request->method);
		$this->assertEquals('http://www.example.org/', $request->url);
	}

	public function testSend()
	{
		$request=new CHttpClientRequest('http://www.example.org/');
		$response=$this->_client->send($request);
		$this->assertInstanceOf('CHttpClientResponse', $response);
		$this->assertSame($request, $this->_client->connector->getRequest());
		$this->assertSame($response, $this->_client->connector->getResponse());

		$response=$this->_client->send(array(
			'url'=>'http://www.example.org/',
			'method'=>CHttpClient::METHOD_HEAD,
			'httpVersion'=>1.0,
			'headers'=>array(
				'X-Foo'=>'bar',
			),
		));
		$request=$this->_client->connector->getRequest();

		$this->assertInstanceOf('CHttpClientResponse', $response);
		$this->assertInstanceOf('CHttpClientRequest', $request);
		$this->assertEquals('http://www.example.org/', $request->url);
		$this->assertEquals(CHttpClient::METHOD_HEAD, $request->method);
		$this->assertEquals(1.0, $request->httpVersion);
		$this->assertNotEmpty($request->headers);
		$this->assertEquals('bar', $request->headers['X-Foo']);
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
			$this->_response=new CHttpClientResponse;
		return $this->_response;
	}

	public function send(CHttpClientRequest $request)
	{
		$this->_request=$request;
		return $this->getResponse();
	}
}