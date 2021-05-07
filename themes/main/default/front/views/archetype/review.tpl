{if !$request_async}{include file="includes/head.tpl"}{/if}

<div class="aggregate-container">
    <div class="archetype-container">
        <div class="archetype-image"
             style="background: no-repeat top {if $deck.id_archetype==3}0{else}-58px{/if} right 50%/120%
                     url({$content.archetype.image_archetype});"></div>
        <div class="archetype-info">
            <h2>Archetype review : {$content.archetype.name_archetype}</h2>
            <h4>{$content.name_format}</h4>
            <h3>Average distance : {$content.average_distance}</h3>
        </div>
    </div>

{if $content.aggregate_decklist}
    <div class="archetype-aggregate">
        <div class="card">
            <div class="card-header" id="headingOne">
                <h3 class="collapsed" data-toggle="collapse" data-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                    Aggregate decklist
                </h3>
            </div>

            <div id="collapseOne" class="collapse" aria-labelledby="headingOne" data-parent="#accordion">
                <div class="card-body">
                    <ul class="archetype-aggregate-decklist">
                        {foreach from=$content.aggregate_decklist key="name_card" item="count_card"}
                            <li>{$count_card} {$name_card}</li>
                        {/foreach}
                    </ul>
                </div>
            </div>
        </div>
    </div>
{/if}

{if $content.players}
    {foreach from=$content.players item="player"}
        <div class="player-check">
            <div class="player-check-header">
                <a href="deck/id:{$player.id_player}/" class="btn btn-info player-check-link" target="_blank">
                    <span class="glyphicon glyphicon-duplicate"></span>
                </a>
                <h3>{$player.name_player}</h3>
                <h4>{$player.name_deck}</h4>
            </div>
            <ul class="player-check-cards">
                {foreach from=$player.diff.removed item="card"}
                    <li>- {$card}</li>
                {/foreach}
            </ul>
            <ul class="player-check-cards">
                {foreach from=$player.diff.added item="card"}
                    <li>+ {$card}</li>
                {/foreach}
            </ul>
        </div>
    {/foreach}
{else}
    <div class="alert alert-warning">No decklists to review</div>
{/if}
</div>

{if !$request_async}{include file="includes/footer.tpl"}{/if}