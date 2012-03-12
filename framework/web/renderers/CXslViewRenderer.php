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
	public $recursionDepth=1;
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
		if(0)
		{
			header('Content-type: application/xml; charset=' . Yii::app()->charset);
			echo $doc->saveXML();
			Yii::app()->end();
		}
		
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
		if($data===null)
			return;
		if($data instanceof CModel)
		{
			$this->serialize($doc, $parent, $data->attributes, $level);
			if($level<=0)
				return;
			if($data instanceof CActiveRecord)
			{
				foreach($data->relations() as $name=>$relation)
				{
					switch($relation[0])
					{
						case CActiveRecord::STAT:
							$attr=$doc->createAttribute($name);
							$attr->appendChild($doc->createTextNode($data->getRelated($name)));
							$parent->appendChild($attr);
							break;
						case CActiveRecord::HAS_ONE:
						case CActiveRecord::BELONGS_TO:
							$relNode=$doc->createElement($name);
							$this->serialize($doc, $relNode, $data->getRelated($name), $level-1);
							$parent->appendChild($relNode);
							break;
						case CActiveRecord::HAS_MANY:
						case CActiverecord::MANY_MANY:
							foreach($data->getRelated($name) as $model)
							{
								$relNode=$doc->createElement($name);
								$this->serialize($doc, $relNode, $model, $level-1);
								$parent->appendChild($relNode);
							}
							break;
						default:
							Yii::log("Found relation of unknown type {$relation[0]}. Help?", CLogger::LEVEL_WARNING);
					}
				}
			}
		}
		else
		{
			foreach($data as $name=>$value)
			{
				if(is_numeric($name))
					$name='child'.$name;
				
				
				if($value instanceof CModel)
				{
					$node=$doc->createElement(get_class($value));
					$this->serialize($doc, $node, $value, $level-1);
				}
				elseif(is_array($value) || $value instanceof CModel)
				{
					$node=$doc->createElement($name);
					$this->serialize($doc, $node, $value, $level);
				}
				else
				{
					$node=$doc->createAttribute($name);
					$node->appendChild($doc->createTextNode($value));
				}
				$parent->appendChild($node);
			}
		}
	}
}
