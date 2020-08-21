{if !$request_async}{include file="includes/head.tpl"}{/if}

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
                        <form action="" method="post">
                            {foreach from=$content.metagame item="deck"}
                                {if $deck.name_archetype != "Other"}
                                    <div class="checkbox">
                                        <label>
                                            <input name="archetypes-select[{$deck.id_archetype}]" type="checkbox" value="{$deck.id_archetype}"{if !$content.other_archetypes[$deck.id_archetype]} checked{/if}>
                                            {$deck.name_archetype} <span class="small">({$deck.percent} %)</span>
                                        </label>
                                    </div>
                                {/if}
                            {/foreach}
                            <button type="submit" class="btn btn-info">Update dashboard</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <table class="table table-hover table-condensed">
        <tbody>
            <tr>
                <th>{$content.data.count_tournaments} tournaments</th>
                <th>{$content.data.count_players} players</th>
                <th title="{$content.data.percent} %">{$content.data.count_matches} matches</th>
            </tr>
        </tbody>
    </table>
    <h2>Metagame breakdown</h2>
    <table class="table table-hover table-condensed">
        <tbody>
            <tr>
                <th>Archetype</th>
                <th>Count</th>
                <th>%</th>
            </tr>
            {foreach from=$content.metagame item="deck"}
                <tr{if $deck.name_archetype == "Other"} class="active"{/if}>
                    <td>{$deck.name_archetype}</td>
                    <td>{$deck.count}</td>
                    <td>{$deck.percent} %</td>
                </tr>
            {/foreach}
        </tbody>
    </table>
{/if}
{if $content.archetypes}
    <h2>Winrate by Archetype</h2>
        <label for="matchups-export-mode">Export mode</label>
        <input type="checkbox" name="matchups-export-mode" id="matchups-export-mode" />
    <table class="table table-hover table-condensed table-matchups">
        <tbody>
            <tr>
                <th></th>
                {foreach from=$content.archetypes item="archetype"}
                    <th class="rotated-text"><div><span>{$archetype.name_archetype}</span></div></th>
                {/foreach}
                <th class="rotated-text matchup-total"><div><span>TOTAL</span></div></th>
                <th class="rotated-text matchup-total"><div><span>TOTAL + MIRROR</span></div></th>
            </tr>
            {foreach from=$content.archetypes item="archetype"}
                <tr>
                    <td class="strong">{$archetype.name_archetype}</td>
                    {foreach from=$archetype.winrates item="deck"}
                        <td class="
                            {if $deck.percent!==null}
                                {if $archetype.id_archetype==$deck.id_archetype} matchup-mirror{/if}
                                {if $deck.id_archetype==0} matchup-total{/if}
                                {if $deck.percent>50}{if $deck.percent>60}matchup-positive{else}matchup-slightly-positive{/if}{/if}
                                {if $deck.percent<50}{if $deck.percent<40}matchup-negative{else}matchup-slightly-negative{/if}{/if}
                            {/if}
                        ">
                            {if $deck.percent!==null}
                                <span class="matchup-deviation">{$deck.deviation_down}%-{$deck.deviation_up}%</span>
                                <div class="matchup-percent">{$deck.percent}</div>
                                <span class="matchup-count">{$deck.count}</span>
                            {else}
                                -
                            {/if}
                        </td>
                    {/foreach}
                </tr>
            {/foreach}
        </tbody>
    </table>
{/if}

{if !$request_async}{include file="includes/footer.tpl"}{/if}