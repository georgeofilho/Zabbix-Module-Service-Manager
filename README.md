# Zabbix Service Manager

_Read this in other languages: [![pt-br](https://img.shields.io/badge/lang-pt--br-green.svg)](README.pt-br.md)_

**Zabbix Service Manager** is a modern and intuitive module for the Zabbix Web Interface designed to add specific service monitoring capabilities directly to hosts. It seamlessly integrates into the Zabbix UI, adding a dedicated view for managing and monitoring host-specific services.

Developed by **George Filho - SuporTI Soluções Técnicas**.

---

## 🎯 Main Features

- **Dedicated View:** Provides a dedicated view for managing and monitoring services for each specific host.
- **Easy Management:** Built-in actions to view, add, save, and delete specific service configurations.
- **Simple Integration:** Simple installation as a plug-and-play Zabbix module.
- **Native Zabbix Integration:** Securely implemented using native Zabbix UI classes, fully compliant with Zabbix 7.0+ architecture.

---

## 📋 Prerequisites

Before installing and using this module, ensure that:

1. You are running **Zabbix Frontend 6.0 or higher** (optimized for 7.0+).
2. The web server user (e.g., `www-data`, `apache`, or `nginx`) has read permissions for the module directory.

---

## 🚀 Installation

Installing modules in the Zabbix frontend is simple and fast.

1. **Download or clone this repository:**
   Download the module files to your server.

2. **Copy to the Zabbix modules directory:**
   Move the module folder to the `modules` directory of your Zabbix web interface. 
   **CRITICAL:** The folder name must be exactly `servicemanager` to match the module configuration.

   The default path is usually:
   ```bash
   /usr/share/zabbix/ui/modules/servicemanager
   # or
   /usr/share/zabbix/modules/servicemanager
   ```

3. **Register the Module in the Frontend:**
   - Log into the Zabbix web interface as a Super Admin.
   - Navigate to **Administration** → **General** → **Modules**.
   - Click the **"Scan directory"** button in the top right corner.
   - The **"Zabbix Service Manager"** module should appear in the list.
   - Click the **Disabled** link in the Status column to change it to **Enabled**.

---

## 💻 How to Use

After activation, a new menu will be available to facilitate your daily operations.

1. Navigate to **Monitoring** → **Service Manager** in the main menu.
2. The module's view will open, allowing you to select and monitor services specific to your hosts.
3. Use the interface to add new services or remove existing ones as necessary.

---

## 🛠️ Technical Details & Development References

This module is built following the Zabbix Module Development Guidelines:

- **MVC Architecture:**
  - **Controllers:** Located in `actions/`. Handles routing and data actions like view, save, and delete.
  - **Views:** Located in `views/`. Renders the UI structure.
- **Security (CSP):** Stylesheets are loaded via the `assets` property in `manifest.json`.

## 📂 Directory Structure

This is the standard file structure of the Zabbix Service Manager module:

```text
servicemanager/
├── manifest.json                  # Module configuration and asset registration
├── Module.php                     # Core module class and menu registration
├── README.md                      # English documentation
├── README.pt-br.md                # Portuguese documentation
├── README_modelo.md               # Model documentation (reference)
├── actions/
│   ├── ServiceManagerDelete.php   # Action to delete services
│   ├── ServiceManagerSave.php     # Action to save services
│   └── ServiceManagerView.php     # Main page view controller
├── assets/
│   ├── css/
│   │   └── service.manager.css    # Module styles
│   └── images/
│       ├── image-01.png           # Screenshots
│       ├── image-02.png
│       ├── image-03.png
│       └── image-04.png
└── views/
    └── service.manager.view.php   # UI structure and components
```

---

## 🖼️ Screenshots

This section demonstrates the visual capabilities of the module.

### 1. Main Dashboard View
![Main Dashboard](assets/images/image-01.png)

*The primary interface of the Service Manager.*

### 2. Service Details
![Service Details](assets/images/image-02.png)

*Visualizing specific services.*

### 3. Adding/Editing Services
![Adding/Editing](assets/images/image-03.png)

*Managing host service configurations.*

### 4. Advanced Options
![Advanced Options](assets/images/image-04.png)

*Additional features of the Service Manager.*

---

## 📄 License & Credits

**Copyright &copy; 2006-2026 by [George Filho - SuporTI Soluções Técnicas](https://georgeofilho.github.io).**

All rights reserved.
