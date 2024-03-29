(function(){
    // TODO separate components on different pages
    function init(){
        // init forms loader
        var forms = document.querySelectorAll('.container form:not(.no-loader)');
        if(forms.length > 0 ){
            forms.forEach(function(pElt) {
                pElt.addEventListener("submit", function(pEvt) {
                    // TODO - add spinner
                    pEvt.target.classList.add("loading");
                });
            });
        }

        // handle copy decklist to clipboard button
        var button_export_arena = document.querySelector('.button-export-arena');
        if (button_export_arena) {
            button_export_arena.addEventListener("click", function(pEvt) {
                var export_arena_field = document.querySelector('#export-arena-field');
                export_arena_field.select();
                export_arena_field.setSelectionRange(0, 99999);
                document.execCommand("copy");
            });
        }

        // click on add / remove card
        var analysis_include = document.querySelectorAll('table.archetype-analysis .card-include, table.archetype-analysis .card-exclude');
        if (analysis_include.length > 0) {
            analysis_include.forEach(function(pElt) {
                pElt.addEventListener("click", function(pEvt) {
                    var id_card = pEvt.target.getAttribute('data-card-id');
                    postValue(id_card, "id_card");
                });
            });
        }

        // click on remove rule
        var remove_rules = document.querySelectorAll('.container .archetypes-info .rule-remove');
        if (remove_rules.length > 0) {
            remove_rules.forEach(function(pElt) {
                pElt.addEventListener("click", function(pEvt) {
                    var id_card = pEvt.target.getAttribute('data-card-id');
                    postValue(id_card, "remove_rule");
                });
            });
        }

        // load archetypes for selected format
        window.select_format = document.querySelectorAll('.container #format-select');
        window.select_archetypes = document.querySelectorAll('.container #archetype-select');

        if (select_format[0] && select_archetypes[0]) {
            select_format = select_format[0];
            select_archetypes = select_archetypes[0];
            select_format.addEventListener("change", function(pEvt) {
                var id_format = pEvt.target.value;
                Request.load(
                    'dashboard/data/',
                    {
                        action: 'get_archetypes_by_format',
                        id_format: id_format
                    }, 'POST')
                    .onComplete(populateArchetypesList);
            });
        }

        // enable link to tabs
        var hash = location.hash.replace(/^#/, '');     // only match the first hash
        if (hash) {
            $('.nav-tabs a[href="#' + hash + '"]').tab('show');
        }

        // Change hash for page-reload
        $('.nav-tabs a').on('shown.bs.tab', function (e) {
            window.location.hash = e.target.hash;
        })
    }

    function populateArchetypesList (pResponse) {
        if (pResponse.responseJSON && pResponse.responseJSON.content && pResponse.responseJSON.content.archetypes) {
            // clear select
            if (select_archetypes) {
                var archetypes = select_archetypes.querySelectorAll('option[value]:not([value=""])');
                if (archetypes) {
                    archetypes.forEach(function(pElt) {
                        select_archetypes.removeChild(pElt);
                    });
                    pResponse.responseJSON.content.archetypes.forEach(function(pElt) {
                        var opt = document.createElement('option');
                        opt.setAttribute("value", pElt.id_archetype);
                        opt.innerHTML = pElt.name_archetype;
                        select_archetypes.appendChild(opt);
                    });
                }
            }

        }
    }

    // submit value with given name for current page
    function postValue (pValue, pName) {
        var form = document.createElement("form");
        var elem = document.createElement("input");
        form.method = "POST";
        form.action = window.location.href;
        elem.value = pValue;
        elem.name = pName;
        form.appendChild(elem);
        document.body.appendChild(form);
        form.submit();
    }

    window.addEventListener('DOMContentLoaded', init, false);
    $(document).ready(function(){
        $('[data-toggle="tooltip"]').tooltip();
    });
})();