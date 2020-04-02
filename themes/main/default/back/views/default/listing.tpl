{include file="includes/head.tpl"}

<h1>{$content.h1}</h1>
<div class="new-entry">
    <a href="{$controller}/add/" class="button {if null==$content.actions.add}disabled{/if}">Ajouter une entr&eacute;e</a>
</div>
<table>
    <thead>
        <tr>
            {foreach from=$content.titles item=title}
                <th class="{$title.champ}-title">
                    {$title.label}<br/>{if $title.order}
                    <a href="{$controller}/listing/order:{$title.champ}/by:asc/" class="button small asc"><span></span></a>
                    <a href="{$controller}/listing/order:{$title.champ}/by:desc/" class="button small desc"><span></span></a>{/if}
                </th>
            {/foreach}
            {foreach from=$content.actions item="action" key="className"}
                {if $action.applyToEntry}
                    <th class="{$className}"></th>
                {/if}
            {/foreach}
        </tr>
    </thead>
    <tbody>
{foreach from=$content.liste item=item key=k}
        <tr class="{if $k%2==0}even{else}odd{/if}">
            {foreach from=$content.titles item=title}
                <td>{$item[$title.champ]}</td>
            {/foreach}
            {foreach from=$content.actions item="action" key="className"}
                {if $action.applyToEntry}
                    <td class="{$className}">
                        <a href="{$controller}/{$action.name}/id:{$item[$content.id]}/" class="button {$className}"><span></span></a>
                    </td>
                {/if}
            {/foreach}
        </tr>
        {foreachelse}

        <tr class="empty">
            <td>
                Aucun enregistrement
            </td>
        </tr>
    {/foreach}
    </tbody>
</table>
{if $content.pagination}
    {content.pagination->display}
{/if}
{include file="includes/footer.tpl"}