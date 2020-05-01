{if !$request_async}{include file="includes/head.tpl"}{/if}

{if $content.list_formats}
    <form class="form-inline">
        <div class="form-group">
            {include file="includes/list_formats.tpl"}
            <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-search"></span></button>
        </div>
    </form>
{/if}
{if $content.format}
    <h1>Archetypes - {$content.format.name_format}</h1>
{/if}

{if $content.archetypes}
    <table class="table table-hover table-condensed">
        <thead>
            <tr>
                <th>Archetype name</th>
                <th>CFB name</th>
                <th>Count</th>
                <th>Decklist example</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$content.archetypes item="archetype"}
                <tr>
                    <td>{$archetype.name_archetype}</td>
                    <td>{$archetype.name_deck}</td>
                    <td>{$archetype.count}</td>
                    <td>
                        <a href="{$archetype.decklist_player}" class="btn btn-info" target="_blank">
                            <span class="glyphicon glyphicon-duplicate"></span>
                        </a>
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>
{/if}

{if !$request_async}{include file="includes/footer.tpl"}{/if}