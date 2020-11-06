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
	<body>
		<div class="container">
			<div class="menu">
				<a href="dashboard/"><span class="glyphicon glyphicon-dashboard"></span> Dashboard</a>
				<a href="dashboard/leaderboard/"><span class="glyphicon glyphicon-stats"></span> MPL</a>
				<a href="archetype/"><span class="glyphicon glyphicon-screenshot"></span> Analysis</a>
				<a href="player/"><span class="glyphicon glyphicon-user"></span> Players</a>
				<a href="tournament/import/"><span class="glyphicon glyphicon-save"></span> Import</a>
				<a href="tournament/search/"><span class="glyphicon glyphicon-search"></span> Tournaments</a>
				<a href="dashboard/archetypes/"><span class="glyphicon glyphicon-tasks"></span> Archetypes</a>
				<a href="index/logout/"><span class="glyphicon glyphicon-log-out"></span> Log out</a>
			</div>
			<div id="messages">
				{foreach from=$messages item="message"}
					<p class="bg-{$message.type}">{$message.message}</p>
				{/foreach}
			</div>