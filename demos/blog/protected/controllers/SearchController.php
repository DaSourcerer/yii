<?php
class SearchController extends Controller
{
	public function init()
	{
		//Please do not blindly copy this. We can only do so because all views reached via this controller are xsl views.
		Yii::app()->setComponent('viewRenderer', new CXslViewRenderer);
		parent::init();
	}
	
	public function actionPlugin()
	{
		$result=$this->renderPartial('plugin', array(
		), true);
		header('Content-Type: application/opensearchdescription+xml; charset=' . Yii::app()->charset);
		echo $result;
	}
	
	public function actionSearch()
	{
		
	}
}