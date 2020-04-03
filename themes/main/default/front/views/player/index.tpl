{if !$request_async}{include file="includes/head.tpl"}{/if}

<h1>Player search</h1>
<form class="form-inline">
    <div class="form-group">
        <input type="text" placeholder="Search for player..." name="search" class="form-control">
        <button type="submit" class="btn btn-primary">Search</button>
        {if $content.players!==null}
            <em>{$content.players|count} results</em>
        {/if}
        <p>Note : search by Arena <em>#tag</em> may be more efficient</p>
    </div>
</form>
{if $content.players!==null}
    <table class="table table-striped">
        <tr>
            <th>Arena ID</th>
            <th>Tournament</th>
            <th>Record</th>
            <th>Archetype</th>
            <th>Decklist</th>
        </tr>
        {foreach from=$content.players item="player"}
            <tr>
                <td class="strong">{$player.arena_id}</td>
                <td>{$player.name_tournament}</td>
                <td>{$player.wins}-{$player.matches-$player.wins}</td>
                <td>{$player.name_archetype}</td>
                <td><a href="{$player.decklist_player}">View decklist</a></td>
            </tr>
        {foreachelse}
            <tr>
                <td colspan="5" class="bg-danger">No results found</td>
            </tr>
        {/foreach}
    </table>
{/if}

{if !$request_async}{include file="includes/footer.tpl"}{/if}