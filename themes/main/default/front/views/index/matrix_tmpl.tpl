{if !$request_async}{include file="includes/head.tpl"}{/if}

<div class="container">
    <table class="tmp-matrix">
        <thead>
            <tr>
                <th></th>
                {foreach from=$content.matrix item="player"}
                    <th class="name-player">{$player.name_player}</th>
                {/foreach}
            </tr>
        </thead>
        <tbody>
            {foreach from=$content.matrix key="key_player" item="player"}
                <tr>
                    <td class="name-player">
                        {$player.name_player}
                    </td>
                    {foreach from=$player.results key="key_result" item="result"}
                        <td class="{if $result.win_matches > $result.loss_matches}matchup-positive{else}{if $result.win_matches < $result.loss_matches}matchup-negative{else}matchup-even{/if}{/if}">
                            {if $key_player != $key_result}{$result.win_matches}-{$result.loss_matches}{/if}</td>
                    {/foreach}
                </tr>
            {/foreach}
        </tbody>
    </table>
    <hr />
    <table class="tmp-matrix">
        <thead>
            <tr>
                <th>Player name</th>
                <th>Season winrate</th>
                <th>Season winrate vs. League</th>
                <th>Standard winrate</th>
                <th>Historic winrate</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$content.global item="player"}
                <tr>
                    <td class="name-player">
                        {$player.name_player}
                    </td>
                    <td><span class="winrate-percent">{$player.global_season.percent} %</span>
                        <span class="winrate-detail">({$player.global_season.win_matches}-{$player.global_season.loss_matches})</span>
                    </td>
                    <td>{$player.global_league.percent} %
                        ({$player.global_league.win_matches}-{$player.global_league.loss_matches})
                    </td>
                    <td>{$player.standard.percent} %
                        ({$player.standard.win_matches}-{$player.standard.loss_matches})
                    </td>
                    <td>{$player.historic.percent} %
                        ({$player.historic.win_matches}-{$player.historic.loss_matches})
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>
</div>

{if !$request_async}{include file="includes/footer.tpl"}{/if}