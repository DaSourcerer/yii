<?php
Yii::import('system.web.CHttpClient',true);

class CDechunkFilterTest extends CTestCase
{
	public static function setUpBeforeClass()
	{
		stream_filter_register('yiitestdechunkfilter','CDechunkFilter');
	}


	public function testChunkedContent()
	{
		$stream=fopen(dirname(__FILE__).DIRECTORY_SEPARATOR.'_fixtures'.DIRECTORY_SEPARATOR.'chunked.txt','rb');
		stream_filter_append($stream,'yiitestdechunkfilter');
		$this->assertEquals('hello world!',stream_get_contents($stream));
		fclose($stream);
	}

	public function testChunkedContentWithExtensions()
	{
		$stream=fopen(dirname(__FILE__).DIRECTORY_SEPARATOR.'_fixtures'.DIRECTORY_SEPARATOR.'chunked-extended.txt','rb');
		stream_filter_append($stream,'yiitestdechunkfilter');
		$this->assertEquals('hello world!',stream_get_contents($stream));
		fclose($stream);
	}

	public function testChunkedContentWithTrailers()
	{
		$trailers='';
		$stream=fopen(dirname(__FILE__).DIRECTORY_SEPARATOR.'_fixtures'.DIRECTORY_SEPARATOR.'chunked-trailers.txt','rb');
		stream_filter_append($stream,'yiitestdechunkfilter',STREAM_FILTER_READ,array('trailers'=>&$trailers));
		$this->assertEquals('hello world!',stream_get_contents($stream));
		$this->assertNotEmpty($trailers);
		$this->assertEquals('X-Foo: Bar',$trailers);
		fclose($stream);
	}

	/**
	 * @expectedException CException
	 */
	public function testChunkedCorruptedContent()
	{
		$stream=fopen(dirname(__FILE__).DIRECTORY_SEPARATOR.'_fixtures'.DIRECTORY_SEPARATOR.'chunked-corrupted.txt','rb');
		stream_filter_append($stream,'yiitestdechunkfilter');
		stream_get_contents($stream);
	}
}