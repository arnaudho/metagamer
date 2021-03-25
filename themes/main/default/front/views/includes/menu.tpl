<menu id="main-menu">
    {foreach from=$content.menu_items item="item"}
        <a{if null !==($item.current) && $item.current} class="current"{/if} href="{$item.url}">
            {if $item.icon}<span class="glyphicon glyphicon-{$item.icon}"></span>{/if}
            {$item.label}
        </a>
    {/foreach}
</menu>