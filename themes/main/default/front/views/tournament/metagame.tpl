{if !$request_async}{include file="includes/head.tpl"}{/if}

{if $content.metagame}
    <label for="metagame-hide-winrates">Hide winrates</label>
    <input type="checkbox" name="metagame-hide-winrates" id="metagame-hide-winrates" />
    <div class="table-metagame-container">
        <div class="background-placeholder"></div>
        <div class="logo"></div>
        <h2>{$content.title}</h2>
        <h3>METAGAME BREAKDOWN</h3>
        <hr width="9%" />
        <table class="table-metagame">
            <tbody>
                <tr class="metagame-deck-name">
                    <td></td>
                    {foreach from=$content.metagame item="deck"}
                        <td>{$deck.name_archetype}</td>
                    {/foreach}
                </tr>
                <tr>
                    <td></td>
                    {foreach from=$content.metagame item="deck"}
                        <td class="metagame-deck-image">
                            <div class="deck-image"
                                 style="background: no-repeat top {if $deck.id_archetype==3}-20px right -50px/170%{else}-53px right 50%/273%{/if}
                                         url({$deck.image_archetype});"></div></td>
                    {/foreach}
                </tr>
                <tr class="metagame-deck-percent">
                    <td class="deck-legend">% Field</td>
                    {foreach from=$content.metagame item="deck"}
                        <td><sup class="percent-placeholder">%</sup>{$deck.percent}<sup>%</sup></td>
                    {/foreach}
                </tr>
                <tr class="metagame-deck-count">
                    <td class="deck-legend">Copies played</td>
                    {foreach from=$content.metagame item="deck"}
                        <td>{$deck.count}</td>
                    {/foreach}
                </tr>
                <tr class="metagame-deck-winrate">
                    <td class="deck-legend">Winrate</td>
                    {foreach from=$content.metagame item="deck"}
                        <td class="{if $deck.winrate > 50}winrate-positive{else}{if $deck.winrate < 50}winrate-negative{/if}{/if}">
                            <sup class="percent-placeholder">%</sup>{$deck.winrate}<sup>%</sup>
                        </td>
                    {/foreach}
                </tr>
            </tbody>
        </table>
        <div class="legend-container">
            <div class="legend">
                <p>{$content.date} - Sample size : {$content.count_matches} matches</p>
                <p>Data source : <img src="https://mtgmelee.com/images/logo.png" style="width: 100px; display: inline-block; vertical-align: middle;" /></p>
            </div>
        </div>
    </div>
{/if}

{if !$request_async}{include file="includes/footer.tpl"}{/if}