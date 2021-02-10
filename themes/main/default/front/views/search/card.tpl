{if !$request_async}{include file="includes/head.tpl"}{/if}

{if $content.card}
    <div class="container-card">
        <div class="card-image">
            <img src="{$content.card.image_card}" />
        </div>
        <div class="card-content-container">
            <h2>{$content.card.name_card}</h2>
            <div class="card-content">
                <table>
                    <tbody>
                        <tr>
                            <td>{$content.card.mana_cost_card}</td>
                        </tr>
                        <tr>
                            <td>{$content.card.type_card}</td>
                        </tr>
                        <tr>
                            <td>{$content.card.set_card}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    {if $content.players}
        <div class="container-card-decklists">
            <ul class="nav nav-tabs">
                {foreach from=$content.players key="i" item="format"}
                    <li{if $i==0} class="active"{/if}><a data-toggle="tab" href="#players_{$format.slug}">{$format.label}</a></li>
                {/foreach}
            </ul>
            <div class="tab-content">
                {foreach from=$content.players key="i" item="format"}
                    <div id="players_{$format.slug}" class="tab-pane fade{if $i==0} in active{/if}">
                        <table class="table table-striped table-condensed table-players">
                            <tbody>
                                <tr>
                                    <th>Archetype</th>
                                    <th>Player</th>
                                    <th>Tournament</th>
                                    <th>Record</th>
                                    <th>#copies</th>
                                    <td></td>
                                </tr>
                                {foreach from=$format.players item="player"}
                                    <tr>
                                        <td>
                                            <a href="deck/id:{$player.id_player}/" target="_blank">{$player.name_archetype}</a>
                                        </td>
                                        <td>
                                            {$player.arena_id}
                                        </td>
                                        <td>
                                            {$player.name_tournament} - {$player.date_tournament}
                                        </td>
                                        <td>
                                            {$player.wins}-{$player.matches-$player.wins}
                                        </td>
                                        <td>
                                            {if $player.count_main}{$player.count_main} MD {/if}
                                            {if $player.count_side}{$player.count_side} SB {/if}
                                        </td>
                                        <td>
                                            <a href="deck/id:{$player.id_player}/" class="btn btn-info" target="_blank">
                                                <span class="glyphicon glyphicon-duplicate"></span>
                                            </a>
                                        </td>
                                    </tr>
                                {foreachelse}
                                    <tr>
                                        <td colspan="6" class="bg-warning">No decklists found</td>
                                    </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    </div>
                {/foreach}
            </div>
        </div>
    {/if}
{/if}

{if !$request_async}{include file="includes/footer.tpl"}{/if}