<?php

namespace Modules\ServiceManager\Actions;

use CController;
use CControllerResponseData;
use API;

class ServiceManagerView extends CController {

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
			'hostid' => 'db hosts.hostid'
		];
		return $this->validateInput($fields);
	}

	protected function checkPermissions(): bool {
		return true;
	}

	protected function doAction(): void {
		try {
			// Fetch all hosts for the sidebar
			$all_hosts = API::Host()->get([
				'output' => ['hostid', 'name', 'status'],
				'selectInterfaces' => ['type', 'available'],
				'selectParentTemplates' => ['name'],
				'editable' => true,
				'sortfield' => 'name'
			]);

			foreach ($all_hosts as &$h) {
				$h['is_eligible'] = false;
				$h['os_type'] = 'S/O'; // default
				$h['agent_available'] = 0; // 0=unknown, 1=available, 2=unavailable

				// Determine OS from linked templates
				if (isset($h['parentTemplates']) && is_array($h['parentTemplates'])) {
					$templates = array_column($h['parentTemplates'], 'name');
					$templates_str = strtolower(implode(' ', $templates));
					
					if (strpos($templates_str, 'windows') !== false) {
						$h['os_type'] = 'WINDOWS';
					} elseif (strpos($templates_str, 'linux') !== false || strpos($templates_str, 'ubuntu') !== false || strpos($templates_str, 'debian') !== false || strpos($templates_str, 'centos') !== false) {
						$h['os_type'] = 'LINUX';
					} elseif (strpos($templates_str, 'vmware') !== false || strpos($templates_str, 'hyper-v') !== false || strpos($templates_str, 'proxmox') !== false) {
						$h['os_type'] = 'HYPERVISOR';
					} elseif (strpos($templates_str, 'docker') !== false) {
						$h['os_type'] = 'DOCKER';
					}
				}

				// Fallback if no specific template matched
				if ($h['os_type'] === 'S/O') {
					if (stripos($h['name'], 'windows') !== false) {
						$h['os_type'] = 'WINDOWS';
					} elseif (stripos($h['name'], 'linux') !== false || stripos($h['name'], 'ubuntu') !== false) {
						$h['os_type'] = 'LINUX';
					} elseif (stripos($h['name'], 'proxmox') !== false || stripos($h['name'], 'vmware') !== false) {
						$h['os_type'] = 'HYPERVISOR';
					}
				}

				foreach ($h['interfaces'] as $interface) {
					if ($interface['type'] == 1 || $interface['type'] == 2) { // 1 = Zabbix Agent, 2 = SNMP
						$h['is_eligible'] = true;
						// Prefer Zabbix Agent status if both exist, otherwise use SNMP status
						if ($h['agent_available'] == 0 || $interface['type'] == 1) {
							$h['agent_available'] = $interface['available'];
						}
					}
				}
			}
			unset($h);

			$selected_hostid = $this->hasInput('hostid') ? $this->getInput('hostid') : null;
			$selected_host = null;
			$active_services = [];

			if ($selected_hostid) {
				// Find specific host in the already fetched list to retain os_type
				foreach ($all_hosts as $h) {
					if ($h['hostid'] == $selected_hostid) {
						$selected_host = $h;
						break;
					}
				}

				if ($selected_host) {
					
					// Fetch active services (items)
					$active_services = API::Item()->get([
						'output' => ['itemid', 'name', 'key_', 'state', 'status', 'type', 'delay'],
						'selectTriggers' => ['triggerid', 'priority'],
						'hostids' => $selected_hostid,
						'tags' => [
							['tag' => 'Application', 'value' => 'Service Manager']
						]
					]);
				}
			}

			$data = [
				'hosts' => $all_hosts,
				'selected_hostid' => $selected_hostid,
				'selected_host' => $selected_host,
				'active_services' => $active_services,
				'trigger_priorities' => [
					1 => _('Info'),
					2 => _('Warning'),
					3 => _('Average'),
					4 => _('High'),
					5 => _('Disaster')
				]
			];

			$response = new CControllerResponseData($data);
			$response->setTitle(_('Service Manager'));
			$this->setResponse($response);
		} catch (\Throwable $e) {
			\CMessageHelper::setErrorTitle(_('Error loading Service Manager: ' . $e->getMessage()));
			$response = new CControllerResponseData([
				'hosts' => [],
				'selected_hostid' => null,
				'selected_host' => null,
				'active_services' => [],
				'trigger_priorities' => []
			]);
			$response->setTitle(_('Service Manager'));
			$this->setResponse($response);
		}
	}
}
