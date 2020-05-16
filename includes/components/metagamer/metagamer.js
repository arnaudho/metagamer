(function(){
    function init(){
        // init forms loader
        var forms = document.querySelectorAll('.container form');
        if(forms.length > 0 ){
            forms.forEach(function(pElt) {
                pElt.addEventListener("submit", function(pEvt) {
                    // TODO - add spinner
                    pEvt.target.classList.add("loading");
                });
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
})();