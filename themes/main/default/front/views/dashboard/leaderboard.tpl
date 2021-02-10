{if !$request_async}{include file="includes/head.tpl"}{/if}

<h1>Leaderboard</h1>

<ul class="nav nav-tabs">
    <li class="active"><a data-toggle="tab" href="#mpl">MPL</a></li>
    <li><a data-toggle="tab" href="#rivals">Rivals</a></li>
</ul>

<div class="tab-content">
    {if $content.mpl}
        <div id="mpl" class="tab-pane fade in active">
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
                    {foreach from=$content.mpl item="player"}
                        <tr>
                            <td>{$player.rank_player}</td>
                            <td>{$player.name_player}</td>
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
                        <td>{$player.name_player}</td>
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


{if !$request_async}{include file="includes/footer.tpl"}{/if}