<?php

Yii::import('system.web.CHttpClient',true);

class CHeaderCollectionTest extends CTestCase
{
	public function testHeaderNamesCaseInsensitive()
	{
		$headers=new CHeaderCollection;
		$headers->add('X-Foo','bar');
		$this->assertEquals('bar',$headers['x-foo']);
		$this->assertEquals('bar',$headers['X-Foo']);
		$this->assertEquals('bar',$headers['X-FOO']);
	}

	public function testToString()
	{
		$headers=new CHeaderCollection;
		$this->assertNotEmpty((string)$headers);
		$this->assertEquals(CHttpClient::CRLF,(string)$headers);
		$headers->add('X-Foo','bar');
		$this->assertNotEmpty((string)$headers);
		$this->assertStringStartsWith('X-Foo:',(string)$headers);
		$this->assertStringEndsWith(CHttpClient::CRLF.CHttpClient::CRLF,(string)$headers);
	}

	public function testAdd()
	{
		$headers=new CHeaderCollection;
		$this->assertEquals(0,$headers->count());
		$headers->add('X-Foo','bar');
		$this->assertEquals(1,$headers->count());
		$this->assertInternalType('string',$headers['X-Foo']);
		$headers->add('X-Foo','baz');
		$this->assertEquals(1,$headers->count());
		$this->assertInternalType('array',$headers['X-Foo']);
		$this->assertEquals(array('bar','baz'),$headers['X-Foo']);
		$headers->add('X-Foo',array('1','2'));
		$this->assertEquals(array('bar','baz','1','2'),$headers['X-Foo']);
	}

	public function testRemove()
	{
		$headers=new CHeaderCollection;
		$this->assertNull($headers->remove('X-Foo'));
		$headers->add('X-Foo','bar');
		$this->assertEquals('bar',$headers->remove('X-Foo'));
	}

	public function testSet()
	{
		$headers=new CHeaderCollection;
		$headers->set('X-Foo','bar');
		$this->assertEquals(1,$headers->count());
		$this->assertEquals('bar',$headers['X-Foo']);
		$headers->set('X-Foo','baz');
		$this->assertEquals(1,$headers->count());
		$this->assertEquals('baz',$headers['X-Foo']);
	}
}