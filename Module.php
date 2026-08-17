<?php

namespace Modules\ServiceManager;

use Zabbix\Core\CModule;
use APP;
use	Cmenu;
use CMenuItem;

class Module extends CModule {

	public function init(): void {
		APP::Component()->get('menu.main')
			->findOrAdd(_('SuporTI'))
			->setIcon('zi-server')
			->getSubmenu()
			->add((new CMenuItem(_('Service Manager')))
				->setAction('servicemanager.view')
			);
	}
}
