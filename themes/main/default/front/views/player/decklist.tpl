{if !$request_async}{include file="includes/head.tpl"}{/if}

<h2>{$content.player.name_archetype} by {$content.player.arena_id} ({$content.player.wins}-{$content.player.matches-$content.player.wins})</h2>
<h3>{$content.player.name_tournament} ({$content.player.name_format})</h3>

{if $content.cards!==null}
    <div class="decklist">
        <h5>Maindeck</h5>
        <ul class="decklist-main">
            {foreach from=$content.cards item="card"}
                {if $card.count_total_main > 0}
                    <li>{$card.count_total_main} {$card.name_card}</li>
                {/if}
            {/foreach}
        </ul>
        <h5>Sideboard</h5>
        <ul class="decklist-main">
            {foreach from=$content.cards item="card"}
                {if $card.count_total_side > 0}
                    <li>{$card.count_total_side} {$card.name_card}</li>
                {/if}
            {/foreach}
        </ul>
    </div>
{/if}

{if !$request_async}{include file="includes/footer.tpl"}{/if}