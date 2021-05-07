{if !$request_async}{include file="includes/head.tpl"}{/if}

<a href="{$content.link_visual}" class="btn btn-info" target="_blank">
    <span class="glyphicon glyphicon-eye-open"></span> Visual decklist
</a> {$content.player.name_deck}
{if $content.cards_main!==null}
    <div class="decklist decklist-text">
        {if $content.logo}
            <div class="logo"></div>
        {/if}
        <h2>{$content.player.name_archetype} <small> {$content.player.arena_id}</small></h2>
        <h3>{$content.player.name_tournament}</h3>
        <hr class="decklist-separator" />
        <div class="decklist-cards-container">
            <div class="decklist-main-container">
                <h5 class="decklist-title-main">Main deck ({$content.player.count_cards_main})</h5>
                <ul class="decklist-main">
                    {foreach from=$content.cards_main item="card"}
                        {if $card.count_main > 0}
                            <li class="decklist-card">
                                <span class="decklist-card-name">{$card.count_main} {$card.name_card}</span>
                                <span class="decklist-card-mana">{$card.mana_cost_card}</span>
                            </li>
                        {/if}
                    {/foreach}
                </ul>
            </div>
            <div class="decklist-side-container">
                <h5 class="decklist-title-side">Sideboard ({$content.player.count_cards_side})</h5>
                <ul class="decklist-side">
                    {foreach from=$content.cards_side item="card"}
                        {if $card.count_side > 0}
                            <li class="decklist-card">
                                <span class="decklist-card-name">{$card.count_side} {$card.name_card}</span>
                                <span class="decklist-card-mana">{$card.mana_cost_card}</span>
                            </li>
                        {/if}
                    {/foreach}
                </ul>
            </div>
        </div>
    </div>
    {if $content.overlay_twitter==1}
        <div class="overlay-twitter-size"></div>
    {/if}
{/if}

{if !$request_async}{include file="includes/footer.tpl"}{/if}