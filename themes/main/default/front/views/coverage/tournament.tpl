{if !$request_async}{include file="includes/head.tpl"}{/if}

<div class="container-coverage">
    {if $content.error}
        <p class="bg-warning">{$content.error}</p>
    {else}
        {if $content.tournament && $content.players}
            <h1>{$content.tournament.name_tournament}</h1>
            <h3>{$content.tournament.date_tournament} - {$content.players|count} players</h3>
            <table class="table table-standings table-striped">
                <thead>
                    <tr>
                        <th class="image-archetype"></th>
                        <th>Archetype</th>
                        <th>Player</th>
                        <th>Record</th>
                        <th>Decklist</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$content.players key="rank" item="player"}
                        <tr>
                            <td class="image-archetype" style="background: no-repeat top -21px right 50%/116% url({$player.image_archetype});"></td>
                            <td class="name-archetype">{$player.name_archetype}</td>
                            <td class="name-player">{$player.arena_id}</td>
                            <td>{$player.wins}-{$player.matches-$player.wins}</td>
                            <td>
                                <a href="deck/id:{$player.id_player}/" class="btn btn-info" target="_blank">
                                    <span class="glyphicon glyphicon-duplicate"></span>
                                </a>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
            <p class="legend">Tiebreakers not informed, players with the same record are sorted alphabetically</p>
        {/if}
    {/if}
</div>

{if !$request_async}{include file="includes/footer.tpl"}{/if}