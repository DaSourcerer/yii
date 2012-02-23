<?php
class CXslViewRenderer extends CApplicationComponent implements IViewRenderer {
	public $fileExtension='.xsl';
	
	/**
	 * @see http://www.php.net/manual/en/libxml.constants.php
	 * @var int
	 */
	public $xslOptions=0;
		
	/**
	 * The root element' name of the source xml.
	 * There is little sense in changing this unless you are doing something super fancy with your xsl/t;  
	 * @var string
	 */
	public $rootElement='data';
	
	/**
	 * Limit for relation depth
	 * @var int
	 */
	public $recursionDepth=3;
	/**
	 * The XSL/T processor
	 * @var XSLTProcessor
	 */
	private $processor;
	
	/**
	 * The XSL stylesheet
	 * @var DOMDocument
	 */
	private $stylesheet;
	
	public function init()
	{
		if(!extension_loaded('xsl'))
			throw new CException('The php-xsl module is required!');
		$this->processor=new XSLTProcessor;
		$this->stylesheet=new DOMDocument;
		parent::init();
	}
	
	public function renderFile($context, $sourceFile, $data=array(), $return)
	{
		$this->stylesheet->load($sourceFile, $this->xslOptions);		
		$this->processor->importStylesheet($this->stylesheet);
		
		$doc=new DOMDocument('1.0', Yii::app()->charset);
		$root=$doc->appendChild($doc->createElement($this->rootElement));

		foreach($data as $name=>$value)
		{
			$node;
			if(is_array($value) || $value instanceof CModel)
			{
				$node=$doc->createElement($name);
				$this->serialize($doc, $node, $value, $this->recursionDepth);
			}
			else
			{
				$node=$doc->createAttribute($name);
				$node->appendChild($doc->createTextNode($value));
			}
			$root->appendChild($node);
		}
		
		$attr=$doc->createAttributeNS('http://xml.yiiframework.com', 'yii:memory');
		$attr->appendChild($doc->createTextNode(Yii::getLogger()->getMemoryUsage()));
		$root->appendChild($attr);
		$attr=$doc->createAttributeNS('http://xml.yiiframework.com', 'yii:time');
		$attr->appendChild($doc->createTextNode(Yii::getLogger()->getExecutionTime()));
		$root->appendChild($attr);
		
		$this->processor->registerPHPFunctions();
		
		//TODO: Find another method for debugging the source xml
		/*header('Content-type: application/xml; charset=' . Yii::app()->charset);
		echo $doc->saveXML();
		Yii::app()->end();*/
		
		$result=$this->processor->transformToXML($doc);
		if($result===false)
			throw new CException('Error while transforming ' . $sourceFile);

		if($return)
			return $result;
		echo $result;
	}
	
	/**
	 * 
	 * @param DOMDocument $doc
	 * @param DOMNode $parent
	 * @param mixed $data
	 */
	private function serialize(DOMDocument &$doc, DOMNode &$parent, $data, $level)
	{
		foreach($data as $name=>$value)
		{
			$node;
			if($value instanceof CModel)
			{
				if($level==0)
					continue;
				$node=$doc->createElement(get_class($value));
				$this->serialize($doc, $node, $value, $level--);
			}
			else
			{
				if(is_numeric($name))
					$name = 'child'.$name;
				
				$node=$doc->createAttribute($name);
				if(is_array($value))
					$this->serialize($doc, $node, $value, $level);
				else
					$node->appendChild($doc->createTextNode($value));
			}
			$parent->appendChild($node);
		}
	}
}
