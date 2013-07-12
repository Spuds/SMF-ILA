<?php

/**
 *
 * @package "Inline Attachments (ILA)" Mod for Simple Machines Forum (SMF) V2.0
 * @author Spuds
 * @copyright (c) 2011 Spuds
 * @license Mozilla Public License version 1.1 http://www.mozilla.org/MPL/1.1/.
 *
 * @version 1.21
 *
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * ila_bbc_add_code()
 *
 * Adds in new BBC code tags for use with inline images
 *
 * @param mixed $codes
 * @return
 */
function ila_bbc_add_code($codes)
{
	// Add in our new codes, if found on to the end of this array
	// here mostly used to null them out should they not be rendered
	$codes = array_merge($codes, array(
		array(
			'tag' => 'attachimg',
			'type' => 'closed',
			'content' => '',
		),
		array(
			'tag' => 'attachurl',
			'type' => 'closed',
			'content' => '',
		),
		array(
			'tag' => 'attachmini',
			'type' => 'closed',
			'content' => '',
		),
		array(
			'tag' => 'attachthumb',
			'type' => 'closed',
			'content' => '',
		))
	);

	return;
}

/**
 * ila_integrate_admin_areas()
 *
 * add a line under modifcation config
 * @param mixed $admin_areas
 * @return
 */
function ila_integrate_admin_areas(&$admin_areas)
{
	global $txt;
	$admin_areas['config']['areas']['modsettings']['subsections']['ila'] = array($txt['mods_cat_modifications_ila']);
}

/**
 * ila_integrate_modify_modifications()
 *
 * @param mixed $sub_actions
 * @return
 */
function ila_integrate_modify_modifications(&$sub_actions)
{
	$sub_actions['ila'] = 'ModifyilaSettings';
}

/**
 * ModifyilaSettings()
 *
 * @param mixed $return_config
 * @return
 */
function ModifyilaSettings($return_config = false)
{
	global $txt, $scripturl, $context, $smcFunc, $sourcedir;

	$context[$context['admin_menu_name']]['tab_data']['tabs']['ila']['description'] = $txt['ila_desc'];
	$config_vars = array(
		array('check', 'ila_enabled'),
		array('check', 'ila_alwaysfullsize'),
		array('check', 'ila_basicmenu'),
	);

	if ($return_config)
		return $config_vars;

	if (isset($_GET['save']))
	{
		checkSession();
		saveDBSettings($config_vars);

		// enabling the mod then lets have the main file available, otherwise lets not ;)
		if (isset($_POST['ila_enabled']))
			add_integration_function('integrate_pre_include', '$sourcedir/ILA-Subs.php');
		else
			remove_integration_function('integrate_pre_include', '$sourcedir/ILA-Subs.php');

		writeLog();
		redirectexit('action=admin;area=modsettings;sa=ila');
	}

	$context['post_url'] = $scripturl . '?action=admin;area=modsettings;save;sa=ila';
	$context['settings_title'] = $txt['mods_cat_modifications_ila'];

	prepareDBSettingContext($config_vars);
}

?>