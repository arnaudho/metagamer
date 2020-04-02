<menu id="main">
    {foreach from=$content.menu_items item="item"}
        <li>
            <a{if null !==($item.current) && $item.current} class="current"{/if} href="{$item.url}">{$item.label}</a>
        </li>
    {/foreach}
    <li class="global logout">
        <a href="index/logout/"><span></span></a>
    </li>
</menu>