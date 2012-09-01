<?php

Yii::import('system.collections.CConfiguration');

class MyClass extends CComponent
{
	public $param1;
	private $_param2;
	public $param3;
	private $_object;
	public $backquote;

	public function getParam2()
	{
		return $this->_param2;
	}

	public function setParam2($value)
	{
		$this->_param2=$value;
	}

	public function getObject()
	{
		if($this->_object===null)
			$this->_object=new MyClass;
		return $this->_object;
	}
}

class CConfigurationTest extends CTestCase
{
	public $configFile;

	public function setUp()
	{
		$this->configFile=dirname(__FILE__).'/data/config.php';
	}

	public function tearDown()
	{
	}

	public function testLoadFromFile()
	{
		$config=new CConfiguration;
		$this->assertEmpty($config->toArray());
		$config->loadFromFile($this->configFile);
		$data=include($this->configFile);
		$this->assertEquals($data, $config->toArray());
	}

	public function testSaveAsString()
	{
		$config=new CConfiguration($this->configFile);
		$str=$config->saveAsString();
		eval("\$data=$str;");
		$this->assertEquals($data, $config->toArray());
	}

	public function testApplyTo()
	{
		$config=new CConfiguration($this->configFile);
		$object=new MyClass;
		
		$this->assertFalse(isset($object->param1));
		$this->assertFalse(isset($object->param2));
		$this->assertFalse(isset($object->param3));
		$this->assertFalse(isset($object->blockquote));
		
		$config->applyTo($object);
		
		$this->assertEquals('value1', $object->param1);
		$this->assertFalse($object->param2);
		$this->assertEquals(123, $object->param3);
		$this->assertEquals("\\back'quote'", $object->backquote);
	}

	/**
	 * @expectedException CException
	 * @expectedExceptionMessage Property "MyClass.invalid" is not defined
	 */
	public function testException()
	{
		$config=new CConfiguration(array('invalid'=>'value'));
		$object=new MyClass;
		$config->applyTo($object);
	}

	public function testCreateComponent()
	{
		$obj=Yii::createComponent(array('class'=>'MyClass','param2'=>3));
		$this->assertInstanceof('MyClass', $obj);
		$this->assertEquals(3, $obj->param2);
	}
}
