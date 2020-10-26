{if !$request_async}{include file="includes/head.tpl"}{/if}

<h1>Archetypes mapping</h1>

<p class="bg-info">
    Archetypes order matter !<br /> A rule will only be tested if all previous archetypes did not match for a given decklist.
</p>

<table class="table table-hover table-condensed table-archetypes">
    <thead>
        <tr>
            <th class="archetype-image">Image</th>
            <th>Archetype</th>
            <th>Must contain</th>
            <th>Must not contain</th>
        </tr>
    </thead>
    <tbody>
        {foreach from=$content.archetypes key="archetype" item="rules"}
            <tr>
                <td class="archetype-image" style="background: no-repeat  top -32px right 50%/120% url({$rules.image_card});"></td>
                <td class="archetype-name">{$archetype}</td>
                <td>
                    {foreach from=$rules.contains item="card"}
                        {$card}<br />
                    {/foreach}
                </td>
                <td>
                    {foreach from=$rules.exclude item="card"}
                        {$card}<br />
                    {/foreach}
                </td>
            </tr>
        {/foreach}
        <tr>
            <td>Tier 3 - Other</td>
            <td colspan="2">Everything else</td>
        </tr>
    </tbody>
</table>
{*
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
*}

{if !$request_async}{include file="includes/footer.tpl"}{/if}