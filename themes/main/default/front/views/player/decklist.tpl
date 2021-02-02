{if !$request_async}{include file="includes/head.tpl"}{/if}

{if $content.cards_main!==null}
    <div class="decklist decklist-visual decklist-curve">
        <div class="logo"></div>
        <h2>{$content.player.name_archetype} <small>by {$content.player.arena_id}</small></h2>
        <h3>{$content.player.name_tournament}</h3>
        <hr class="decklist-separator" />
        <div class="decklist-main-container">
            <h5 class="decklist-title-main">Main deck</h5>
            <div class="decklist-main">
                {foreach from=$content.cards_main item="mana"}
                    <div class="mana-curve" style="display: inline-block; vertical-align: top;">
                        {foreach from=$mana key="i" item="card"}
                            {if $card.count_main > 0}
                                <div class="decklist-card" style="display: block; width: 165px; top: {$i*-166}px;">
                                    <img src="{$card.image_card}" />
                                    <span class="decklist-card-count" {if $card.count_main >= 10}style="right: 34px;"{/if}>
                                        x{$card.count_main}
                                    </span>
                                    <span class="decklist-card-count-shadow" {if $card.count_main >= 10}style="width: 18px;"{/if}></span>
                                </div>
                            {/if}
                        {/foreach}
                    </div>
                {/foreach}
            </div>
        </div>
        <div class="decklist-side-container">
            <h5 class="decklist-title-side">Sideboard</h5>
            <div class="decklist-side">
                {foreach from=$content.cards_side key="i" item="card"}
                    {if $card.count_side > 0}
                        <div class="decklist-card" style="top: {$i*-180}px;">
                            <img src="{$card.image_card}" />
                            <span class="decklist-card-count" {if $card.count_side >= 10}style="right: 34px;"{/if}>
                                x{$card.count_side}
                            </span>
                            <span class="decklist-card-count-shadow" {if $card.count_side >= 10}style="width: 18px;"{/if}></span>
                        </div>
                    {/if}
                {/foreach}
                {if $content.sideboard_more}
                    <div class="decklist-more">...</div>
                {/if}
            </div>
        </div>
    </div>
    <div class="overlay-twitter-size"></div>
{/if}

{if !$request_async}{include file="includes/footer.tpl"}{/if}