{include file="includes/head.tpl"}
	<div id="connexion">
        <h1>Identification</h1>
        {if $content.error!=""}
        <div class='error'>{$content.error}</div>
        {/if}
        {form.login->display}
	</div>
{include file="includes/footer.tpl"}