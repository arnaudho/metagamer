<select name="id_format" id="format-select" class="form-control">
    <option value="" disabled{if !$content.format} selected{/if}>Choose a format</option>
    {foreach from=$content.list_formats item="format"}
        <option value="{$format.id_format}"{if $content.format.id_format == $format.id_format} selected{/if}>{$format.name_format}</option>
    {/foreach}
</select>
{if $content.list_tournaments}
    <select name="id_tournament" id="tournament-select" class="form-control">
        <option value="" {if !$content.tournament}selected{/if}>No tournament selected</option>
        {foreach from=$content.list_tournaments item="tournament"}
            <option value="{$tournament.id_tournament}"{if $content.tournament.id_tournament == $tournament.id_tournament} selected{/if}>{$tournament.name_tournament}</option>
        {/foreach}
    </select>
{/if}