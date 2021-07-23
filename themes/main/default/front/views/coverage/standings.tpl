{if !$request_async}{include file="includes/head.tpl"}{/if}

<div class="container-coverage">
    {if $content.players}
        <div class="sticky-header" id="sticky-header" style="background: white; margin-bottom: 20px;">
            <div class="logo"></div>
            <h1 class="upper"><span class="flag-icon flag-icon-fr flag-icon-squared"></span> Score des français</h1>
            {if $content.players}
                <h3 class="upper">{$content.name_tournament}</h3>
            {/if}
            <table class="table table-standings table-french" style="margin-bottom: 0;">
                <thead>
                <tr>
                    <th style="width: 100px;"></th>
                    <th style="width: 420px;"></th>
                    <th style="width: 102px;"></th>
                    <th style="width: 185px;" class="player-score">Score Standard</th>
                    <th style="width: 185px;" class="player-score">Score Historic</th>
                    <th style="width: 185px;" class="player-score">Score Total</th>
                </tr>
                </thead>
            </table>
        </div>
        <table class="table table-hover table-standings table-french">
            <thead>
            <tr>
                <th style="width: 100px;"></th>
                <th style="width: 420px;"></th>
                <th style="width: 102px;"></th>
                <th style="width: 185px;" class="player-score"></th>
                <th style="width: 185px;" class="player-score"></th>
                <th style="width: 185px;" class="player-score player-total"></th>
            </tr>
            </thead>
            <tbody>
            {foreach from=$content.players key="id_player" item="player"}
                <tr {if $player.disabled == 1}class="disabled"{/if}>
                    <td class="player-image"><img src="{$player.image_player}" /></td>
                    <td class="player-name">{$player.name_player}</td>
                    <td class="icon-player">
                        {if $player.tag_player}
                            <img src="{$player.tag_player}" />
                        {/if}
                    </td>
                    <td class="player-score">{$player.t1.wins}-{$player.t1.loss}<span class="player-archetype">{$player.decks[1]}</span></td>
                    <td class="player-score">{$player.t2.wins}-{$player.t2.loss}<span class="player-archetype">{$player.decks[2]}</span></td>
                    <td class="player-score">{$player.global.wins}-{$player.global.loss}</td>
                </tr>
            {/foreach}
            </tbody>
        </table>
        <p class="legend">Tiebreakers not informed</p>
    {else}
        <div class="alert alert-danger" role="alert">No players found</div>
    {/if}
</div>

{if !$request_async}{include file="includes/footer.tpl"}{/if}