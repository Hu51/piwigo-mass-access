<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

global $template;

function toArray($result)
{
    $array = array();
    while ($row = pwg_db_fetch_assoc($result)) {
        $array[] = $row;
    }
    return $array;
}

// Process form submission
if (isset($_POST['submit'])) {
    $set_all_private = isset($_POST['set_all_private']) ? $_POST['set_all_private'] : false;
    $assign_admin_all = isset($_POST['assign_admin_all']) ? $_POST['assign_admin_all'] : false;
    $clear_existing_rules = isset($_POST['clear_existing_rules']) ? $_POST['clear_existing_rules'] : false;


    if ($clear_existing_rules) {
        // Clear all existing rules
        pwg_query("DELETE FROM " . USER_ACCESS_TABLE);
        pwg_query("DELETE FROM " . GROUP_ACCESS_TABLE);
        $page['infos'][] = l10n('Existing rules cleared successfully');
    }

    if ($set_all_private) {
        // Set all albums to private
        pwg_query("UPDATE " . CATEGORIES_TABLE . " SET status = 'private'");
        $page['infos'][] = l10n('All albums set to private successfully');
    }

    if ($assign_admin_all) {
        // Assign admin user to all albums
        $admin_user_id = 1;
        pwg_query("INSERT IGNORE INTO " . USER_ACCESS_TABLE . " (user_id, cat_id) SELECT " . $admin_user_id . ", id FROM " . CATEGORIES_TABLE);
        $page['infos'][] = l10n('Admin user assigned to all albums successfully');
    }
}

function setUserAlbumAccess($album_id, $user_id, $access_type, $recursive = false) {
    if ($access_type == 'add') {
        $query = "
            INSERT INTO " . USER_ACCESS_TABLE . "
            (user_id, cat_id)
            VALUES
            (" . $user_id . ", " . $album_id . ")
            ON DUPLICATE KEY UPDATE user_id = user_id";
        pwg_query($query);
    } else if ($access_type == 'remove') {
        $query = "
            DELETE FROM " . USER_ACCESS_TABLE . "
            WHERE user_id = " . $user_id . " AND cat_id = " . $album_id;
        pwg_query($query);
    } else if ($access_type == 'nochange') {
        // Do nothing
    }

    if ($recursive) {
        $query = "
            SELECT id FROM " . CATEGORIES_TABLE . " WHERE id_uppercat = " . $album_id;
        $result = pwg_query($query);
        while ($row = pwg_db_fetch_assoc($result)) {
            setUserAlbumAccess($row['id'], $user_id, $access_type, true);
        }
    }
}

function setGroupAlbumAccess($album_id, $group_id, $access_type, $recursive = false) {
    if ($access_type == 'add') {
        $query = "
            INSERT INTO " . GROUP_ACCESS_TABLE . "
            (group_id, cat_id)
            VALUES
            (" . $group_id . ", " . $album_id . ")
            ON DUPLICATE KEY UPDATE group_id = group_id";
        pwg_query($query);
    } else if ($access_type == 'remove') {
        $query = "
            DELETE FROM " . GROUP_ACCESS_TABLE . "
            WHERE group_id = " . $group_id . " AND cat_id = " . $album_id;
        pwg_query($query);
    } else if ($access_type == 'nochange') {
        // Do nothing
    }

    if ($recursive) {
        $query = "
            SELECT id FROM " . CATEGORIES_TABLE . " WHERE id_uppercat = " . $album_id;
        $result = pwg_query($query);
        while ($row = pwg_db_fetch_assoc($result)) {
            setGroupAlbumAccess($row['id'], $group_id, $access_type, true);
        }
    }
}



if (isset($_POST['submit_assign'])) {
    $albums = isset($_POST['album_select']) ? $_POST['album_select'] : array();
    $users = isset($_POST['user_group_select']) ? $_POST['user_group_select'] : array();
    $access = isset($_POST['access']) ? $_POST['access'] : null;
    $recursive = isset($_POST['recursive']);

    if (!empty($albums) && !empty($users) && !empty($access)) {
        // Process each album
        foreach ($albums as $album_id) {
            // Process each user
            foreach ($users as $user_id) {
                $userType = explode('_', $user_id)[0];
                $id = explode('_', $user_id)[1];
                if ($userType == 'user') {
                    setUserAlbumAccess($album_id, $id, $access, $recursive);
                } 
                if ($userType == 'group') {
                    setGroupAlbumAccess($album_id, $id, $access, $recursive);
                }
            }
        }
        $page['infos'][] = l10n('Access permissions assigned successfully');
    }
}


