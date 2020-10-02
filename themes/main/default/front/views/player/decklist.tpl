{if !$request_async}{include file="includes/head.tpl"}{/if}

{if $content.cards!==null}
    <div class="decklist decklist-visual">
        <div class="credits">
            MTG <span class="credits-highlight">DATA</span>
        </div>
        <h2>{$content.player.name_archetype} <small>by {$content.player.arena_id}</small></h2>
        <h3>{$content.player.name_tournament}</h3>
        <hr class="decklist-separator" />
        <div class="decklist-main-container">
            <h5 class="decklist-title-main">Main deck</h5>
            <div class="decklist-main">
                {foreach from=$content.cards item="card"}
                    {if $card.count_main > 0}
                        <div class="decklist-card">
                            <img src="{$card.image_card}" />
                            <span class="decklist-card-count" {if $card.count_main >= 10}style="width: 43px; padding: 2px 8px;"{/if}>
                                {$card.count_main}
                            </span>
                        </div>
                    {/if}
                {/foreach}
            </div>
        </div>
        <div class="decklist-side-container">
            <h5 class="decklist-title-side">Sideboard</h5>
            <div class="decklist-side">
                {foreach from=$content.cards item="card"}
                    {if $card.count_side > 0}
                        <div class="decklist-card" style="top: -{$card.side_margin}px;">
                            <img src="{$card.image_card}" />
                            <span class="decklist-card-count">{$card.count_side}</span>
                        </div>
                    {/if}
                {/foreach}
            </div>
        </div>
    </div>
    <div class="overlay-twitter-size"></div>
{/if}

{if !$request_async}{include file="includes/footer.tpl"}{/if}