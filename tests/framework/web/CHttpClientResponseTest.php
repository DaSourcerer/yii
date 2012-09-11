<?php
Yii::import('system.web.CHttpClient');

class CHttpClientResponseTest extends CTestCase
{
	/**
	 * @var CHttpClientResponse
	 */
	private $_response;

	public function setUp()
	{
		$this->_response=new CHttpClientResponse;
	}

	/**
	 * @dataProvider redirectStatusCodes
	 */
	public function testIsRedirect($status)
	{
		$this->_response->status=$status;
		$this->assertTrue($this->_response->isRedirect());
	}

	public function redirectStatusCodes()
	{
		return array(
			array(301),
			array(302),
			array(303),
		);
	}
	/**
	 * @dataProvider noRedirectStatusCodes
	 */
	public function testIsNoRedirect($status)
	{
		$this->_response->status=$status;
		$this->assertFalse($this->_response->isRedirect());
	}

	public function noRedirectStatusCodes()
	{
		return array(
			array(100),
			array(200),
			array(300),
			array(304),
			array(400),
		);
	}
}