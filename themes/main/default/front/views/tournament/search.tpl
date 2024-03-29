{if !$request_async}{include file="includes/head.tpl"}{/if}

<h1>Search tournament</h1>
<form class="form-inline">
    <div class="form-group">
        <select name="id" id="tournament-select" class="form-control">
            <option value="" disabled{if !$content.tournament} selected{/if}>Choose a tournament</option>
            {foreach from=$content.list_tournaments item="tournament"}
                <option value="{$tournament.id_tournament}"{if $content.tournament.id_tournament == $tournament.id_tournament} selected{/if}>{$tournament.name_tournament}</option>
            {/foreach}
        </select>
        <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-search"></span></button>
    </div>
</form>

{if $content.tournament}
    <hr />
    <h2>{$content.tournament.name_tournament} ({$content.tournament.count_players} players)</h2>

    {if $content.clean_duplicates == 1}
        <form method="post" action="">
            <input type="hidden" name="duplicates" value="1" />
            <button type="submit" class="btn btn-warning">Clean duplicate decklists</button>
        </form>
    {/if}
{/if}

{if $content.link_metagame}
    <a href="{$content.link_metagame}"
       class="btn btn-default" target="_blank">Metagame breakdown <span class="glyphicon glyphicon-new-window"></span></a>
{/if}

{if $content.metagame}
    <h3>Metagame breakdown</h3>
    <table class="table table-hover table-condensed">
        <tbody>
        <tr>
            <th>Archetype</th>
            <th>Count</th>
            <th>%</th>
            <th>Winrate</th>
        </tr>
        {foreach from=$content.metagame item="deck"}
            <tr{if $deck.name_archetype == "Other"} class="active"{/if}>
                <td>{$deck.name_archetype}</td>
                <td>{$deck.count}</td>
                <td>{$deck.percent} %</td>
                <td class="{if $deck.winrate>50}winrate-positive{else}winrate-negative{/if}">{$deck.winrate} %</td>
            </tr>
        {/foreach}
        </tbody>
    </table>
    <form method="post" action="">
        <input type="hidden" name="refresh" value="1" />
        <button type="submit" class="btn btn-info">Re-evaluate archetypes</button>
    </form>
{/if}

{if !$request_async}{include file="includes/footer.tpl"}{/if}