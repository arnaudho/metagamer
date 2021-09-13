{if !$request_async}{include file="includes/head.tpl"}{/if}

<div class="decklist-header">
    <a href="{$content.link_visual}" class="btn btn-info" target="_blank">
        <span class="glyphicon glyphicon-eye-open"></span> Visual decklist
    </a> {$content.player.name_deck} ({$content.player.wins}-{$content.player.matches-$content.player.wins})
    {if $content.export_arena}
        <button class="btn btn-default button-export-arena">
            <span class="glyphicon glyphicon-download"></span> Export Arena
        </button>
        <textarea id="export-arena-field" readonly="">{$content.export_arena}</textarea>
    {/if}
</div>
{if $content.player.count_cards_main > 0}
    <div class="decklist decklist-text">
        {if $content.logo}
            <div class="logo"></div>
        {/if}
        <h2>{$content.player.name_archetype} <small> {$content.player.arena_id}</small></h2>
        <h3>{$content.player.name_tournament}</h3>
        <hr class="decklist-separator" />
        <div class="decklist-cards-container">
            <div class="decklist-main-container">
                <h4 class="decklist-title-main">Main deck ({$content.player.count_cards_main})</h4>
                <ul class="decklist-main">
                    {foreach from=$content.categories item="cat"}
                        {if $cat.count > 0}
                            <h5>{$cat.label} ({$cat.count})</h5>
                            {foreach from=$cat.cards item="card"}
                                {if $card.count_main > 0}
                                    <li class="decklist-card">
                                        <span class="decklist-card-name">{$card.count_main} {$card.name_card}</span>
                                        <span class="decklist-card-mana">{$card.mana_cost_card}</span>
                                    </li>
                                {/if}
                            {/foreach}
                        {/if}
                    {/foreach}
                </ul>
            </div>
            <div class="decklist-side-container">
                <h4 class="decklist-title-side">Sideboard ({$content.player.count_cards_side})</h4>
                <ul class="decklist-side">
                    {foreach from=$content.cards_side item="card"}
                        {if $card.count_side > 0}
                            <li class="decklist-card">
                                <span class="decklist-card-mana">{$card.mana_cost_card}</span>
                                <span class="decklist-card-name">{$card.count_side} {$card.name_card}</span>
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