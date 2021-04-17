{if !$request_async}{include file="includes/head.tpl"}{/if}

<div class="archetype-container">
    <div class="archetype-image"
         style="background: no-repeat top {if $deck.id_archetype==3}0{else}-58px{/if} right 50%/120%
                 url({$content.archetype.image_archetype});"></div>
    <div class="archetype-info">
        <h2>{$content.archetype.name_archetype}</h2>
        <h4>{$content.format.name_format}</h4>
        <h3>{$content.decklists|count} decklists</h3>
    </div>
</div>

{if $content.decklists !== null}
    <table class="table table-hover table-condensed" data-toggle="table"  data-search="true">
        <thead>
            <tr>
                <th data-field="player_name" data-sortable="true">Arena ID</th>
                <th data-field="player_tournament" data-sortable="true">Tournament</th>
                {if $content.decklists[0]['name_deck']}
                    <th data-field="player_deck_name" data-sortable="true">Deck name</th>
                {/if}
                <th data-field="player_record" data-sortable="true">Record</th>
                <th>Decklist</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$content.decklists item="decklist"}
                <tr>
                    <td><a href="player/?search={$decklist.arena_id}">{$decklist.arena_id}</a></td>
                    <td>
                        <a href="dashboard/?id_format={$decklist.id_format}&id_tournament={$decklist.id_tournament}">{$decklist.name_tournament}</a>
                        ({$decklist.date_tournament})
                    </td>
                    {if $decklist.name_deck}
                        <td>{$decklist.name_deck}</td>
                    {/if}
                    <td>{$decklist.wins}-{$decklist.total-$decklist.wins}</td>
                    <td>
                        <a href="deck/id:{$decklist.id_player}/" class="btn btn-info" target="_blank">
                            <span class="glyphicon glyphicon-duplicate"></span>
                        </a>
                    </td>
                </tr>
            {foreachelse}
                <tr>
                    <td><em>No decklist found</em></td>
                </tr>
            {/foreach}
        </tbody>
    </table>
{/if}

{if !$request_async}{include file="includes/footer.tpl"}{/if}