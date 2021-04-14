{if !$request_async}{include file="includes/head.tpl"}{/if}

{if $content.clean_duplicates == 1}
    <form method="post" action="">
        <input type="hidden" name="duplicates" value="1" />
        <button type="submit" class="btn btn-warning">Clean duplicate decklists</button>
    </form>
{/if}

{if $content.list_formats}
    <form class="form-inline">
        <div class="form-group">
            {include file="includes/list_formats.tpl"}
            <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-search"></span></button>
        </div>
    </form>
{/if}
{if $content.format}
    <h1>{$content.format.name_format}{if $content.tournament} - {$content.tournament.name_tournament}{/if}</h1>
{/if}

{if $content.metagame}
    <div class="dashboard-container">
        <table class="table table-hover table-condensed">
            <tbody>
            <tr>
                <th>{$content.data.count_tournaments} tournaments</th>
                <th>{$content.data.count_players} players</th>
                <th title="{$content.data.percent} %">{$content.data.count_matches} matches</th>
            </tr>
            </tbody>
        </table>

        {if $content.link_metagame}
            <a href="{$content.link_metagame}"
               class="btn btn-default" target="_blank">Metagame breakdown <span class="glyphicon glyphicon-new-window"></span></a>
        {/if}
        <form action="{$content.link_matrix}" method="post" target="_blank" class="no-loader">
            <button type="submit" class="btn btn-default">Winrate matrix <span class="glyphicon glyphicon-th"></span></button>
            <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
                <div class="panel panel-default">
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingTwo">
                            <h4 class="panel-title">
                                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    Select which archetypes will be displayed in the winrates matrix
                                </a>
                            </h4>
                        </div>
                        <div id="collapseTwo" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo">
                            <div class="panel-body">
                                {foreach from=$content.metagame item="deck"}
                                    {if $deck.name_archetype != "Other"}
                                        <div class="checkbox">
                                            <label>
                                                <input name="archetypes-select[{$deck.id_archetype}]" type="checkbox" value="{$deck.id_archetype}"{if $deck.checked == 1} checked{/if}>
                                                {$deck.name_archetype} <span class="small">({$deck.percent} %)</span>
                                            </label>
                                        </div>
                                    {/if}
                                {/foreach}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <table class="table table-hover table-condensed table-standings">
            <thead>
            <tr>
                <th>Archetype</th>
                <th>Count</th>
                <th>Metagame %</th>
                <th class="archetype-decklists">Browse decklists</th>
                <th class="archetype-decklists">Aggregate decklist</th>
            </tr>
            </thead>
            <tbody>
            {foreach from=$content.metagame item="deck"}
                <tr{if $deck.name_archetype == "Other"} class="active"{/if}>
                    <td class="name-archetype">{$deck.name_archetype}</td>
                    <td>{$deck.count}</td>
                    <td>{$deck.percent} %</td>
                    <td>
                        <a href="archetype/lists/?id_archetype={$deck.id_archetype}&id_format={$content.format.id_format}" class="btn btn-info" target="_blank">
                            <span class="glyphicon glyphicon-duplicate"></span>
                        </a>
                    </td>
                    <td>
                        <a href="archetype/aggregatelist/?id_archetype={$deck.id_archetype}&id_format={$content.format.id_format}" class="btn btn-success" target="_blank">
                            <span class="glyphicon glyphicon-file"></span>
                        </a>
                    </td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    </div>
{/if}

{if !$request_async}{include file="includes/footer.tpl"}{/if}