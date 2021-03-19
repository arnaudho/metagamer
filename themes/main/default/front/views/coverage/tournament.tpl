<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset={$configuration.global_encoding}" >
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <base href="{$configuration.server_url}"/>
    <title>{$head.title}</title>
    {if !null == $content.canonical && !empty($content.canonical)}
        <link rel="canonical" href="{$content.canonical}">
    {/if}
    <meta name="description" content="{$head.description}"/>
    <link type="text/css" rel="stylesheet" href="{$path_to_theme}/css/style.css">
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" integrity="sha384-HSMxcRTRxnN+Bdg0JdbxYKrThecOKuH5zCYotlSAcp1+c8xmyTe9GYg1l9a69psu" crossorigin="anonymous">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    {foreach from=$styles item=style}
        <link type="text/css" rel="stylesheet" href="{$style}">
    {/foreach}
    {foreach from="$scripts" item=script}
        <script type="text/javascript" src="{$script}"></script>
    {/foreach}
</head>
<body id="page-coverage">
    <div class="container">
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
                                    {*<a href="coverage/decklist/{$player.id_player}/" class="btn btn-info" target="_blank">*}
                                    <a href="deck/id:{$player.id_player}/" class="btn btn-info" target="_blank">
                                        <span class="glyphicon glyphicon-duplicate"></span>
                                    </a>
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            {/if}
        {/if}
    </div>
</body>
</html>