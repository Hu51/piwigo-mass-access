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
if (isset($_POST['submit_global'])) {
    $set_all_private = isset($_POST['set_all_private']) ? $_POST['set_all_private'] : false;
    $assign_admin_all = isset($_POST['assign_admin_all']) ? $_POST['assign_admin_all'] : false;
    $clear_existing_rules = isset($_POST['clear_existing_rules']) ? $_POST['clear_existing_rules'] : false;

    clearCache();

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


function collectAlbumIds($album_id, $recursive = false, $album_ids = array())
{
    $album_ids[] = $album_id;
    if ($recursive) {
        $query = "
            SELECT id FROM " . CATEGORIES_TABLE . " WHERE id_uppercat = " . $album_id;
        $result = pwg_query($query);
        while ($row = pwg_db_fetch_assoc($result)) {
            $album_ids[] = $row['id'];
            $album_ids = collectAlbumIds($row['id'], true, $album_ids);
        }
    }
    return array_unique($album_ids);
}

;
function generate_breadcrumbs(&$items, $parent_id = null, $indexed_items = [])
{
    // Index items by ID for quick parent lookup
    if (empty($indexed_items)) {
        foreach ($items as $item) {
            $indexed_items[$item['id']] = $item;
        }
    }

    $items_to_process = array_filter($items, function ($item) use ($parent_id) {
        return $item['id_uppercat'] == $parent_id;
    });

    // Build breadcrumbs for each item
    foreach ($items_to_process as $index => &$item) {
        $breadcrumb = [];
        $current_id = $item['id'];

        while (isset($indexed_items[$current_id])) {
            $current = $indexed_items[$current_id];
            array_unshift($breadcrumb, $current['name']);
            $current_id = $current['id_uppercat'];
        }

        $item['long_name'] = implode(' &raquo; ', $breadcrumb);
        $items[$index] = $item;
        generate_breadcrumbs($items, $item['id'], $indexed_items);
    }
}


function setUserAlbumAccess($album_ids, $user_id, $access_type)
{
    switch ($access_type) {
        case 'add':
            foreach ($album_ids as $album_id) {
                $query = "
                    INSERT IGNORE INTO " . USER_ACCESS_TABLE . "
                    (user_id, cat_id)
                    VALUES
                    (" . $user_id . ", " . $album_id . ")
                    ON DUPLICATE KEY UPDATE user_id = user_id";
                pwg_query($query);
            }
            break;

        case 'remove':
            foreach ($album_ids as $album_id) {
                $query = "
                    DELETE FROM " . USER_ACCESS_TABLE . "
                    WHERE user_id = " . $user_id . " AND cat_id = " . $album_id;
                pwg_query($query);
            }
            break;

        case 'nochange':
            // Do nothing
            break;
    }
}


function setGroupAlbumAccess($album_ids, $group_id, $access_type)
{
    switch ($access_type) {
        case 'add':
            foreach ($album_ids as $album_id) {
                $query = "
                    INSERT IGNORE INTO " . GROUP_ACCESS_TABLE . "
                    (group_id, cat_id)
                    VALUES
                    (" . $group_id . ", " . $album_id . ")
                    ON DUPLICATE KEY UPDATE group_id = group_id";
                pwg_query($query);
            }
            break;

        case 'remove':
            foreach ($album_ids as $album_id) {
                $query = "
                    DELETE FROM " . GROUP_ACCESS_TABLE . "
                    WHERE group_id = " . $group_id . " AND cat_id = " . $album_id;
                pwg_query($query);
            }
            break;

        case 'nochange':
            // Do nothing
            break;
    }
}

function clearCache()
{
    $query = "DELETE FROM " . USER_CACHE_TABLE;
    pwg_query($query);
    $query = "DELETE FROM " . USER_CACHE_CATEGORIES_TABLE;
    pwg_query($query);
}


if (isset($_POST['submit_assign'])) {
    $albums = isset($_POST['album_select']) ? $_POST['album_select'] : array();
    $users = isset($_POST['user_group_select']) ? $_POST['user_group_select'] : array();
    $access = isset($_POST['access']) ? $_POST['access'] : null;
    $recursive = isset($_POST['recursive']);

    if (!empty($albums) && !empty($users) && !empty($access)) {
        clearCache();
        // Process each album
        foreach ($albums as $album_id) {
            // Process each user
            foreach ($users as $user_id) {
                $userType = explode('_', $user_id)[0];
                $id = explode('_', $user_id)[1];

                $album_ids = collectAlbumIds($album_id, $recursive);
                if ($userType == 'user') {
                    setUserAlbumAccess($album_ids, $id, $access);
                }
                if ($userType == 'group') {
                    setGroupAlbumAccess($album_ids, $id, $access);
                }
            }
        }
        $page['infos'][] = l10n('Access permissions assigned successfully');
    }
}


if (isset($_POST['submit_onebyone'])) {
    $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : array();
    if (!empty($permissions)) {
        clearCache();
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
                    WHERE cat_id = " . $album_id;
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
    SELECT c.id, c.name, p.name as parent_name, c.visible, c.status, c.id_uppercat
    FROM " . CATEGORIES_TABLE . " c
    LEFT JOIN " . CATEGORIES_TABLE . " p ON c.id_uppercat = p.id
    ORDER BY c.id DESC LIMIT 5
";
$last5_albums = toArray(pwg_query($query));
generate_breadcrumbs($last5_albums, null, []);
$template->assign('last5_albums', $last5_albums);

// Get all albums
$query = "
    SELECT c.id, c.name, p.name as parent_name, c.visible, c.status, c.id_uppercat
    FROM " . CATEGORIES_TABLE . " c
    LEFT JOIN " . CATEGORIES_TABLE . " p ON c.id_uppercat = p.id
    ORDER BY c.name ASC
";
$albums = toArray(pwg_query($query));
generate_breadcrumbs($albums, null, []);
usort($albums, function ($a, $b) {
    return strcmp($a['long_name'], $b['long_name']);
});
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