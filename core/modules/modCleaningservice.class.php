<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2025 SuperAdmin
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * 	\defgroup   cleaningservice     Module Cleaningservice
 *  \brief      Cleaningservice module descriptor.
 *
 *  \file       htdocs/cleaningservice/core/modules/modCleaningservice.class.php
 *  \ingroup    cleaningservice
 *  \brief      Description and activation file for module Cleaningservice
 */
include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';


/**
 *  Description and activation class for module Cleaningservice
 */
class modCleaningservice extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;

		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 500121; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'cleaningservice';

		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family = "other";

		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '90';

		// Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		//$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));
		// Module label (no space allowed), used if translation string 'ModuleCleaningserviceName' not found (Cleaningservice is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// DESCRIPTION_FLAG
		// Module description, used if translation string 'ModuleCleaningserviceDesc' not found (Cleaningservice is name of module).
		$this->description = "CleaningserviceDescription";
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "CleaningserviceDescription";

		// Author
		$this->editor_name = 'WILTEK SOFTWARE';
		$this->editor_url = 'https://wiltek-software.com';		// Must be an external online web site
		$this->editor_squarred_logo = '';					// Must be image filename into the module/img directory followed with @modulename. Example: 'myimage.png@cleaningservice'

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated', 'experimental_deprecated' or a version string like 'x.y.z'
		$this->version = '1.0';
		// Url to the file with your last numberversion of this module
		//$this->url_last_version = 'http://www.example.com/versionmodule.txt';

		// Key used in llx_const table to save module status enabled/disabled (where CLEANINGSERVICE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);

		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		// To use a supported fa-xxx css style of font awesome, use this->picto='xxx'
		$this->picto = 'cleaningservice@cleaningservice';

		// Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = array(
			// Set this to 1 if module has its own trigger directory (core/triggers)
			'triggers' => 1,
			// Set this to 1 if module has its own login method file (core/login)
			'login' => 0,
			// Set this to 1 if module has its own substitution function file (core/substitutions)
			'substitutions' => 0,
			// Set this to 1 if module has its own menus handler directory (core/menus)
			'menus' => 0,
			// Set this to 1 if module overwrite template dir (core/tpl)
			'tpl' => 0,
			// Set this to 1 if module has its own barcode directory (core/modules/barcode)
			'barcode' => 0,
			// Set this to 1 if module has its own models directory (core/modules/xxx)
			'models' => 1,
			// Set this to 1 if module has its own printing directory (core/modules/printing)
			'printing' => 0,
			// Set this to 1 if module has its own theme directory (theme)
			'theme' => 0,
			// Set this to relative path of css file if module has its own css file
			'css' => array(
				'/cleaningservice/css/cleaningservice.css.php',
			),
			// Set this to relative path of js file if module must load a js on all pages
			'js' => array(
				'/cleaningservice/js/cleaningservice.js.php',
			),
			// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
			/* BEGIN MODULEBUILDER HOOKSCONTEXTS */
			'hooks' => array(
				// 'data' => array(

				// ),
				//   'entity' => '0',
			),
			/* END MODULEBUILDER HOOKSCONTEXTS */
			// Set this to 1 if features of module are opened to external users
			'moduleforexternal' => 0,
			// Set this to 1 if the module provides a website template into doctemplates/websites/website_template-mytemplate
			'websitetemplates' => 0
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/cleaningservice/temp","/cleaningservice/subdir");
		$this->dirs = array("/cleaningservice/temp");

		// Config pages. Put here list of php page, stored into cleaningservice/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@cleaningservice");

		// Dependencies
		// A condition to hide module
		$this->hidden = getDolGlobalInt('MODULE_CLEANINGSERVICE_DISABLED'); // A condition to disable module;
		// List of module class names that must be enabled if this module is enabled. Example: array('always'=>array('modModuleToEnable1','modModuleToEnable2'), 'FR'=>array('modModuleToEnableFR')...)
		$this->depends = array();
		// List of module class names to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->requiredby = array();
		// List of module class names this module is in conflict with. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = array();

		// The language file dedicated to your module
		$this->langfiles = array("cleaningservice@cleaningservice");

		// Prerequisites
		$this->phpmin = array(7, 1); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(19, -3); // Minimum version of Dolibarr required by module
		$this->need_javascript_ajax = 0;

		// Messages at activation
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		//$this->automatic_activation = array('FR'=>'CleaningserviceWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(1 => array('CLEANINGSERVICE_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
		//                             2 => array('CLEANINGSERVICE_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
		// );
		$this->const = array();

		// Some keys to add into the overwriting translation tables
		/*$this->overwrite_translation = array(
			'en_US:ParentCompany'=>'Parent company or reseller',
			'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
		)*/

		if (!isModEnabled("cleaningservice")) {
			$conf->cleaningservice = new stdClass();
			$conf->cleaningservice->enabled = 0;
		}

		// Array to add new pages in new tabs
		/* BEGIN MODULEBUILDER TABS */
		$this->tabs = array();
		/* END MODULEBUILDER TABS */
		// Example:
		// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@cleaningservice:$user->hasRight('cleaningservice', 'read'):/cleaningservice/mynewtab1.php?id=__ID__');
		// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
		// $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@cleaningservice:$user->hasRight('othermodule', 'read'):/cleaningservice/mynewtab2.php?id=__ID__',
		// To remove an existing tab identified by code tabname
		// $this->tabs[] = array('data'=>'objecttype:-tabname:NU:conditiontoremove');
		//
		// Where objecttype can be
		// 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
		// 'contact'          to add a tab in contact view
		// 'contract'         to add a tab in contract view
		// 'group'            to add a tab in group view
		// 'intervention'     to add a tab in intervention view
		// 'invoice'          to add a tab in customer invoice view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'member'           to add a tab in foundation member view
		// 'opensurveypoll'	  to add a tab in opensurvey poll view
		// 'order'            to add a tab in sale order view
		// 'order_supplier'   to add a tab in supplier order view
		// 'payment'		  to add a tab in payment view
		// 'payment_supplier' to add a tab in supplier payment view
		// 'product'          to add a tab in product view
		// 'propal'           to add a tab in propal view
		// 'project'          to add a tab in project view
		// 'stock'            to add a tab in stock view
		// 'thirdparty'       to add a tab in third party view
		// 'user'             to add a tab in user view


		// Dictionaries
		/* Example:
		 $this->dictionaries=array(
		 'langs'=>'cleaningservice@cleaningservice',
		 // List of tables we want to see into dictonnary editor
		 'tabname'=>array("table1", "table2", "table3"),
		 // Label of tables
		 'tablib'=>array("Table1", "Table2", "Table3"),
		 // Request to select fields
		 'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table1 as f', 'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table2 as f', 'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table3 as f'),
		 // Sort order
		 'tabsqlsort'=>array("label ASC", "label ASC", "label ASC"),
		 // List of fields (result of select to show dictionary)
		 'tabfield'=>array("code,label", "code,label", "code,label"),
		 // List of fields (list of fields to edit a record)
		 'tabfieldvalue'=>array("code,label", "code,label", "code,label"),
		 // List of fields (list of fields for insert)
		 'tabfieldinsert'=>array("code,label", "code,label", "code,label"),
		 // Name of columns with primary key (try to always name it 'rowid')
		 'tabrowid'=>array("rowid", "rowid", "rowid"),
		 // Condition to show each dictionary
		 'tabcond'=>array(isModEnabled('cleaningservice'), isModEnabled('cleaningservice'), isModEnabled('cleaningservice')),
		 // Tooltip for every fields of dictionaries: DO NOT PUT AN EMPTY ARRAY
		 'tabhelp'=>array(array('code'=>$langs->trans('CodeTooltipHelp'), 'field2' => 'field2tooltip'), array('code'=>$langs->trans('CodeTooltipHelp'), 'field2' => 'field2tooltip'), ...),
		 );
		 */
		/* BEGIN MODULEBUILDER DICTIONARIES */
		// $this->dictionaries = array(
		// 	'langs' => 'cleaningservice@cleaningservice',

		// );
		/* END MODULEBUILDER DICTIONARIES */

		// Boxes/Widgets
		// Add here list of php file(s) stored in cleaningservice/core/boxes that contains a class to show a widget.
		/* BEGIN MODULEBUILDER WIDGETS */
		$this->boxes = array(
			//  0 => array(
			//      'file' => 'cleaningservicewidget1.php@cleaningservice',
			//      'note' => 'Widget provided by Cleaningservice',
			//      'enabledbydefaulton' => 'Home',
			//  ),
			//  ...
		);
		/* END MODULEBUILDER WIDGETS */

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
		/* BEGIN MODULEBUILDER CRON */
		$this->cronjobs = array(
			//  0 => array(
			//      'label' => 'MyJob label',
			//      'jobtype' => 'method',
			//      'class' => '/cleaningservice/class/cleaningservicetask.class.php',
			//      'objectname' => 'CleaningServiceTask',
			//      'method' => 'doScheduledJob',
			//      'parameters' => '',
			//      'comment' => 'Comment',
			//      'frequency' => 2,
			//      'unitfrequency' => 3600,
			//      'status' => 0,
			//      'test' => 'isModEnabled("cleaningservice")',
			//      'priority' => 50,
			//  ),
		);
		/* END MODULEBUILDER CRON */
		// Example: $this->cronjobs=array(
		//    0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>'isModEnabled("cleaningservice")', 'priority'=>50),
		//    1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>'isModEnabled("cleaningservice")', 'priority'=>50)
		// );

		// Permissions provided by this module
		$this->rights = array();
		$r = 0;
		// Add here entries to declare new permissions
		/* BEGIN MODULEBUILDER PERMISSIONS */

		// Task management permissions
		$this->rights[$r][0] = $this->numero . '01';
		$this->rights[$r][1] = 'Create/modify cleaning tasks';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'task';
		$this->rights[$r][5] = 'create';
		$r++;

		$this->rights[$r][0] = $this->numero . '02';
		$this->rights[$r][1] = 'View cleaning tasks';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'task';
		$this->rights[$r][5] = 'read';
		$r++;

		$this->rights[$r][0] = $this->numero . '03';
		$this->rights[$r][1] = 'Delete cleaning tasks';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'task';
		$this->rights[$r][5] = 'delete';
		$r++;

		$this->rights[$r][0] = $this->numero . '04';
		$this->rights[$r][1] = 'Validate cleaning tasks';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'task';
		$this->rights[$r][5] = 'write';
		$r++;



		// Main menu entries to add
		$this->menu = array();
		$r = 0;
		// Add here entries to declare new menus
		/* BEGIN MODULEBUILDER TOPMENU */
		// Top menu
		$this->menu[$r++] = array(
			'fk_menu' => '',
			'type' => 'top',
			'titre' => 'CleaningService',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu' => 'cleaningservice',
			'leftmenu' => '',
			'url' => '/custom/cleaningservice/cleaningserviceindex.php',
			'langs' => 'cleaningservice@cleaningservice',
			'position' => 100,
			'enabled' => '$conf->cleaningservice->enabled',
			'perms' => '1',
			'target' => '',
			'user' => 2
		);

		// Left menu - Tasks
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=cleaningservice',
			'type' => 'left',
			'titre' => 'Tasks',
			'mainmenu' => 'cleaningservice',
			'leftmenu' => 'tasks',
			'url' => '/custom/cleaningservice/task_list.php',
			'langs' => 'cleaningservice@cleaningservice',
			'position' => 101,
			'enabled' => '$conf->cleaningservice->enabled',
			'perms' => '$user->rights->cleaningservice->task->read',
			'target' => '',
			'user' => 2
		);

		// Left menu - New Task
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=cleaningservice,fk_leftmenu=tasks',
			'type' => 'left',
			'titre' => 'NewTask',
			'mainmenu' => 'cleaningservice',
			'leftmenu' => 'task_new',
			'url' => '/custom/cleaningservice/task_card.php?action=create',
			'langs' => 'cleaningservice@cleaningservice',
			'position' => 102,
			'enabled' => '$conf->cleaningservice->enabled',
			'perms' => '$user->rights->cleaningservice->task->create',
			'target' => '',
			'user' => 2
		);

		// Left menu - Schedule
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=cleaningservice',
			'type' => 'left',
			'titre' => 'Schedule',
			'mainmenu' => 'cleaningservice',
			'leftmenu' => 'schedule',
			'url' => '/custom/cleaningservice/schedule.php',
			'langs' => 'cleaningservice@cleaningservice',
			'position' => 103,
			'enabled' => '$conf->cleaningservice->enabled',
			'perms' => '$user->rights->cleaningservice->task->read',
			'target' => '',
			'user' => 2
		);

		// In setupMenu() method
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=cleaningservice',
			'type' => 'left',
			'titre' => 'TaskValidation',
			'url' => '/custom/cleaningservice/task_validation.php',
			'langs' => 'cleaningservice@cleaningservice',
			'position' => 104,
			'enabled' => '$user->rights->cleaningservice->task->write',
			'perms' => '$user->rights->cleaningservice->task->write',
			'target' => '',
			'user' => 2
		);

		// $this->menu[$r++] = array(
		// 	'fk_menu' => 'fk_mainmenu=cleaningservice',
		// 	'type' => 'left',
		// 	'titre' => 'HoursSummary',
		// 	'url' => '/cleaningservice/hours_report.php',
		// 	'langs' => 'cleaningservice@cleaningservice',
		// 	'position' => 105,
		// 	'enabled' => '$user->rights->cleaningservice->task->read',
		// 	'perms' => '$user->rights->cleaningservice->task->read',
		// 	'target' => '',
		// 	'user' => 2
		// );
		/* END MODULEBUILDER TOPMENU */

		/* BEGIN MODULEBUILDER LEFTMENU CLEANINGSERVICETASK */
		/* END MODULEBUILDER LEFTMENU CLEANINGSERVICETASK */
		/* BEGIN MODULEBUILDER LEFTMENU MYOBJECT */
		/*
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=cleaningservice',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',                          // This is a Left menu entry
			'titre'=>'CleaningServiceTask',
			'prefix' => img_picto('', $this->picto, 'class="pictofixedwidth valignmiddle paddingright"'),
			'mainmenu'=>'cleaningservice',
			'leftmenu'=>'cleaningservicetask',
			'url'=>'/cleaningservice/cleaningserviceindex.php',
			'langs'=>'cleaningservice@cleaningservice',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'isModEnabled("cleaningservice")', // Define condition to show or hide menu entry. Use 'isModEnabled("cleaningservice")' if entry must be visible if module is enabled.
			'perms'=>'$user->hasRight("cleaningservice", "cleaningservicetask", "read")',
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
			'object'=>'CleaningServiceTask'
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=cleaningservice,fk_leftmenu=cleaningservicetask',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'New_CleaningServiceTask',
			'mainmenu'=>'cleaningservice',
			'leftmenu'=>'cleaningservice_cleaningservicetask_new',
			'url'=>'/cleaningservice/cleaningservicetask_card.php?action=create',
			'langs'=>'cleaningservice@cleaningservice',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'isModEnabled("cleaningservice")', // Define condition to show or hide menu entry. Use 'isModEnabled("cleaningservice")' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->hasRight("cleaningservice", "cleaningservicetask", "write")'
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
			'object'=>'CleaningServiceTask'
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=cleaningservice,fk_leftmenu=cleaningservicetask',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'List_CleaningServiceTask',
			'mainmenu'=>'cleaningservice',
			'leftmenu'=>'cleaningservice_cleaningservicetask_list',
			'url'=>'/cleaningservice/cleaningservicetask_list.php',
			'langs'=>'cleaningservice@cleaningservice',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'isModEnabled("cleaningservice")', // Define condition to show or hide menu entry. Use 'isModEnabled("cleaningservice")' if entry must be visible if module is enabled.
			'perms'=>'$user->hasRight("cleaningservice", "cleaningservicetask", "read")'
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
			'object'=>'CleaningServiceTask'
		);
		*/
		/* END MODULEBUILDER LEFTMENU MYOBJECT */


		// Exports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER EXPORT MYOBJECT */
		/*
		$langs->load("cleaningservice@cleaningservice");
		$this->export_code[$r] = $this->rights_class.'_'.$r;
		$this->export_label[$r] = 'CleaningServiceTaskLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r] = $this->picto;
		// Define $this->export_fields_array, $this->export_TypeFields_array and $this->export_entities_array
		$keyforclass = 'CleaningServiceTask'; $keyforclassfile='/cleaningservice/class/cleaningservicetask.class.php'; $keyforelement='cleaningservicetask@cleaningservice';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		//$this->export_fields_array[$r]['t.fieldtoadd']='FieldToAdd'; $this->export_TypeFields_array[$r]['t.fieldtoadd']='Text';
		//unset($this->export_fields_array[$r]['t.fieldtoremove']);
		//$keyforclass = 'CleaningServiceTaskLine'; $keyforclassfile='/cleaningservice/class/cleaningservicetask.class.php'; $keyforelement='cleaningservicetaskline@cleaningservice'; $keyforalias='tl';
		//include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		$keyforselect='cleaningservicetask'; $keyforaliasextra='extra'; $keyforelement='cleaningservicetask@cleaningservice';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$keyforselect='cleaningservicetaskline'; $keyforaliasextra='extraline'; $keyforelement='cleaningservicetaskline@cleaningservice';
		//include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$this->export_dependencies_array[$r] = array('cleaningservicetaskline'=>array('tl.rowid','tl.ref')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		//$this->export_special_array[$r] = array('t.field'=>'...');
		//$this->export_examplevalues_array[$r] = array('t.field'=>'Example');
		//$this->export_help_array[$r] = array('t.field'=>'FieldDescHelp');
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'cleaningservice_cleaningservicetask as t';
		//$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'cleaningservice_cleaningservicetask_line as tl ON tl.fk_cleaningservicetask = t.rowid';
		$this->export_sql_end[$r] .=' WHERE 1 = 1';
		$this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('cleaningservicetask').')';
		$r++; */
		/* END MODULEBUILDER EXPORT MYOBJECT */

		// Imports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER IMPORT MYOBJECT */
		/*
		$langs->load("cleaningservice@cleaningservice");
		$this->import_code[$r] = $this->rights_class.'_'.$r;
		$this->import_label[$r] = 'CleaningServiceTaskLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->import_icon[$r] = $this->picto;
		$this->import_tables_array[$r] = array('t' => MAIN_DB_PREFIX.'cleaningservice_cleaningservicetask', 'extra' => MAIN_DB_PREFIX.'cleaningservice_cleaningservicetask_extrafields');
		$this->import_tables_creator_array[$r] = array('t' => 'fk_user_author'); // Fields to store import user id
		$import_sample = array();
		$keyforclass = 'CleaningServiceTask'; $keyforclassfile='/cleaningservice/class/cleaningservicetask.class.php'; $keyforelement='cleaningservicetask@cleaningservice';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinimport.inc.php';
		$import_extrafield_sample = array();
		$keyforselect='cleaningservicetask'; $keyforaliasextra='extra'; $keyforelement='cleaningservicetask@cleaningservice';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinimport.inc.php';
		$this->import_fieldshidden_array[$r] = array('extra.fk_object' => 'lastrowid-'.MAIN_DB_PREFIX.'cleaningservice_cleaningservicetask');
		$this->import_regex_array[$r] = array();
		$this->import_examplevalues_array[$r] = array_merge($import_sample, $import_extrafield_sample);
		$this->import_updatekeys_array[$r] = array('t.ref' => 'Ref');
		$this->import_convertvalue_array[$r] = array(
			't.ref' => array(
				'rule'=>'getrefifauto',
				'class'=>(!getDolGlobalString('CLEANINGSERVICE_MYOBJECT_ADDON') ? 'mod_cleaningservicetask_standard' : getDolGlobalString('CLEANINGSERVICE_MYOBJECT_ADDON')),
				'path'=>"/core/modules/cleaningservice/".(!getDolGlobalString('CLEANINGSERVICE_MYOBJECT_ADDON') ? 'mod_cleaningservicetask_standard' : getDolGlobalString('CLEANINGSERVICE_MYOBJECT_ADDON')).'.php',
				'classobject'=>'CleaningServiceTask',
				'pathobject'=>'/cleaningservice/class/cleaningservicetask.class.php',
			),
			't.fk_soc' => array('rule' => 'fetchidfromref', 'file' => '/societe/class/societe.class.php', 'class' => 'Societe', 'method' => 'fetch', 'element' => 'ThirdParty'),
			't.fk_user_valid' => array('rule' => 'fetchidfromref', 'file' => '/user/class/user.class.php', 'class' => 'User', 'method' => 'fetch', 'element' => 'user'),
			't.fk_mode_reglement' => array('rule' => 'fetchidfromcodeorlabel', 'file' => '/compta/paiement/class/cpaiement.class.php', 'class' => 'Cpaiement', 'method' => 'fetch', 'element' => 'cpayment'),
		);
		$this->import_run_sql_after_array[$r] = array();
		$r++; */
		/* END MODULEBUILDER IMPORT MYOBJECT */
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string  $options    Options when enabling module ('', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs;

		//$result = $this->_load_tables('/install/mysql/', 'cleaningservice');
		$result = $this->_load_tables('/cleaningservice/sql/');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		//Create extrafields during init
		include_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
		$extrafields = new ExtraFields($this->db);
		// Add frequency field
		//Add frequency field with proper parameters
		$result = $extrafields->addExtraField(
			'frequency',              // Field name
			'Frequency',             // Label
			'select',                // Type ('select' for dropdown)
			100,                     // Position
			'',                      // Size
			'cleaningservice_task',  // Element type
			0,                       // Unique
			0,                       // Required
			'',                      // Default value
			array('options' => array(
				'one-off' => 'OneOff',
				'daily'   => 'Daily',
				'weekly'  => 'Weekly',
				'bimonthly'  => 'Bimonthly',
				'monthly' => 'Monthly'
			)),                      // Options for select
			1,                       // Always editable
			'',                      // Perms
			1,                       // List visible (Show in list view)
			'FrequencyHelp',        // Help text
			'',                      // Computed
			$conf->entity,          // Entity
			'cleaningservice@cleaningservice'  // Langfile
		);

		$extrafields_array = array(
			// Integer fields
			array('matelas_double', 'Matelas double', 'int', 10, 10),
			array('matelas_simple', 'Matelas simple', 'int', 20, 10),
			array('duvet_double', 'Duvet double', 'int', 30, 10),
			array('duvet_simple', 'Duvet simple', 'int', 40, 10),
			array('oreiller', 'Oreiller (unité)', 'int', 50, 10),
			array('canapes_lit', 'Canapés lit', 'int', 60, 10),
			array('salles_bain', 'Salles de bain', 'int', 70, 10),
			array('salles_douche', 'Salles de douche', 'int', 80, 10),
			// Varchar fields
			array('code_boite_cle', 'Code boite clé', 'varchar', 90, 255),
			array('code_batiment', 'Code bâtiment / portail', 'varchar', 100, 255),
			// Text field
			array('informations_plus', 'Informations en +', 'text', 110, 0)
		);

		// Create each extrafield
		foreach ($extrafields_array as $field) {
			$result = $extrafields->addExtraField(
				$field[0],      // attrname
				$field[1],      // label
				$field[2],      // type
				$field[3],      // pos
				$field[4],      // size
				'cleaningservice_task',  // elementtype
				0,             // unique
				0,             // required
				'',            // default_value
				'',            // param
				1,             // alwayseditable
				'',            // perms
				1              // enabled
			);
			if ($result < 0) {
				$this->error = "Failed to add extrafield " . $field[0];
				return -1;
			}
		}

		// Permissions
// 		$this->remove($options);

		$sql = array();

		// Document templates
		$moduledir = dol_sanitizeFileName('cleaningservice');
		$myTmpObjects = array();
		$myTmpObjects['CleaningServiceTask'] = array('includerefgeneration' => 0, 'includedocgeneration' => 0);

		foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
			if ($myTmpObjectKey == 'CleaningServiceTask') {
				continue;
			}
			if ($myTmpObjectArray['includerefgeneration']) {
				$src = DOL_DOCUMENT_ROOT . '/install/doctemplates/' . $moduledir . '/template_cleaningservicetasks.odt';
				$dirodt = DOL_DATA_ROOT . '/doctemplates/' . $moduledir;
				$dest = $dirodt . '/template_cleaningservicetasks.odt';

				if (file_exists($src) && !file_exists($dest)) {
					require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
					dol_mkdir($dirodt);
					$result = dol_copy($src, $dest, 0, 0);
					if ($result < 0) {
						$langs->load("errors");
						$this->error = $langs->trans('ErrorFailToCopyFile', $src, $dest);
						return 0;
					}
				}

				$sql = array_merge($sql, array(
					"DELETE FROM " . MAIN_DB_PREFIX . "document_model WHERE nom = 'standard_" . strtolower($myTmpObjectKey) . "' AND type = '" . $this->db->escape(strtolower($myTmpObjectKey)) . "' AND entity = " . ((int) $conf->entity),
					"INSERT INTO " . MAIN_DB_PREFIX . "document_model (nom, type, entity) VALUES('standard_" . strtolower($myTmpObjectKey) . "', '" . $this->db->escape(strtolower($myTmpObjectKey)) . "', " . ((int) $conf->entity) . ")",
					"DELETE FROM " . MAIN_DB_PREFIX . "document_model WHERE nom = 'generic_" . strtolower($myTmpObjectKey) . "_odt' AND type = '" . $this->db->escape(strtolower($myTmpObjectKey)) . "' AND entity = " . ((int) $conf->entity),
					"INSERT INTO " . MAIN_DB_PREFIX . "document_model (nom, type, entity) VALUES('generic_" . strtolower($myTmpObjectKey) . "_odt', '" . $this->db->escape(strtolower($myTmpObjectKey)) . "', " . ((int) $conf->entity) . ")"
				));
			}
		}

		// Notifications setup
		$this->const = array(
			0 => array(
				'NOTIFICATION_CLEANINGSERVICE_TASK_ASSIGNED',
				'chaine',
				'1',
				'Enable notifications for task assignments',
				0,
				'current',
				1
			),
			array(
				'CLEANINGSERVICE_PRICE_PER_HOUR',
				'chaine',
				'25',
				'Default price per hour for cleaning services',
				0,
				'current',
				1
			),
			array(
				'CLEANINGSERVICE_AUTO_INVOICE',
				'chaine',
				'0',
				'Automatically create invoice when task is completed',
				0,
				'current',
				1
			),
			array(
				'CLEANINGSERVICE_DEFAULT_VAT_RATE',
				'chaine',
				'20.0',
				'Default VAT rate for cleaning services',
				0,
				'current',
				1
			),
			array(
				'CLEANINGSERVICE_DEFAULT_PAYMENT_TERM',
				'chaine',
				'1',
				'Default payment term for cleaning service invoices',
				0,
				'current',
				1
			),
			array(
				'CLEANINGSERVICE_DEFAULT_PAYMENT_MODE',
				'chaine',
				'1',
				'Default payment mode for cleaning service invoices',
				0,
				'current',
				1
			),
			array(
				'CLEANINGSERVICE_INVOICE_PREFIX',
				'chaine',
				'CS-',
				'Prefix for cleaning service invoices',
				0,
				'current',
				1
			),
			array(
				'CLEANINGSERVICE_SWISSQR_IBAN',
				'chaine',
				'CH...',  // Your IBAN
				'IBAN for SwissQR invoices',
				0,
				'current',
				1
			),
			array(
				'CLEANINGSERVICE_SWISSQR_ADDRESS_TYPE',
				'chaine',
				'S',  // S for structured, K for combined
				'Address type for SwissQR invoices',
				0,
				'current',
				1
			)

		);

		return $this->_init($sql, $options);
	}

	/**
	 *  Function called when module is disabled.
	 *  Remove from database constants, boxes and permissions from Dolibarr database.
	 *  Data directories are not deleted
	 *
	 *  @param      string	$options    Options when enabling module ('', 'noboxes')
	 *  @return     int                 1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();
		// Remove extrafields
		// include_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
		// $extrafields = new ExtraFields($this->db);

		// $extrafields_to_delete = array(
		// 	'matelas_double',
		// 	'matelas_simple',
		// 	'duvet_double',
		// 	'duvet_simple',
		// 	'oreiller',
		// 	'canapes_lit',
		// 	'salles_bain',
		// 	'salles_douche',
		// 	'code_boite_cle',
		// 	'code_batiment',
		// 	'informations_plus'
		// );

		// foreach ($extrafields_to_delete as $field) {
		// 	$result = $extrafields->delete($field, 'cleaningservice_task');
		// 	if ($result < 0) {
		// 		$this->error = "Failed to remove extrafield " . $field;
		// 		return -1;
		// 	}
		// }
		return $this->_remove($sql, $options);
	}
}
