<?php

namespace App\ViewModels\Attributes;

use App\Enums\FormAction;
use Illuminate\Support\Fluent;

#Search form依功能狀況較不一致故不抽出
#Statistics response
trait attrResponse
{
	/* Statistics的回傳data */
	public function responseBaseData()
	{
		$token = data_get($this->statistics, 'exportToken', NULL);
		
		$data['status'] 		= $this->status();
		$data['isInit']			= empty($this->statistics);
		$data['exportAction'] 	= empty($token) ? '' : $this->getFormAction(FormAction::EXPORT);
		$data['brandCode']		= $this->brand->code(); #判別當前的功能brand, 故不能取自statistics
			
		return $data;
	}
	
	#為了方便管理,獨立取
	public function statisticsData($target = NULL)
	{
		if (is_null($target))
			return $this->statistics;
		
		return $this->get("statistics.{$target}");
	}
}