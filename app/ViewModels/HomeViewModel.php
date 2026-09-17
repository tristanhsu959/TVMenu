<?php

namespace App\ViewModels;

use App\Facades\AppManager;
use App\Enums\FormAction;
use App\Enums\Functions;
use App\ViewModels\Attributes\attrStatus;
use App\ViewModels\Attributes\attrActionBar;
use Illuminate\Support\Fluent;

class HomeViewModel extends Fluent
{
	use attrStatus, attrActionBar;
	
	public function __construct()
	{
		$this->function = Functions::HOME;
		$this->action	= NULL; #enum form action
		$this->backRoute= FALSE;
		$this->success();	#default
		$this->hasSetPassword();
	}
	
	public function hasSetPassword()
	{
		$currentUser = AppManager::getCurrentUser();
		return $currentUser->hasSetPassword;
	}
	
	public function hasSetEmail()
	{
		$currentUser = AppManager::getCurrentUser();
		
		return empty($currentUser->email) ? FALSE : TRUE;
	}
}