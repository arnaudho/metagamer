{if !$request_async}{include file="includes/head.tpl"}{/if}

{if $content.archetypes}
    {if $content.link_dashboard}
        <a href="{$content.link_dashboard}"
           class="btn btn-default"><span class="glyphicon glyphicon-chevron-left"></span> Dashboard</a>
    {/if}
    {if $content.confidence}
        <p>Confidence level : {$content.confidence}</p>
    {/if}
    <label for="matchups-export-mode">Export mode</label>
    <input type="checkbox" name="matchups-export-mode" id="matchups-export-mode" />
    <div class="matchups-container">
        <div class="logo"></div>
        <h1>{$content.title}</h1>
        <h3>{$content.date}</h3>
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
                                    {if $deck.percent!==null && $deck.deviation_up-$deck.deviation_down <= 20}matchup-highlighted{/if}
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
                                    {*<span class="matchup-count">{$deck.count} matches</span>*}
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
                <p>Sample size : {$content.count_matches} matches | Confidence level : {$content.confidence} | Winrates do not include mirror matches</p>
            </div>
            <div class="legend" style="text-align: right;">
                <div class="">
                    <p style="display: inline-block;">Data source : <img src="https://mtgmelee.com/images/logo.png" style="width: 100px; display: inline-block;" /></p>
                </div>
            </div>
        </div>
    </div>

        {* TODO export texte *}{*

        TODO change format -- check last email

        <div style="margin-top: 40px;">
            <table>
                <tbody>
                <tr style="background-color: lightblue;">
                    <th>Archetype</th>
                    <th>% metagame</th>
                    <th class="matchup-total"><div class="matchup-total-cell">WINRATE vs. Metagame</div></th>
                    {foreach from=$content.archetypes item="archetype"}
                        <th class="matchup-detail"><div>vs <span class="matchup-archetype-name">{$archetype.name_archetype}</span></div></th>
                    {/foreach}
                </tr>
                {foreach from=$content.archetypes item="archetype"}
                    <tr>
                        <td rowspan="2">
                            <div class="archetype-name">{$archetype.name_archetype}</div>
                        </td>
                        <td>
                            <div class="archetype-count">{$archetype.count}</div>
                        </td>
                        {foreach from=$archetype.winrates item="deck"}
                            <td>
                                {if $deck.percent!==null}
                                    <div class="matchup-percent">{$deck.percent}<sup>%</sup></div>
                                {else}
                                    -
                                {/if}
                            </td>
                        {/foreach}
                    </tr>
                    <tr>
                        <td>{$archetype.percent}<sup>%</sup></td>
                        {foreach from=$archetype.winrates item="deck"}
                            <td>
                                {if $deck.percent!==null}
                                    <span class="matchup-count">{$deck.wins}/{$deck.count}</span>
                                {/if}
                            </td>
                        {/foreach}
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>*}
        {* TODO export texte *}

    {if $content.list_tournaments}
        <ul>
            {foreach from=$content.list_tournaments item="tournament"}
                <li>{$tournament.name_tournament}</li>
            {/foreach}
        </ul>
    {/if}
{/if}

{if !$request_async}{include file="includes/footer.tpl"}{/if}