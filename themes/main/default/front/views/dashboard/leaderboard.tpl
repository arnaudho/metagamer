{if !$request_async}{include file="includes/head.tpl"}{/if}

<h1>Leaderboard</h1>

<p>
    <a href="dashboard/leaderboard/?detailed=1" class="btn btn-primary">Get detailed leaderboard</a>
</p>

<ul class="nav nav-tabs">
    <li class="active"><a data-toggle="tab" href="#mpl">MPL</a></li>
    <li><a data-toggle="tab" href="#rivals">Rivals</a></li>
</ul>
{if $content.detailed}
    <div class="tab-content">
        {if $content.mpl}
            <div id="mpl" class="tab-pane fade in active">
                <table class="table table-leaderboard leaderboard-detailed">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Name</th>
                            <th>Total points</th>
                            <th>League matches played</th>
                            <th>Winrate</th>
                            <th>Points behind</th>
                            {if $content.tournament_labels}
                                {foreach from=$content.tournament_labels item="tournament"}
                                    <th>{$tournament.name_tournament}</th>
                                {/foreach}
                            {/if}
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$content.mpl item="player"}
                            <tr>
                                <td>{$player.rank_player}</td>
                                <td class="player-name">{$player.name_player}</td>
                                <td>{$player.points_player}</td>
                                <td>{$player.total_matches}</td>
                                <td>{$player.winrate} %</td>
                                <td>{$player.points_behind}</td>
                                {if $content.tournament_labels}
                                    {foreach from=$content.tournament_labels item="tournament"}
                                        <td>{$player.detail[$tournament.id_tournament]}</td>
                                    {/foreach}
                                {/if}
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        {/if}
        {if $content.rivals}
            <div id="rivals" class="tab-pane fade">
                <table class="table table-leaderboard">
                    <thead>
                    <tr>
                        <th></th>
                        <th>Name</th>
                        <th>Total points</th>
                        <th>League matches played</th>
                        <th>Winrate</th>
                        <th>Points behind</th>
                        {if $content.tournament_labels}
                            {foreach from=$content.tournament_labels item="tournament"}
                                <th>{$tournament.name_tournament}</th>
                            {/foreach}
                        {/if}
                    </tr>
                    </thead>
                    <tbody>
                    {foreach from=$content.rivals item="player"}
                        <tr>
                            <td>{$player.rank_player}</td>
                            <td class="player-name">{$player.name_player}</td>
                            <td>{$player.points_player}</td>
                            <td>{$player.total_matches}</td>
                            <td>{$player.winrate} %</td>
                            <td>{$player.points_behind}</td>
                            {if $content.tournament_labels}
                                {foreach from=$content.tournament_labels item="tournament"}
                                    <td>{$player.detail[$tournament.id_tournament]}</td>
                                {/foreach}
                            {/if}
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
            </div>
        {/if}
    </div>
{else}
    <div class="tab-content container-leaderboard">
        <div class="background-placeholder"></div>
        {if $content.mpl}
            <div id="mpl" class="tab-pane fade in active">
                <h2>Magic Pro League</h2>
                <h3>2020-2021 Standings</h3>
                <hr />
                <table class="table table-leaderboard">
                    <thead>
                        <tr>
                            <th class="player-rank"></th>
                            <th class="player-diff"></th>
                            <th class="player-flag"></th>
                            <th class="player-name">Name</th>
                            <th class="player-points">Total points</th>
                            <th class="player-matches">League matches played</th>
                            <th class="player-finish">If the season <br />ended today</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$content.mpl item="player"}
                            {if $player.finish_player && $player.rank_player != 1}
                                <tr class="row-spacer">
                                    <td colspan="2"></td>
                                    <td colspan="3">
                                        {if $player.rank_player <= 5}MPL next year if the season ended today{/if}
                                    </td>
                                </tr>
                            {/if}
                            <tr>
                                <td class="player-rank">{$player.rank_player}</td>
                                <td class="player-diff {if $player.rank_diff_player > 0}diff-positive{else}{if $player.rank_diff_player < 0}diff-negative{/if}{/if}">
                                    <span class="glyphicon glyphicon-triangle-{if $player.rank_diff_player > 0}top{else}{if $player.rank_diff_player < 0}bottom{/if}{/if}">
                                    </span>{$player.rank_diff_player}
                                </td>
                                <td class="player-flag">
                                    <span class="flag-icon flag-icon-{$player.country_player} flag-icon-squared"></span>
                                </td>
                                <td class="player-name{if $player.mpl_next} player-mpl-next{/if}">{$player.name_player}</td>
                                <td class="player-points">{$player.points_player}</td>
                                <td class="player-matches">{$player.total_matches}</td>
                                {if $player.finish_player}
                                    <td class="player-finish" rowspan="{$player.finish_player.count}">
                                        <div class="player-finish-image">
                                            <img src="{$player.finish_player.image}" />
                                        </div>
                                    </td>
                                {/if}
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        {/if}
        {if $content.rivals}
            <div id="rivals" class="tab-pane fade">
                <h2>Rivals League</h2>
                <h3>2020-2021 Standings</h3>
                <hr />
                <table class="table table-leaderboard">
                    <thead>
                        <tr>
                            <th class="player-rank"></th>
                            <th class="player-diff"></th>
                            <th class="player-flag"></th>
                            <th class="player-name">Name</th>
                            <th class="player-points">Total points</th>
                            <th class="player-matches">League matches played</th>
                            <th class="player-finish">If the season <br />ended today</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$content.rivals item="player"}
                            {if $player.finish_player && $player.rank_player != 1}
                                <tr class="row-spacer">
                                    <td colspan="2"></td>
                                    <td colspan="3">
                                        {if $player.rank_player <= 5}MPL next year if the season ended today{/if}
                                    </td>
                                </tr>
                            {/if}
                            <tr>
                                <td class="player-rank">{$player.rank_player}</td>
                                <td class="player-diff {if $player.rank_diff_player > 0}diff-positive{else}{if $player.rank_diff_player < 0}diff-negative{/if}{/if}">
                                    <span class="glyphicon glyphicon-triangle-{if $player.rank_diff_player > 0}top{else}{if $player.rank_diff_player < 0}bottom{/if}{/if}">
                                    </span>{$player.rank_diff_player}
                                </td>
                                <td class="player-flag">
                                    <span class="flag-icon flag-icon-{$player.country_player} flag-icon-squared"></span>
                                </td>
                                <td class="player-name{if $player.mpl_next} player-mpl-next{/if}">{$player.name_player}</td>
                                <td class="player-points">{$player.points_player}</td>
                                <td class="player-matches">{$player.total_matches}</td>
                                {if $player.finish_player}
                                    <td class="player-finish" rowspan="{$player.finish_player.count}">
                                        <div class="player-finish-image">
                                            <img {if $player.finish_player.width}style="width:{$player.finish_player.width}px;"{/if}
                                                src="{$player.finish_player.image}" />
                                        </div>
                                    </td>
                                {/if}
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        {/if}
        <div class="legend">
            <p class="legend-rules">Tiebreakers not informed, players with equal score are ordered alphabetically</p>
            <div class="main-legend">
                <p>Last updated 28 Feb. 2021</p>
                <p>
                    <img src="https://pbs.twimg.com/profile_images/1180593036478148616/w58XZ5tf_400x400.jpg"
                        style="border-radius: 16px;" />
                    <strong> @JPBALL5</strong> X
                    <img src="{$content.img_path}logo.png" />
                </p>
            </div>
            <p class="legend-copyright">Wizards of the Coast, Magic: The Gathering, and their logos are trademarks of Wizards of the Coast LLC. © 1995-2021 Wizards. All rights reserved.</p>
        </div>
    </div>
{/if}


{if !$request_async}{include file="includes/footer.tpl"}{/if}