{if !$request_async}{include file="includes/head.tpl"}{/if}

<form class="form-inline" id="search-form">
    <div class="form-group">
        <input type="text" placeholder="" name="q" class="form-control" value="{$content.term}" autofocus>
        <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-search"></span></button>
    </div>
</form>
{if $content.results}
    <p><em>{$content.count_results} results</em></p>
    <table class="table table-results">
        <tr>
            {foreach from=$content.results item="item"}
                <th>{$item.label}{if $item.count > 0}{/if} ({$item.count})</th>
            {/foreach}
        </tr>
        {foreach from=$content.max_results item="i"}
            <tr>
                {foreach from=$content.results item="item"}
                    <td>
                        {if $item.elements[$i]}
                            {if $item.label == "Tournaments"}
                                <a href="dashboard/?id_format={$item.elements[$i]['id_format']}&id_tournament={$item.elements[$i]['id_tournament']}">{$item.elements[$i]['name_tournament']}</a><br />
                                {$item.elements[$i]['date_tournament']}
                            {/if}
                            {if $item.label == "Archetypes"}
                                <span class="strong">{$item.elements[$i]['name_archetype']}</span><br />
                                <span class="small">{$item.elements[$i]['name_format']}</span>
                            {/if}
                            {if $item.label == "Formats"}
                                <a href="dashboard/?id_format={$item.elements[$i]['id_format']}">{$item.elements[$i]['name_format']}</a>
                            {/if}
                            {if $item.label == "Cards"}
                                {*<div class="archetype-image" style="height: 24px; width: 50px; display: inline-block; background: no-repeat top -32px right 50%/120% url({$item.elements[$i]['image_card']});"></div>*}
                                <a href="card/{$item.elements[$i]['id_card']}/"><span class="strong">{$item.elements[$i]['name_card']}</span></a><br />
                                <span class="small">{$item.elements[$i]['mana_cost_card']}</span>
                            {/if}
                            {if $item.label == "Players"}
                                <a href="player/?search={$item.elements[$i]['arena_id']}">{$item.elements[$i]['arena_id']}</a>
                            {/if}
                        {/if}
                    </td>
                {/foreach}
            </tr>
        {/foreach}
        <tr>
            {foreach from=$content.results item="item"}
                <td>
                    {if $item.count > 10}
                        More results >
                    {/if}
                </td>
            {/foreach}
        </tr>
    </table>
{/if}

{if !$request_async}{include file="includes/footer.tpl"}{/if}