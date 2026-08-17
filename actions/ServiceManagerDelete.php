<?php
namespace Modules\ServiceManager\Actions;

use CController;
use CControllerResponseRedirect;
use CMessageHelper;
use CUrl;
use API;

class ServiceManagerDelete extends CController {

	protected function init(): void {
		if (method_exists($this, 'disableCsrfValidation')) {
			$this->disableCsrfValidation();
		}
		if (method_exists($this, 'disableSIDValidation')) {
			$this->disableSIDValidation();
		}
		if (method_exists($this, 'disableSIDvalidation')) {
			$this->disableSIDvalidation();
		}
	}

	protected function checkInput(): bool {
		$fields = [
			'itemid' => 'required|db items.itemid',
			'hostid' => 'required|db hosts.hostid'
		];
		return $this->validateInput($fields);
	}

	protected function checkPermissions(): bool {
		return true;
	}

	protected function doAction(): void {
		$itemid = $this->getInput('itemid');
		$hostid = $this->getInput('hostid');

		try {
			$result = API::Item()->delete([$itemid]);

			if ($result) {
				CMessageHelper::setSuccessTitle(_('Service monitoring deleted successfully.'));
			} else {
				throw new \Exception(_('Failed to delete item.'));
			}
		} catch (\Throwable $e) {
			CMessageHelper::setErrorTitle(_('Error deleting monitoring: ' . $e->getMessage()));
		}

		$response = new CControllerResponseRedirect((new CUrl('zabbix.php'))->setArgument('action', 'servicemanager.view')->setArgument('hostid', $hostid));
		$this->setResponse($response);
	}
}
