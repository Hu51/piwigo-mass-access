<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

// Check if user is admin
if (!$user['is_admin'])
{
    die('Access denied');
}

// Get all albums
$query = "
    SELECT id, name
    FROM " . CATEGORIES_TABLE . "
    ORDER BY name ASC
";
$albums = pwg_query($query);
$template->assign('albums', $albums);

// Get all users
$query = "
    SELECT id, username
    FROM " . USERS_TABLE . "
    ORDER BY username ASC
";
$users = pwg_query($query);
$template->assign('users', $users);

// Process form submission
if (isset($_POST['submit']))
{
    $albums = isset($_POST['albums']) ? $_POST['albums'] : array();
    $users = isset($_POST['users']) ? $_POST['users'] : array();
    $access_level = isset($_POST['access_level']) ? $_POST['access_level'] : 'private';

    if (!empty($albums) && !empty($users))
    {
        // Process each album
        foreach ($albums as $album_id)
        {
            // Process each user
            foreach ($users as $user_id)
            {
                // Update permissions based on access level
                switch ($access_level)
                {
                    case 'private':
                        // Remove all permissions
                        $query = "
                            DELETE FROM " . USER_ACCESS_TABLE . "
                            WHERE user_id = " . $user_id . "
                            AND cat_id = " . $album_id;
                        pwg_query($query);
                        break;

                    case 'public':
                        // Grant full access
                        $query = "
                            INSERT INTO " . USER_ACCESS_TABLE . "
                            (user_id, cat_id)
                            VALUES
                            (" . $user_id . ", " . $album_id . ")
                            ON DUPLICATE KEY UPDATE user_id = user_id";
                        pwg_query($query);
                        break;

                    case 'custom':
                        // Handle custom permissions (to be implemented)
                        break;
                }
            }
        }

        $page['infos'][] = l10n('Permissions updated successfully');
    }
    else
    {
        $page['errors'][] = l10n('Please select at least one album and one user');
    }
}

// Set template
$template->set_filename('mass_access_admin', realpath(MASS_ACCESS_PATH . 'template/admin.tpl'));
$template->assign('MASS_ACCESS_CONTENT', $template->parse('mass_access_admin', true)); 