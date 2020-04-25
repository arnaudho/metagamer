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
    }

    window.addEventListener('DOMContentLoaded', init, false);
})();