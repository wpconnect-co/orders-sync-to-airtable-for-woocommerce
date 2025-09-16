# Orders Sync to Airtable for WooCommerce

## Basic structure

### Overview

This plugins allows users to sync WooCommerce orders to Airtable.

### Folder structure

* `assets/` : contains CSS, JS and image files.
* `dev/`: contains development dependencies, separate from plugin dependencies. PHP Coding Standards, and some build utilities are in this folder.
* `includes/`: plugin classes. Contains admin interface classes, helper classes, as well as core abstract classes, sources and formatters. More on those just below.
* `views/`:  contains admin templates for the wizard screen.

### Inner workings

The core abstract classes can be found in the `includes`/ folder.

The Order_Module initializes code specific to the WooCommerce order export.

The `Airtable_Api_Client` class located in the `includes/` folder is used to manage Airtable API calls.

In the `includes/` folder, there are also two important set of classes.

* _Formatter Strategies_ are helper classes used to format or process special fields (datetime, etc...).
* _Sources_ are helper classes that expose and convert incoming WooCommerce fields to something easier to work with.

## Data / user flow

### Basic connection configuration

* A user starts the wizard
* Enter its Airtable’s access token
* He chooses a template ("WP connect WooCommerce Orders template" or "Start from scratch" (manual selection of a base / table from Airtable to export data to)
* Then he can map fields from WooCommerce to Airtable
* Finally he chooses sync option: manual or automatic

### Sync flow

When a sync is triggered, the actions below are processed :

The Exporter :
* load wizard settings
* gather data from WooCommerce orders
* chunk records and creates Action Scheduler tasks

The Action Scheduler :
* triggered by WP Cron
* sends records to process to the Exporter

The Exporter :
* checks if the content has already been created or not
* then creates or updates the content on Airtable
	* each field mapped in the wizard is processed / formatted
* when all orders have been processed, the sync ends.

The flow described above can change based on the Sync strategy set in the connection.

## Setting up development

**Clone the repository** :  `git clone https://github.com/wpconnect-co/orders-sync-to-airtable-for-woocommerce.git orders-sync-to-airtable-for-woocommerce`.

**Install dependencies** : `composer install`

**Install dev dependencies** : `cd dev && composer install`

**Import WPC Sync UI library** : `cd assets/src && git clone git@github.com:wpconnect-co/wpc-sync-ui.git`

### Development scripts
A number of scripts in `package.json` are created to speed up development.

* `phpcbf`: Runs PHPCBF tool to autofix code formatting, using `.phpcs.xml.dist` file.
* `phpcs`: Runs PHPCS, using the same config file.
* `archive`: Creates a zip folder of the plugin. Last step in the build process.

