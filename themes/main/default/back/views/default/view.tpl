{include file="includes/head.tpl"}

<h1>{$content.h1}</h1>
<div class="back">
    <a href="{$controller}/" class="button {if null == $content.actions.listing}disabled{/if}">Retour à la liste</a>
</div>
<div class="details">
    <table class="data">
    {foreach from=$content.data item=v key=k}
        {if !is_array($v)}
            <tr class="field">
                <td class="label">{$k}</td>
                {if !empty($v)}
                    <td>{$v}</td>
                {else}
                    <td class="empty">Non-Renseigné</td>
                {/if}
            </tr>
        {/if}
    {/foreach}
    </table>

    {foreach from=$content.data item=v key=k}
        {if is_array($v)}
            <h2>{$k}</h2>
            <table class="data secondary {$k}">
                <tr>
                {foreach from=$v[0] item=va key=ke}
                    <th>{$ke}</th>
                {/foreach}
                </tr>
                {foreach from=$v item=va key=ke}
                    <tr class="field">
                        {foreach from=$v[0] item=item key=key}
                            {if !empty($va[$key])}
                                <td>{$va[$key]}</td>
                            {else}
                                <td class="empty">Non-Renseigné</td>
                            {/if}
                        {/foreach}
                    </tr>
                {/foreach}
            </table>
        {/if}
    {/foreach}
</div>
{include file="includes/footer.tpl"}