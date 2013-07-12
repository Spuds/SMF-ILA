<?php
/**********************************************************************************
* add_remove_hooks.php                                                            *
***********************************************************************************
***********************************************************************************
* This program is distributed in the hope that it is and will be useful, but      *
* WITHOUT ANY WARRANTIES; without even any implied warranty of MERCHANTABILITY    *
* or FITNESS FOR A PARTICULAR PURPOSE.                                            *
*                                                                                 *
* This file is a simplified database installer. It does what it is suppoed to.    *
**********************************************************************************/

// If we have found SSI.php and we are outside of SMF, then we are running standalone.
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
elseif (!defined('SMF')) // If we are outside SMF and can't find SSI.php, then throw an error
	die('<b>Error:</b> Cannot install - please verify you put this file in the same place as SMF\'s SSI.php.');

if (SMF == 'SSI')
	db_extend('packages');
	
// Define the hooks
$hook_functions = array(
	'integrate_bbc_codes' => 'ila_bbc_add_code',
	'integrate_modify_modifications' => 'ila_integrate_modify_modifications',
	'integrate_admin_areas' => 'ila_integrate_admin_areas',
	'integrate_pre_include' => '$sourcedir/ILA_integration.php'
);

// Adding or removing them?
if (!empty($context['uninstalling']))
{
	// We only added this if they enabled the mod, need to make sure we clear it now
	$hook_functions['integrate_pre_include'] = '$sourcedir/ILA-Subs.php';
	$call = 'remove_integration_function';
}
else
	$call = 'add_integration_function';

// Do the deed
foreach ($hook_functions as $hook => $function)
	$call($hook, $function);

if (SMF == 'SSI')
   echo 'Congratulations! You have successfully installed The Hooks for ILA!';

?>