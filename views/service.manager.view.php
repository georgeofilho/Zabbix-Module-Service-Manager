<?php
/**
 * @var CView $this
 * @var array $data
 */

try {
	$widget = (new CHtmlPage())
		->setTitle(_('Service Manager'))
		->setDocUrl(CDocHelper::getUrl('modules'));

	$html = '
	<div class="sm-container">
		<div class="sm-sidebar">
			<div class="sm-sidebar-header">Servers (Hosts)</div>
			<div class="sm-host-list">';

	foreach ($data['hosts'] as $host) {
		$dot_class = 'grey'; // Default: unknown or disabled
		if ($host['status'] == 1 || !$host['is_eligible']) {
			$dot_class = 'grey';
		} elseif ($host['agent_available'] == 1) {
			$dot_class = 'green';
		} elseif ($host['agent_available'] == 2) {
			$dot_class = 'red';
		}

		$active_class = ($data['selected_hostid'] == $host['hostid']) ? 'active' : '';
		$url = (new CUrl('zabbix.php'))->setArgument('action', 'servicemanager.view')->setArgument('hostid', $host['hostid'])->getUrl();
		
		if (!$host['is_eligible'] || $host['status'] == 1) {
			$url = 'javascript:void(0);';
			$active_class .= ' disabled';
		}

		$html .= '
				<a href="'.$url.'" class="sm-host-item '.$active_class.'">
					<div class="sm-host-status-dot '.$dot_class.'"></div>
					<div class="sm-host-info">
						<div class="sm-host-name">'.htmlspecialchars($host['name']).'</div>
						<div class="sm-host-subname">'.htmlspecialchars($host['name']).'</div>
					</div>
					<div class="sm-host-os">'.htmlspecialchars($host['os_type']).'</div>
				</a>';
	}

	$html .= '
			</div>
		</div>';

	if ($data['selected_host']) {
		$host = $data['selected_host'];
		$os_label = $host['os_type']; // Use the dynamically discovered OS type
		
		$html .= '
		<div class="sm-main">
			<div class="sm-header">
				<div class="sm-header-os">'.$os_label.'</div>
				<div class="sm-header-details">
					<h2>'.htmlspecialchars($host['name']).'</h2>
					<p>Technical name: '.htmlspecialchars($host['name']).' | ID: '.$host['hostid'].'</p>
				</div>
			</div>
			<div class="sm-panels">
				<div class="sm-panel">
					<h3>Add Service</h3>
					<p class="sub">Creates monitoring item and trigger on this server</p>
					
					<form action="zabbix.php" method="POST" id="sm-form">
						<input type="hidden" name="action" value="servicemanager.save">
						<input type="hidden" name="hostid" value="'.$host['hostid'].'">
						<input type="hidden" id="edit_itemid" name="itemid" value="">
						
						<div class="sm-form-group">
							<label class="sm-form-label">Item Operating System</label>
							<div class="sm-btn-group">
								<input type="radio" id="os_win" name="os_type" value="windows" '.($os_label === 'WINDOWS' ? 'checked' : '').'>
								<label for="os_win">Windows</label>
								<input type="radio" id="os_lin" name="os_type" value="linux" '.($os_label !== 'WINDOWS' ? 'checked' : '').'>
								<label for="os_lin">Linux</label>
							</div>
						</div>

						<div class="sm-form-group">
							<label class="sm-form-label">Service Name <span class="sm-required">*</span></label>
							<input type="text" name="service_name" class="sm-input" placeholder="E.g.: wuauserv or nginx" required maxlength="255">
							<div class="sm-form-hint">Exact name used by the operating system.</div>
						</div>

						<div class="sm-form-group">
							<label class="sm-form-label">Display Name (Optional)</label>
							<input type="text" name="display_name" class="sm-input" placeholder="E.g.: Windows Update or Web Nginx" maxlength="255">
							<div class="sm-form-hint">How the alert will be named.</div>
						</div>

						<div class="sm-form-group">
							<label class="sm-form-label">Monitoring Type</label>
							<select name="monitor_type" id="monitor_type" class="sm-input">
								<option value="systemd">Systemd Unit (Agent 2)</option>
								<option value="proc">Process Count (Agent)</option>
							</select>
						</div>

						<div class="sm-form-group">
							<label class="sm-form-label">Agent Type</label>
							<select name="agent_type" id="agent_type" class="sm-input">
								<option value="0">Zabbix Agent (Passive)</option>
								<option value="7">Zabbix Agent (Active)</option>
							</select>
						</div>

						<div class="sm-form-group">
							<label class="sm-form-label">Update Interval</label>
							<select name="delay" id="delay" class="sm-input">
								<option value="30s">30 Seconds</option>
								<option value="1m" selected>1 Minute</option>
								<option value="3m">3 Minutes</option>
								<option value="5m">5 Minutes</option>
							</select>
						</div>

						<div class="sm-form-group">
							<label class="sm-form-label">Alert Severity</label>
							<div class="sm-sev-group">';
		
		foreach ($data['trigger_priorities'] as $priority => $name) {
			$class = 'sm-sev-' . $priority;
			$html .= '
								<input type="radio" id="sev_'.$priority.'" name="priority" value="'.$priority.'" '.($priority == 2 ? 'checked' : '').'>
								<label for="sev_'.$priority.'" class="'.$class.'">'.$name.'</label>';
		}

		$html .= '
							</div>
						</div>

						<div class="sm-btn-actions">
							<button type="submit" id="submit_btn" class="sm-submit">Create Monitoring</button>
							<button type="button" id="cancel_btn" class="sm-submit cancel-mode" style="display:none;" onclick="cancelEdit()">Cancel</button>
						</div>
					</form>
				</div>
				
				<div class="sm-panel">
					<div class="sm-panel-header">
						<h3>Active Services</h3>
						<div class="sm-service-count">
							'.count($data['active_services']).' services
						</div>
					</div>
					<div class="sm-services-container">';
					
		if (empty($data['active_services'])) {
			$html .= '
						<div class="sm-empty">
							<div class="sm-empty-icon">-</div>
							<div>No custom service being<br>monitored on this server.</div>
						</div>';
		} else {
			foreach ($data['active_services'] as $item) {
				$status_class = 'sm-badge-green';
				$status_text = 'Active';
				if ($item['status'] != 0) {
					$status_class = 'sm-badge-red';
					$status_text = 'Inactive';
				} elseif ($item['state'] == 1) {
					$status_class = 'sm-badge-grey';
					$status_text = 'Not Supported';
				}

				$service_name = $item['name'];
				$monitor_type = 'systemd';
				$key = $item['key_'];
				if (preg_match('/^systemd\.unit\.info\[(.*)\]$/', $key, $m)) {
					$service_name = trim($m[1], '"');
					$monitor_type = 'systemd';
				} elseif (preg_match('/^proc\.num\[(.*)\]$/', $key, $m)) {
					$service_name = trim($m[1], '"');
					$monitor_type = 'proc';
				} elseif (preg_match('/^service\.info\[(.*),state\]$/', $key, $m)) {
					$service_name = trim($m[1], '"');
					$monitor_type = 'systemd';
				}

				$display_name = $item['name'];
				if (strpos($display_name, 'Service: ') === 0) {
					$display_name = substr($display_name, 9);
				}
				
				if ($display_name === $service_name) {
					$display_name = '';
				}

				$priority = 2; // Default
				if (!empty($item['triggers'])) {
					$priority = $item['triggers'][0]['priority'];
				}

				$delete_url = (new CUrl('zabbix.php'))
					->setArgument('action', 'servicemanager.delete')
					->setArgument('hostid', $host['hostid'])
					->setArgument('itemid', $item['itemid'])
					->getUrl();

				$html .= '
						<div class="sm-service-item">
							<div>
								<div class="sm-service-name">'.htmlspecialchars($item['name']).'</div>
								<div class="sm-service-key">'.htmlspecialchars($item['key_']).'</div>
							</div>
							<div class="sm-service-status-wrapper">
								<div class="sm-status-badge '.$status_class.'">'.$status_text.'</div>
								<div class="sm-actions">
									<button class="sm-action-btn edit" 
										data-itemid="'.htmlspecialchars($item['itemid'], ENT_QUOTES, 'UTF-8').'"
										data-rawname="'.htmlspecialchars($service_name, ENT_QUOTES, 'UTF-8').'"
										data-displayname="'.htmlspecialchars($display_name, ENT_QUOTES, 'UTF-8').'"
										data-monitortype="'.htmlspecialchars($monitor_type, ENT_QUOTES, 'UTF-8').'"
										data-priority="'.(int)$priority.'"
										data-agenttype="'.(int)$item['type'].'"
										data-delay="'.htmlspecialchars($item['delay'], ENT_QUOTES, 'UTF-8').'"
										onclick="editService(this)" title="Edit">✏️</button>
									<a href="'.$delete_url.'" class="sm-action-btn delete" onclick="return confirm(\'Delete this monitoring?\')" title="Delete">🗑️</a>
								</div>
							</div>
						</div>';
			}
		}
					
		$html .= '
					</div>
				</div>
			</div>
		</div>';
	} else {
		$html .= '
		<div class="sm-main">
			<div class="sm-empty">
				<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
					<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
					<circle cx="12" cy="7" r="4"></circle>
				</svg>
				<h2>Select a Server</h2>
				<p>Choose an eligible server on the sidebar to manage its services.</p>
			</div>
		</div>';
	}

	$html .= '</div>';

	$html .= '
	<div style="text-align:center; padding: 15px; font-size: 12px; opacity: 0.5; margin-top: 10px; font-weight: bold;">
		&copy; 2006-2026 by <a href="https://georgeofilho.github.io" target="_blank">George Filho - SuporTI Soluções Técnicas</a>. All rights reserved.
	</div>';

	$html .= '
	<script>
		function editService(btn) {
			document.getElementById("edit_itemid").value = btn.dataset.itemid;
			document.querySelector("input[name=\'service_name\']").value = btn.dataset.rawname;
			document.querySelector("input[name=\'display_name\']").value = btn.dataset.displayname;
			document.getElementById("monitor_type").value = btn.dataset.monitortype;
			document.getElementById("sev_" + btn.dataset.priority).checked = true;
			document.getElementById("agent_type").value = btn.dataset.agenttype;
			document.getElementById("delay").value = btn.dataset.delay;
			
			var submitBtn = document.getElementById("submit_btn");
			submitBtn.textContent = "Save Changes";
			submitBtn.classList.add("save-mode");
			
			document.getElementById("cancel_btn").style.display = "inline-flex";
		}
		
		function cancelEdit() {
			document.getElementById("edit_itemid").value = "";
			document.getElementById("sm-form").reset();
			
			var submitBtn = document.getElementById("submit_btn");
			submitBtn.textContent = "Create Monitoring";
			submitBtn.classList.remove("save-mode");
			
			document.getElementById("cancel_btn").style.display = "none";
		}

		document.getElementById("sm-form").addEventListener("submit", function() {
			var submitBtn = document.getElementById("submit_btn");
			var cancelBtn = document.getElementById("cancel_btn");
			submitBtn.classList.add("loading-mode");
			submitBtn.innerHTML = "Please wait... <span class=\'sm-spinner\'></span>";
			submitBtn.style.pointerEvents = "none";
			cancelBtn.style.pointerEvents = "none";
			cancelBtn.style.opacity = "0.5";
		});
	</script>';

	$widget->show();
	echo $html;

} catch (\Throwable $e) {
	echo (new CTag('div', true, "View Error: " . $e->getMessage()))->addClass('msg-bad');
}
