<?php
class FeedController extends Controller {
	public function init()
	{
		//Please do not blindly copy this. We can only do so because all views reached via this controller are xsl views.
		Yii::app()->setComponent('viewRenderer', new CXslViewRenderer);
		parent::init();
	}
	
	public function actionRss()
	{
		$result=$this->renderPartial('rss', array(
				'posts'=>Post::model()->findAll(),
		), true);
		header('Content-type: application/rss+xml; charset=' . Yii::app()->charset);
		echo $result;
	}
	
	public function actionAtom()
	{
		$result=$this->renderPartial('atom', array(
				'posts'=>Post::model()->findAll(),
		), true);
		header('Content-type: application/atom+xml; charset=' . Yii::app()->charset);
		echo $result;
	}
}