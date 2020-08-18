{if !$request_async}{include file="includes/head.tpl"}{/if}

{if $content.decklists !== null}
    <h2>{$content.decklists|count} decklists</h2>
    <table class="table table-hover table-condensed">
        <thead>
            <tr>
                <th>Arena ID</th>
                <th>Tournament</th>
                <th>Record</th>
                <th>Decklist</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$content.decklists item="decklist"}
                <tr>
                    <td><a href="player/?search={$decklist.arena_id}">{$decklist.arena_id}</a></td>
                    <td><a href="dashboard/?id_format={$decklist.id_format}&id_tournament={$decklist.id_tournament}">{$decklist.name_tournament}</a></td>
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