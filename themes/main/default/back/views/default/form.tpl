{include file="includes/head.tpl"}
<h1>{$content.h1}</h1>
<div class="back">
    <a href="{$controller}/" class="button {if null==$content.actions.listing}disabled{/if}">Retour à la liste</a>
</div>
{if null != $content.error}
    <div class="error">
        {$content.error}
    </div>
{/if}
{if null != $content.confirmation}
	<div class="confirmation">
		{$content.confirmation}
	</div>
{/if}
<div class="details form">
	{if null !== $global.get.id}
		{form.instance->display id=$global.get.id}
	{else}
		{form.instance->display}
	{/if}
</div>
{include file="includes/footer.tpl"}