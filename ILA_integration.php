<?php
/**********************************************************************************
* ILA_integration.php                                                             *
***********************************************************************************/

if (!defined('SMF'))
	die('Hacking attempt...');

function ila_bbc_add_code($codes)
{
	global $txt;
	
	// Add in our new code on to the end of this array
	$codes = array_merge($codes, array(
		array(
			'tag' => 'attach',
			'type' => 'closed',
			'content' => '',
		),
		array(
			'tag' => 'attachment',
			'type' => 'closed',
			'content' => '',
		),
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

function ila_integrate_admin_areas(&$admin_areas)
{
	global $txt;
	$admin_areas['config']['areas']['modsettings']['subsections']['ila'] = array($txt['mods_cat_modifications_ila']);
}

function ila_integrate_modify_modifications(&$sub_actions)
{
	$sub_actions['ila'] = 'ModifyilaSettings';
}

function ModifyilaSettings($return_config = false)
{
	global $txt, $scripturl, $context, $smcFunc;

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
		writeLog();

		redirectexit('action=admin;area=modsettings;sa=ila');
	}

	$context['post_url'] = $scripturl . '?action=admin;area=modsettings;save;sa=ila';
	$context['settings_title'] = $txt['ila_title'];

	prepareDBSettingContext($config_vars);
}

?>