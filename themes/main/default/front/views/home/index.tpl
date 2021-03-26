{if !$request_async}{include file="includes/head.tpl"}{/if}

<div class="container-coverage">
    {if $content.players}
        <h1 class="upper"><span class="flag-icon flag-icon-fr flag-icon-squared"></span> Score des français</h1>
        <h3 class="upper">Kaldheim Championship</h3>
        <table class="table table-hover table-standings table-french">
            <thead>
                <tr>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th class="player-score">Score Standard</th>
                    <th class="player-score">Score Historic</th>
                    <th class="player-score">Score Total</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$content.players key="id_player" item="player"}
                    <tr>
                        <td class="player-image"><img src="{$player.image_player}" /></td>
                        <td class="player-name">{$player.name_player}</td>
                        <td class="icon-player">
                            {if $player.tag_player}
                                <img src="{$player.tag_player}" />
                            {/if}
                        </td>{*
                        <td class="player-score">{$player.t5288.wins}-{$player.t5288.loss}</td>
                        <td class="player-score">{$player.t5287.wins}-{$player.t5287.loss}</td>*}
                        <td class="player-score">{$player.t4090.wins}-{$player.t4090.loss}</td>
                        <td class="player-score">{$player.t4091.wins}-{$player.t4091.loss}</td>
                        <td class="player-score">{$player.global.wins}-{$player.global.loss}</td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    {/if}
</div>

{if !$request_async}{include file="includes/footer.tpl"}{/if}