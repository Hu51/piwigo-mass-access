<!-- Show the title of the plugin -->
<div class="titlePage">
  <h2>Mass Access Management</h2>

  <form method="post" action="">
    <fieldset>
        <legend>Global changes</legend>
        <div class="clear-existing-rules">
          <input
            type="checkbox"
            name="clear_existing_rules"
            id="clear_existing_rules"
          />
          <label for="clear_existing_rules"
            >Clear all existing (user, group access) rules</label
          >
        </div>

      <div class="set-all-private">
        <input type="checkbox" name="set_all_private" id="set_all_private" />
        <label for="set_all_private"
          >Set all albums to <b>private</b></label
        >
      </div>

      <div class="assign-admin">
        <input type="checkbox" name="assign_admin_all" id="assign_admin_all" />
        <label for="assign_admin_all"
          ><b>Assign admin user</b> ({$admin_user}) to all albums</b></label
        >
      </div>


      <div class="submit-buttons">
        <input type="submit" name="submit" value="Apply Changes" />
      </div>
    </fieldset>

    <p>Display the 5 most recently added albums first, followed by albums sorted by their parent category and name.</p>

     <fieldset>
      <legend>Assign Access Permissions to Albums</legend>

      <div class="album-selection" style="float: left; width: 40%">
        <label for="album_select">Select Albums:</label>
        <div class="album-selection-container">
          <select
            name="album_select[]"
            id="album_select"
            multiple
            size="10"
            style="width: 90%"
          >
            {foreach from=$albums item=album}
            <option value="{$album.id}">
                {$album.name}
                {if $album.parent_name != ''}
                    ({$album.parent_name})
                {/if}
            </option>
            {/foreach}
          </select>
        </div>
      </div>

      <div class="user-group-selection" style="float: left; width: 40%">
        <label for="user_group_select">Select Users/Groups:</label>
        <div class="user-group-selection-container">
          <select
            name="user_group_select[]"
            id="user_group_select"
            multiple
            size="10"
            style="width: 90%"
          >
            <optgroup label="Users">
              {foreach from=$users item=user}
              <option value="user_{$user.id}">{$user.username}</option>
              {/foreach}
            </optgroup>
            <optgroup label="Groups">
              {foreach from=$groups item=group}
              <option value="group_{$group.id}">{$group.name}</option>
              {/foreach}
            </optgroup>
          </select>
        </div>
      </div>

      <div class="album-selection" style="float: left; width: 20%">
        <div class="access-radio">
          <div class="access-radio-label">
            <label style="font-weight: bold">With selected albums:</label>
          </div>
          <div class="access-radio-buttons">
            <input
              type="radio"
              name="access"
              value="add"
              id="access_add"
        
            />
            <label for="access_add">Grant Access</label>
            <br />
            <input
              type="radio"
              name="access"
              value="remove"
              id="access_remove"
            />
            <label for="access_remove">Remove Access</label>
          </div>
        </div>
        <br />

        <div class="parent-folder-checkbox">
          <input type="checkbox" name="recursive" id="recursive" />
          <label for="recursive">Set parent folders access</label>
        </div>
        
        <br />
        <div class="submit-buttons">
            <input type="submit" name="submit_assign" value="Apply Changes" />
        </div>
    </div>
    </fieldset> 
    

    <fieldset>
      <legend>Set Access Permissions one by one</legend>
      <table class="border">
        <thead>
          <tr>
            <th colspan="4"></th>
            <th colspan="{$users|count}">Users</th>
            <th colspan="{$groups|count}">Groups</th>
            <th></th>
          </tr>
          <tr>
            <th>Album</th>
            <th>Parent Album</th>
            <th>Vis</th>
            <th>Priv</th>
            {foreach from=$users item=user}
            <th>{$user.username} {if $user.id == 1} ** {/if}</th>
            {/foreach} {foreach from=$groups item=group}
            <th>{$group.name}</th>
            {/foreach}
            <th>Remove all</th>
          </tr>
        </thead>
        <tbody>
          {foreach from=$albums item=album}
          <tr>
            <td>{$album.name}</td>
            <td>{$album.parent_name}</td>
            <td>{if $album.visible} ✔︎ {/if}</td>
            <td>{if $album.status == 'private'} ✔︎ {/if}</td>
            {foreach from=$users item=user}
            <td class="accent">
              {if isset($user_access[$user.id]) && in_array($album.id,
              $user_access[$user.id])}
              <input
                type="radio"
                name="permissions[{$album.id}][user][{$user.id}]"
                value="1"
                checked
              />
              <span style="color: #45a049">yes</span>
              <input
                type="radio"
                name="permissions[{$album.id}][user][{$user.id}]"
                value="0"
              />
              <span>no</span>
              {else}
              <input
                type="radio"
                name="permissions[{$album.id}][user][{$user.id}]"
                value="1"
              />
              <span>yes</span>
              <input
                type="radio"
                name="permissions[{$album.id}][user][{$user.id}]"
                value="0"
                checked
              />
              <span style="color: #d32f2f">no</span>
              {/if}
            </td>
            {/foreach} {foreach from=$groups item=group}
            <td class="accent">
              {if isset($group_access[$group.id]) && in_array($album.id,
              $group_access[$group.id])}
              <input
                type="radio"
                name="permissions[{$album.id}][group][{$group.id}]"
                value="1"
                checked
              />
              <span style="color: #45a049">yes</span>
              <input
                type="radio"
                name="permissions[{$album.id}][group][{$group.id}]"
                value="0"
              />
              <span>no</span>
              {else}
              <input
                type="radio"
                name="permissions[{$album.id}][group][{$group.id}]"
                value="1"
              />
              <span>yes</span>
              <input
                type="radio"
                name="permissions[{$album.id}][group][{$group.id}]"
                value="0"
                checked
              />
              <span style="color: #d32f2f">no</span>
              {/if}
            </td>
            {/foreach}
            <td class="accent">
              <input
                type="checkbox"
                name="permissions[{$album.id}][remove_all]"
                value="1"
              />
              Remove
            </td>
          </tr>
          {/foreach}
        </tbody>
      </table>
      <br />
      <p>** admin user (ID 1)</p>
      <div class="submit-buttons">
        <input type="submit" name="submit_onebyone" value="Apply Changes" />
      </div>
    </fieldset>
  </form>
</div>

<style>
  .content {
    padding: 20px;
  }

  table.border {
    border-collapse: collapse;
  }

  table.border th {
    background-color: #f0f0f0;
    font-weight: bold;
    text-align: center;
    color: #000;
    padding: 2px 5px;
    border: 1px solid #ccc;
  }

  table.border tbody td {
    border: 1px solid #ccc;
    padding: 2px 5px;
  }

  table.border tbody tr td.accent:nth-child(even) {
    background-color: #f4f4f4;
  }

  fieldset {
    margin-bottom: 20px;
    padding: 15px;
    border: 1px solid #ccc;
  }

  legend {
    font-weight: bold;
    padding: 0 10px;
  }

  .submit-buttons {
    margin-top: 20px;
  }

  input[type="submit"] {
    padding: 8px 16px;
    background-color: #4caf50;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
  }

  input[type="submit"]:hover {
    background-color: #45a049;
  }


</style>