if (isset($_POST['submit_onebyone'])) {
    $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : array();
    if (!empty($permissions)) {
        // Process each album
        foreach ($permissions as $album_id => $permission) {
            // Process each user
            if (isset($permission['user'])) {
                foreach ($permission['user'] as $user_id => $value) {
                    if ($value == 1) {
                        $query = "
                            INSERT INTO " . USER_ACCESS_TABLE . "
                            (user_id, cat_id)
                            VALUES
                            (" . $user_id . ", " . $album_id . ")
                            ON DUPLICATE KEY UPDATE user_id = user_id";
                        pwg_query($query);
                    } else {
                        $query = "
                            DELETE FROM " . USER_ACCESS_TABLE . "
                            WHERE user_id = " . $user_id . " AND cat_id = " . $album_id;
                        pwg_query($query);
                    }
                }
            }

            // Process each group
            if (isset($permission['group'])) {
                foreach ($permission['group'] as $group_id => $value) {
                    if ($value == 1) {
                        $query = "
                            INSERT INTO " . GROUP_ACCESS_TABLE . "
                            (group_id, cat_id)
                            VALUES
                            (" . $group_id . ", " . $album_id . ")
                            ON DUPLICATE KEY UPDATE group_id = group_id";
                        pwg_query($query);
                    } else {
                        $query = "
                            DELETE FROM " . GROUP_ACCESS_TABLE . "
                            WHERE group_id = " . $group_id . " AND cat_id = " . $album_id;
                        pwg_query($query);
                    }
                }
            }

            if (isset($permission['remove_all'])) {
                $query = "
                    DELETE FROM " . USER_ACCESS_TABLE . "
                    WHERE cat_id = " . $album_id ;
                pwg_query($query);
                $query = "
                    DELETE FROM " . GROUP_ACCESS_TABLE . "
                    WHERE cat_id = " . $album_id;
                pwg_query($query);
                $page['infos'][] = l10n('All user and group access has been removed for album ' . $album_id);
            }
        }

        $page['infos'][] = l10n('Permissions updated successfully');
    }
}

// Get all albums
$query = "
    SELECT c.id, c.name, p.name as parent_name, c.visible, c.status
    FROM " . CATEGORIES_TABLE . " c
    LEFT JOIN " . CATEGORIES_TABLE . " p ON c.id_uppercat = p.id
    ORDER BY c.id DESC LIMIT 5
";
$last5_albums = toArray(pwg_query($query));
foreach ($last5_albums as &$album) {
    $album['name'] .= ' *';
}

$template->assign('last5_albums', $last5_albums);
$last5_albums_ids = array_column($last5_albums, 'id');

// Get all albums
$query = "
    SELECT c.id, c.name, p.name as parent_name, c.visible, c.status
    FROM " . CATEGORIES_TABLE . " c
    LEFT JOIN " . CATEGORIES_TABLE . " p ON c.id_uppercat = p.id
    WHERE c.id NOT IN (" . implode(',', $last5_albums_ids) . ")
    ORDER BY c.name ASC, coalesce(p.name, '') ASC
";
$albums = array_merge($last5_albums, toArray(pwg_query($query)));
$template->assign('albums', $albums);

// Get all users_access
$query = "
    SELECT user_id, cat_id
    FROM " . USER_ACCESS_TABLE . "
    ORDER BY user_id ASC
";
$user_access = [];
foreach (toArray(pwg_query($query)) as $row) {
    $user_access[(int) $row['user_id']][] = (int) $row['cat_id'];
}
$template->assign('user_access', $user_access);


// Get all groups_access
$query = "
    SELECT group_id, cat_id
    FROM " . GROUP_ACCESS_TABLE . "
    ORDER BY group_id ASC
";
$group_access = [];
foreach (toArray(pwg_query($query)) as $row) {
    $group_access[(int) $row['group_id']][] = (int) $row['cat_id'];
}
$template->assign('group_access', $group_access);

// Get all users
$query = "
    SELECT id, username
    FROM " . USERS_TABLE . "
    WHERE username != 'guest'
    ORDER BY username ASC
";
$users = toArray(pwg_query($query));
$template->assign('users', $users);

// get all groups
$query = "
    SELECT id, name
    FROM " . GROUPS_TABLE . "
    ORDER BY name ASC
";
$groups = toArray(pwg_query($query));
$template->assign('groups', $groups);

// admin user name
$query = "
    SELECT username
    FROM " . USERS_TABLE . "
    WHERE id = 1
";
$admin_user = toArray(pwg_query($query));
$admin_user = $admin_user[0]['username'];
$template->assign('admin_user', $admin_user);

// Add our template to the global template
$template->set_filenames(
    array(
        'plugin_admin_content' => dirname(__FILE__) . '/template/admin.tpl'
    )
);

// Assign the template contents to ADMIN_CONTENT
$template->assign_var_from_handle('ADMIN_CONTENT', 'plugin_admin_content');