{if !$request_async}{include file="includes/head.tpl"}{/if}

<div class="archetype-container">
    <div class="archetype-image"
         style="background: no-repeat top {if $deck.id_archetype==3}0{else}-58px{/if} right 50%/120%
                 url({$content.archetype.image_archetype});"></div>
    <div class="archetype-info">
        <h2>{$content.archetype.name_archetype}</h2>
        <h4>{$content.format.name_format}</h4>
        <h3>{$content.cards_main|count} decklists</h3>
    </div>
</div>

<ul class="nav nav-tabs">
    <li class="active"><a data-toggle="tab" href="#cards_md">Maindeck</a></li>
    <li><a data-toggle="tab" href="#cards_sb">Sideboard</a></li>
</ul>
<div class="tab-content">
    {if $content.cards_main !== null}
        <div id="cards_md" class="tab-pane fade in active">
            <table class="table table-hover table-condensed" data-toggle="table">
                <thead>
                    <tr>
                        <th>Decklist</th>
                        <th data-field="player_name" data-sortable="true">Player</th>
                        {foreach from=$content.all_cards_main item="name_card"}
                            <th data-field="card_{$name_card}" data-sortable="true">{$name_card}</th>
                        {/foreach}
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$content.cards_main key="id_player" item="player_cards"}
                        <tr>
                            <td>
                                <a href="deck/id:{$id_player}/" class="btn btn-info" target="_blank">
                                    <span class="glyphicon glyphicon-duplicate"></span>
                                </a>
                            </td>
                            <td>{$id_player}</td>
                            {foreach from=$player_cards item="card_count"}
                                <td>{$card_count}</td>
                            {/foreach}
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    {/if}
    {if $content.cards_side !== null}
        <div id="cards_sb" class="tab-pane fade">
            <table class="table table-hover table-condensed" data-toggle="table">
                <thead>
                    <tr>
                        <th>Decklist</th>
                        <th data-field="player_name" data-sortable="true">Player</th>
                        {foreach from=$content.all_cards_side item="name_card"}
                            <th data-field="card_{$name_card}" data-sortable="true">{$name_card}</th>
                        {/foreach}
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$content.cards_side key="id_player" item="player_cards"}
                        <tr>
                            <td>
                                <a href="deck/id:{$id_player}/" class="btn btn-info" target="_blank">
                                    <span class="glyphicon glyphicon-duplicate"></span>
                                </a>
                            </td>
                            <td>{$id_player}</td>
                            {foreach from=$player_cards item="card_count"}
                                <td>{$card_count}</td>
                            {/foreach}
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    {/if}
</div>

{if !$request_async}{include file="includes/footer.tpl"}{/if}