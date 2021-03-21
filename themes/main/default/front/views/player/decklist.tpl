{if !$request_async}{include file="includes/head.tpl"}{/if}

{if $content.cards_main!==null}
    <div class="decklist decklist-visual decklist-curve{if $content.aggregate == 1} decklist-aggregate{/if}">
        <div class="background-placeholder"></div>
        {if $content.logo}
            <div class="logo"></div>
        {/if}
        <h2>{$content.player.name_archetype} <small> {$content.player.arena_id}</small></h2>
        <h3>{$content.player.name_tournament}</h3>
        <hr class="decklist-separator" />
        <div class="decklist-main-container" style="width:{$content.maindeck_width}px">
            <h5 class="decklist-title-main">Maindeck ({$content.player.count_cards_main})</h5>
            <div class="decklist-main"{if $content.creatures_main_height} style="height: {$content.creatures_main_height}px;"{/if}>
                {foreach from=$content.cards_main item="mana"}
                    <div class="mana-curve" style="display: inline-block; vertical-align: top; width: 163px;">
                        {foreach from=$mana key="i" item="card"}
                            {if $card.count_main > 0}
                                <div class="decklist-card" style="display: block; width: 165px; top: {$i*-166}px;">
                                    <img src="{$card.image_card}" />
                                    <span class="decklist-card-count" style="{if $card.diff_card}right: 52px;{/if}{if $card.count_main >= 10}right: 34px;{/if}">
                                        x{$card.count_main}
                                    </span>
                                    {if $card.diff_card}
                                        <span class="decklist-card-count decklist-card-diff {if $card.diff_card > 0}decklist-card-diff-positive{else}{if $card.diff_card < 0}decklist-card-diff-negative{/if}{/if}">
                                            {$card.diff_card}
                                        </span>
                                    {/if}
                                    <span class="decklist-card-count-shadow" style="{if $card.diff_card}width: 37px;{/if}{if $card.count_main >= 10}width: 18px;{/if}"></span>
                                </div>
                            {/if}
                        {/foreach}
                    </div>
                {/foreach}
            </div>
            {if $content.cards_spells_main}
                <div class="decklist-main">
                    {foreach from=$content.cards_spells_main item="mana"}
                        <div class="mana-curve" style="display: inline-block; vertical-align: top; width: 163px;">
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
            {/if}
        </div>
        {if $content.cards_side!==null}
            <div class="decklist-side-container">
                <h5 class="decklist-title-side">Sideboard ({$content.player.count_cards_side})</h5>
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
                </div>
            </div>
        {/if}
    </div>
    {if $content.overlay_twitter==1}
        <div class="overlay-twitter-size"></div>
    {/if}
{/if}

{if !$request_async}{include file="includes/footer.tpl"}{/if}