<div class="content">
    <h2>Mass Access Management</h2>
    
    <form method="post" action="">
        <fieldset>
            <legend>Select Albums</legend>
            <div class="album-selection">
                <select name="albums[]" multiple size="10">
                    {foreach from=$albums item=album}
                    <option value="{$album.id}">{$album.name}</option>
                    {/foreach}
                </select>
            </div>
        </fieldset>

        <fieldset>
            <legend>Select Users/Groups</legend>
            <div class="user-selection">
                <select name="users[]" multiple size="10">
                    {foreach from=$users item=user}
                    <option value="{$user.id}">{$user.username}</option>
                    {/foreach}
                </select>
            </div>
        </fieldset>

        <fieldset>
            <legend>Access Level</legend>
            <div class="access-level">
                <select name="access_level">
                    <option value="private">Private</option>
                    <option value="public">Public</option>
                    <option value="custom">Custom</option>
                </select>
            </div>
        </fieldset>

        <div class="submit-buttons">
            <input type="submit" name="submit" value="Apply Changes">
        </div>
    </form>
</div>

<style>
.content {
    padding: 20px;
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

.album-selection,
.user-selection {
    margin: 10px 0;
}

select[multiple] {
    width: 100%;
    min-height: 200px;
}

.submit-buttons {
    margin-top: 20px;
}

input[type="submit"] {
    padding: 8px 16px;
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

input[type="submit"]:hover {
    background-color: #45a049;
}
</style> 