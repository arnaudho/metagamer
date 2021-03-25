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
		<!-- Bootstrap data sortable -->
		<link rel="stylesheet" href="https://unpkg.com/bootstrap-table@1.16.0/dist/bootstrap-table.min.css">
		<script src="https://unpkg.com/bootstrap-table@1.16.0/dist/bootstrap-table.min.js"></script>
		{foreach from=$styles item=style}
			<link type="text/css" rel="stylesheet" href="{$style}">
		{/foreach}
		{foreach from="$scripts" item=script}
			<script type="text/javascript" src="{$script}"></script>
		{/foreach}
	</head>
	<body>
		<div class="container">
			{include file="includes/menu.tpl"}
			<div id="messages">
				{foreach from=$messages item="message"}
					<p class="bg-{$message.type}">{$message.message}</p>
				{/foreach}
			</div>