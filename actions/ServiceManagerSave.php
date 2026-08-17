<?php

namespace Modules\ServiceManager\Actions;

use CController;
use CControllerResponseRedirect;
use API;
use CMessageHelper;
use CUrl;

class ServiceManagerSave extends CController {

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
			'itemid' => 'string',
			'hostid' => 'required|db hosts.hostid',
			'os_type' => 'required|in linux,windows',
			'service_name' => 'required|string|not_empty',
			'display_name' => 'string',
			'monitor_type' => 'required|in systemd,proc',
			'priority' => 'required|in 0,1,2,3,4,5',
			'agent_type' => 'required|in 0,7',
			'delay' => 'required|string'
		];

		$ret = $this->validateInput($fields);

		if (!$ret) {
			$this->setResponse(
				new CControllerResponseRedirect((new CUrl('zabbix.php'))
					->setArgument('action', 'servicemanager.view')
				)
			);
		}

		return $ret;
	}

	protected function checkPermissions(): bool {
		return true;
	}

	protected function doAction(): void {
		try {
			$hostid = $this->getInput('hostid');
			$os_type = $this->getInput('os_type');
			$service_name = $this->getInput('service_name');
			$display_name = $this->hasInput('display_name') ? trim($this->getInput('display_name')) : '';
			$monitor_type = $this->getInput('monitor_type', 'proc');
			$priority = $this->getInput('priority');
			$agent_type = $this->getInput('agent_type');
			$delay = $this->getInput('delay');

			// Fetch host information to get technical name and interface
			$hosts = API::Host()->get([
				'output' => ['host'],
				'selectInterfaces' => ['interfaceid', 'type'],
				'hostids' => $hostid
			]);

			if (!$hosts) {
			CMessageHelper::setErrorTitle(_('Host not found.'));
			$this->setResponse(new CControllerResponseRedirect((new CUrl('zabbix.php'))->setArgument('action', 'servicemanager.view')));
			return;
		}

		$host = $hosts[0];
		$host_name = $host['host'];
		$interfaceid = null;

		// Search for Zabbix Agent Interface (type 1)
		foreach ($host['interfaces'] as $interface) {
			if ($interface['type'] == 1) { // 1 = INTERFACE_TYPE_AGENT
				$interfaceid = $interface['interfaceid'];
				break;
			}
		}

		if (!$interfaceid) {
			CMessageHelper::setErrorTitle(_('Cannot add monitoring: No Zabbix agent interface found on host.'));
			$this->setResponse(new CControllerResponseRedirect((new CUrl('zabbix.php'))->setArgument('action', 'servicemanager.view')->setArgument('hostid', $hostid)));
			return;
		}

		$item_display = ($display_name !== '') ? $display_name : $service_name;

		// The Zabbix API expression parser uses slashes as absolute boundaries.
		// It strictly rejects double quotes around hostnames, even if they contain spaces.
		$host_expr = $host_name;

		if ($os_type === 'linux') {
			if ($monitor_type === 'systemd') {
				$key = 'systemd.unit.info[' . $service_name . ']';
				$value_type = 1; // ITEM_VALUE_TYPE_STR
				// Using find() is the native Zabbix 6.0/7.0 way for strings
				$trigger_expression = 'find(/' . $host_expr . '/' . $key . ',,"eq","active")=0';
			} else {
				$key = 'proc.num[' . $service_name . ']';
				$value_type = 3; // ITEM_VALUE_TYPE_UINT64
				$trigger_expression = 'last(/' . $host_expr . '/' . $key . ')<1';
			}
			$name = 'Service: ' . $item_display;
			$trigger_name = $item_display . ' is down on {HOST.NAME}';
		} else {
			$key = 'service.info[' . $service_name . ',state]';
			$name = 'Service: ' . $item_display;
			$value_type = 3; // ITEM_VALUE_TYPE_UINT64
			$trigger_expression = 'last(/' . $host_expr . '/' . $key . ')>0';
			$trigger_name = $item_display . ' is down on {HOST.NAME}';
		}

		$itemid = $this->hasInput('itemid') ? $this->getInput('itemid') : null;

		if ($itemid !== null && $itemid !== '') {
			// UPDATE FLOW
			$existing_item = API::Item()->get([
				'output' => ['itemid'],
				'selectTriggers' => ['triggerid'],
				'itemids' => $itemid
			]);
			
			if (!$existing_item) {
				throw new \Exception(_('Item not found for update.'));
			}

			$update_result = API::Item()->update([
				'itemid' => $itemid,
				'name' => $name,
				'key_' => $key,
				'type' => (int)$agent_type,
				'delay' => $delay
			]);

			if (!$update_result) {
				throw new \Exception(_('Failed to update item.'));
			}

			if (!empty($existing_item[0]['triggers'])) {
				$triggerid = $existing_item[0]['triggers'][0]['triggerid'];
				$t_update = API::Trigger()->update([
					'triggerid' => $triggerid,
					'description' => $trigger_name,
					'expression' => $trigger_expression,
					'priority' => $priority
				]);
				if (!$t_update) {
					throw new \Exception(_('Failed to update trigger.'));
				}
			}

			CMessageHelper::setSuccessTitle(_('Service monitoring updated successfully.'));

		} else {
			// CREATE FLOW
			$item_result = API::Item()->create([
				'name' => $name,
				'key_' => $key,
				'hostid' => $hostid,
				'type' => (int)$agent_type, // 0 = Passivo, 7 = Ativo
				'value_type' => $value_type,
				'interfaceid' => $interfaceid,
				'delay' => $delay,
				'tags' => [
					['tag' => 'Application', 'value' => 'Service Manager']
				]
			]);

			if (!$item_result) {
				throw new \Exception(_('Failed to create item.'));
			}

			$trigger_result = API::Trigger()->create([
				'description' => $trigger_name,
				'expression' => $trigger_expression,
				'priority' => $priority,
				'tags' => [
					['tag' => 'Application', 'value' => 'Service Manager']
				]
			]);

			if (!$trigger_result) {
				throw new \Exception(_('Failed to create trigger.'));
			}

			CMessageHelper::setSuccessTitle(_('Service monitoring added successfully.'));
		}
		
	} catch (\Throwable $e) {
		CMessageHelper::setErrorTitle(_('Error adding monitoring: ' . $e->getMessage()));
	}

	$response = new CControllerResponseRedirect((new CUrl('zabbix.php'))->setArgument('action', 'servicemanager.view')->setArgument('hostid', $hostid));
	$this->setResponse($response);
}
}
