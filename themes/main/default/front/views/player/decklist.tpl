{if !$request_async}{include file="includes/head.tpl"}{/if}

{if $content.cards!==null}
    <div class="decklist decklist-visual">
        <h2>{$content.player.name_archetype} <small>by {$content.player.arena_id}</small></h2>
        <h3>{$content.player.name_tournament}</h3>
        <hr class="decklist-separator" />
        <h5 class="decklist-title-main">Main deck</h5>
        <div class="decklist-main">
            {foreach from=$content.cards item="card"}
                {if $card.count_main > 0}
                    <div class="decklist-card">
                        <img src="{$card.image_card}" />
                        <span class="decklist-card-count">{$card.count_main}</span>
                    </div>
                {/if}
            {/foreach}
        </div>
        <h5 class="decklist-title-side">Sideboard</h5>
        <div class="decklist-side">
            {foreach from=$content.cards item="card"}
                {if $card.count_side > 0}
                    <div class="decklist-card">
                        <img src="{$card.image_card}" />
                        <span class="decklist-card-count">{$card.count_side}</span>
                    </div>
                {/if}
            {/foreach}
        </div>
        <div class="decklist-credits">
            twitter <span class="credits-highlight">@mtg_data</span>
        </div>
    </div>
    <div class="overlay-twitter-size"></div>
{/if}

{if !$request_async}{include file="includes/footer.tpl"}{/if}