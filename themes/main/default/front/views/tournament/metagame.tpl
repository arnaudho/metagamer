{if !$request_async}{include file="includes/head.tpl"}{/if}

{if $content.metagame}
    <div class="table-metagame-container">
        <div class="background-placeholder"></div>
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
                             style="background: no-repeat top {if $deck.id_archetype==3}-20px right -50px/170%{else}-52px right 50%/273%{/if}
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
            </tbody>
        </table>
        <div class="legend-container">
            <div class="logo"></div>
            <div class="legend">
                <p>{$content.date}</p>
                <p>Data source : <img src="https://mtgmelee.com/images/logo.png" style="width: 100px; display: inline-block; vertical-align: middle;" /></p>
            </div>
        </div>
    </div>
{/if}

{if !$request_async}{include file="includes/footer.tpl"}{/if}