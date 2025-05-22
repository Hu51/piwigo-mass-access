<?php
/*
Plugin Name: Mass access fixer 
Version: beta
Description: Manage access permissions for multiple albums and users at once
Author: Gergely Szabo
*/

defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

// Plugin constants
define('MASS_ACCESS_ID', basename(dirname(__FILE__)));
define('MASS_ACCESS_PATH', PHPWG_PLUGINS_PATH . MASS_ACCESS_ID . '/');
define('MASS_ACCESS_ADMIN', get_root_url() . 'admin.php?page=plugin-' . MASS_ACCESS_ID);

// Add the plugin to the admin menu
add_event_handler('loc_end_section_init', 'mass_access_init');
add_event_handler('loc_end_section_init', 'mass_access_admin_menu');

function mass_access_init()
{
    global $template, $user;
    
    if (script_basename() == 'admin' && $user['is_admin'])
    {
        $template->assign(
            array(
                'MASS_ACCESS_PATH' => MASS_ACCESS_PATH,
                'MASS_ACCESS_ADMIN' => MASS_ACCESS_ADMIN,
            )
        );
        
        $template->set_filename('mass_access_admin', realpath(MASS_ACCESS_PATH . 'template/admin.tpl'));
        $template->assign('MASS_ACCESS_CONTENT', $template->parse('mass_access_admin', true));
    }
}

function mass_access_admin_menu()
{
    global $template, $user;
    
    if (script_basename() == 'admin' && $user['is_admin'])
    {
        $template->assign(
            array(
                'MASS_ACCESS_ADMIN' => MASS_ACCESS_ADMIN,
            )
        );
        
        $template->assign(
            'PLUGIN_INDEX_ACTIONS',
            array_merge(
                $template->get_template_vars('PLUGIN_INDEX_ACTIONS'),
                array(
                    array(
                        'URL' => MASS_ACCESS_ADMIN,
                        'TITLE' => 'Mass Access Management',
                        'ICON' => 'fa-users'
                    )
                )
            )
        );
    }
} 