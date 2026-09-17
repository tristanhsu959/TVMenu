<?php

namespace App\Listeners;

use App\Events\LoginAccess;
use App\Repositories\AccessLogRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LoginAccessNotification
{
    /**
     * Create the event listener.
     */
    public function __construct(protected AccessLogRepository $_repository, )
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(LoginAccess $event): void
    {
        try
		{ 
			$userId = $event->userId;
			$userAccount = $event->userAccount;
			
			$this->_repository->insert($userId, $userAccount);
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
		}
    }
}
