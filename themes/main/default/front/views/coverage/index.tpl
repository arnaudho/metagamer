{if !$request_async}{include file="includes/head.tpl"}{/if}

<div class="container-coverage">
    {if $content.h1}
        <h1 class="upper">{$content.h1}</h1>
    {/if}
    {if $content.groups}
        {foreach from=$content.groups item="group"}
            <div class="list-group">
                {foreach from=$group item="tournament"}
                    <a href="{$tournament.link_tournament}" class="list-group-item list-group-item-action tournament-link">
                        <div class="tournament-image">
                            <img src="{$tournament.image_tournament}" />
                        </div>
                        <div class="tournament-info">
                            <h4 class="tournament-name">{$tournament.name_tournament}</h4>
                            <p class="tournament-date"><span class="glyphicon glyphicon-calendar"></span> {$tournament.date_tournament}</p>
                            <p class="tournament-count-players">{$tournament.count_players} players</p>
                        </div>
                    </a>
                {/foreach}
            </div>
        {/foreach}
    {/if}
</div>

{if !$request_async}{include file="includes/footer.tpl"}{/if}