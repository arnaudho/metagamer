{if !$request_async}{include file="includes/head.tpl"}{/if}

{if $content.list_archetypes && $content.list_cards}
    <form class="form-inline">
        <div class="form-group">
            <select name="id_card" id="card-select" class="form-control">
                <option value="" disabled{if !$content.card} selected{/if}>Choose an card</option>
                {foreach from=$content.list_cards item="card"}
                    <option value="{$card.id_card}"{if $content.card.id_card == $card.id_card} selected{/if}>{$card.name_card}</option>
                {/foreach}
            </select>
            <select name="id_archetype" id="archetype-select" class="form-control">
                <option value="" disabled{if !$content.archetype} selected{/if}>Choose an archetype</option>
                {foreach from=$content.list_archetypes item="archetype"}
                    <option value="{$archetype.id_archetype}"{if $content.archetype.id_archetype == $archetype.id_archetype} selected{/if}>{$archetype.name_archetype}</option>
                {/foreach}
            </select>
            {include file="includes/list_formats.tpl"}
            <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-search"></span></button>
        </div>
    </form>
{/if}
{if $content.card}
    <div class="page-header">
        <h1>Card analysis - {$content.card.name_card}{if $content.format && $content.archetype} <small>{$content.archetype.name_archetype} - {$content.format.name_format}</small>{/if}</h1>
    </div>

    {if $content.count_card > 0 && $content.data}
        <table class="table table-hover">
            <thead>
                <tr>
                    <th></th>
                    {foreach from=$content.ids_archetypes item="id_a"}
                        <th>{$id_a}</th>
                    {/foreach}
                </tr>
            </thead>
            <tbody>
                {foreach from=$content.data key="count_copies" item="line"}
                    <tr>
                        <td>{$count_copies}</td>
                        {foreach from=$line item="winrate"}
                            <td>{$winrate}</td>
                        {/foreach}
                    </tr>
                {/foreach}
            </tbody>
        </table>
    {else}
        <p class="bg-danger">No results found for this card.</p>
    {/if}
{/if}

{if !$request_async}{include file="includes/footer.tpl"}{/if}