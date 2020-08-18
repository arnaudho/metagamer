{if !$request_async}{include file="includes/head.tpl"}{/if}

<h1>Player search</h1>
<form class="form-inline">
    <div class="form-group">
        <input type="text" placeholder="Search for player..." name="search" class="form-control">
        <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-search"></span></button>
        {if $content.players!==null}
            <em>{$content.players|count} results</em>
        {/if}
        <p class="text-info">Note : search by Arena <em>#tag</em> may be more efficient</p>
    </div>
</form>
{if $content.players!==null}
    <table class="table table-striped table-condensed table-players">
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
                <td><a href="dashboard/?id_format={$player.id_format}&id_tournament={$player.id_tournament}">{$player.name_tournament}</a> ({$player.name_format})</td>
                <td>{$player.wins}-{$player.matches-$player.wins}</td>
                <td>{$player.name_archetype}</td>
                <td>
                    <a href="{$player.decklist_player}" class="btn btn-info" target="_blank">
                        <span class="glyphicon glyphicon-duplicate"></span>
                    </a>
                </td>
            </tr>
        {foreachelse}
            <tr>
                <td colspan="5" class="bg-danger">No results found</td>
            </tr>
        {/foreach}
    </table>
{/if}

{if !$request_async}{include file="includes/footer.tpl"}{/if}