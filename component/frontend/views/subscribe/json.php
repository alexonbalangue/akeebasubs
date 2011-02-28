<?php

class ComAkeebasubsViewSubscribeJson extends KViewJson
{
	public function display()
	{
		$model = $this->getModel();
		if($model->get('action','display')) {
			$data = $model->getValidation();
			return json_encode($data);
		} else {
			return parent::display();
		}	
	}
}