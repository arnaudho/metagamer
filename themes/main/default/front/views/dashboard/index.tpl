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

    {if $content.clean_duplicates == 1}
        <form method="post" action="">
            <input type="hidden" name="duplicates" value="1" />
            <button type="submit" class="btn btn-warning">Clean duplicate decklists</button>
        </form>
    {/if}
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
    <h2>Metagame breakdown <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseMetagame" aria-expanded="false" aria-controls="collapseMetagame">
            <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-th-list"></span></button></a></h2>
    <div id="collapseMetagame" class="panel-collapse collapse table-metagame-container" role="tabpanel">
        <div class="background-placeholder"></div>
        <h2>{$content.format.name_format}</h2>
        <h3>METAGAME BREAKDOWN</h3>
        <hr width="9%" />
        <table class="table-metagame">
            <tbody>
                <tr class="metagame-deck-name">
                    <td></td>
                    {foreach from=$content.condensed_metagame item="deck"}
                        <td>{$deck.name_archetype}</td>
                    {/foreach}
                </tr>
                <tr>
                    <td></td>
                    {foreach from=$content.condensed_metagame item="deck"}
                        <td class="metagame-deck-image">
                            <div class="deck-image"
                                style="background: no-repeat top {if $deck.id_archetype==3}0{else}-52px{/if} right 50%/273%
                                        url({$deck.image_archetype});"></div></td>
                    {/foreach}
                </tr>
                <tr class="metagame-deck-percent">
                    <td class="deck-legend">% Field</td>
                    {foreach from=$content.condensed_metagame item="deck"}
                        <td><sup class="percent-placeholder">%</sup>{$deck.percent}<sup>%</sup></td>
                    {/foreach}
                </tr>
                <tr class="metagame-deck-count">
                    <td class="deck-legend">Copies played</td>
                    {foreach from=$content.condensed_metagame item="deck"}
                        <td>{$deck.count}</td>
                    {/foreach}
                </tr>
            </tbody>
        </table>
        <div class="legend-container">
            <div class="logo"></div>
            <div class="legend">
                <p>Format : Historic - 20-22 Nov 2020</p>
                <p>Data source : <img src="https://mtgmelee.com/images/logo.png" style="width: 100px; display: inline-block;" /></p>
            </div>
        </div>
    </div>
    <table class="table table-hover table-condensed">
        <thead>
            <tr>
                <th>Archetype</th>
                <th>Count</th>
                <th>%</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$content.metagame item="deck"}
                <tr{if $deck.name_archetype == "Other"} class="active"{/if}>
                    <td>
                        <a href="archetype/lists/?id_archetype={$deck.id_archetype}&id_format={$content.format.id_format}" class="" target="_blank">
                            {$deck.name_archetype}
                        </a>
                    </td>
                    <td>{$deck.count}</td>
                    <td>{$deck.percent} %</td>
                </tr>
            {/foreach}
        </tbody>
    </table>
{/if}
{if $content.archetypes}
    <h2>Winrate by Archetype</h2>
    {if $content.confidence}
        <p>Confidence level : {$content.confidence}</p>
    {/if}
    <label for="matchups-export-mode">Export mode</label>
    <input type="checkbox" name="matchups-export-mode" id="matchups-export-mode" />
    <div class="matchups-container">
        <h1>{$content.format.name_format}</h1>
        <h3>{$content.date_format}</h3>
        <hr width="10%" />
        <table class="table table-condensed table-matchups">
            <tbody>
                <tr>
                    <th class="matchup-archetype-image"></th>
                    <th class="matchup-archetype-name"></th>
                    <th class="matchup-total"><div class="matchup-total-cell">WINRATE vs. Metagame</div></th>
                    {foreach from=$content.archetypes item="archetype"}
                        <th class="matchup-detail"><div>vs <span class="matchup-archetype-name">{$archetype.name_archetype}</span></div></th>
                    {/foreach}
                </tr>
                {foreach from=$content.archetypes item="archetype"}
                    <tr>
                        <td class="matchup-archetype-image" style="background: no-repeat top -25px right 50%/119% url({$archetype.image_archetype});"></td>
                        <td class="matchup-archetype-name">
                            <div class="archetype-name">{$archetype.name_archetype}</div>
                            <div class="archetype-count">{$archetype.count} decks ({$archetype.percent}<sup>%</sup>)</div>
                        </td>
                        {foreach from=$archetype.winrates item="deck"}
                            <td title="{$deck.count} matches" class="
                                {if $archetype.id_archetype==$deck.id_archetype}matchup-mirror {else}
                                    {if $deck.deviation <= 10}matchup-highlighted{/if}
                                {/if}
                                {if $deck.percent!==null}
                                    {if $deck.id_archetype==0} matchup-total{/if}
                                    {if $deck.percent==50} matchup-even{/if}
                                    {if $deck.percent>50}matchup-positive{/if}
                                    {if $deck.percent<50}matchup-negative{/if}
                                {/if}
                            ">
                                {if $archetype.id_archetype==$deck.id_archetype}
                                    <span class="round-dot"></span>
                                {else}
                                    {if $deck.percent!==null}
                                        {if $deck.id_archetype==0}<div class="matchup-total-cell">{/if}
                                        <div class="matchup-percent">{$deck.percent}<sup>%</sup></div>
                                        <span class="matchup-deviation">{$deck.deviation_down}<sup>%</sup> - {$deck.deviation_up}<sup>%</sup></span>
                                        {*<span class="matchup-count">{$deck.count}</span>*}

                                        {if $deck.id_archetype==0}</div>{/if}
                                    {else}
                                        -
                                    {/if}
                                {/if}
                            </td>
                        {/foreach}
                    </tr>
                {/foreach}
            </tbody>
        </table>
        <div class="legend-container">
            <table class="table table-matchups table-legend" style="width: auto;">
                <tbody>
                <tr>
                    <td class="">
                        <div class="matchup-percent">Winrate</div>
                        <span class="matchup-deviation">Confidence interval</span>
                    </td>
                </tr>
                </tbody>
            </table>
            <div class="legend">
                <div class="matchup-positive matchup-highlighted"></div>
                <div class="matchup-negative matchup-highlighted"></div>
            </div>
            <div class="legend" style="flex-grow: 2;">
                <p>Highlighted matchups are the ones with the strongest sample sizes <br />with as much as -10%/+10% confidence intervals</p>
                <p>Sample size : {$content.data.count_matches} matches | Confidence level : {$content.confidence} | Winrates do not include mirror matches</p>
            </div>
            <div class="legend" style="text-align: right;">
                <div class="">
                    <p style="display: inline-block;">Data source : <img src="https://mtgmelee.com/images/logo.png" style="width: 100px; display: inline-block;" /></p>
                </div>
                <div class="logo"></div>
            </div>
        </div>
    </div>
{/if}

{if !$request_async}{include file="includes/footer.tpl"}{/if}