
$(function () {
// Document.ready -> link up remove event handler

    /*******************************
     **
     ** Gestion des dates avec DatTimePicker
     **
     *******************************/

    $('#situation_datedebut').datepicker({
        format: "dd/mm/yyyy",
        language: "fr",
        autoclose: true,
        todayHighlight: true
    });

    $('#situation_datefin').datepicker({
        format: "dd/mm/yyyy",
        language: "fr",
        autoclose: true,
        todayHighlight: true
    });

    function dateChange() {
        // var dateTest = new Date(2017, 10, 30);


        var strDate = $('#situation_datedebut').val();
        try {
            if (strDate != "") {
                var arrayDate = strDate.split('/');
                var dateDebut = new Date(arrayDate[2], arrayDate[1], arrayDate[0]);

                strDate = $('#situation_datefin').val();
                if (strDate != "") {
                    arrayDate = strDate.split('/');
                    var dateFin = new Date(arrayDate[2], arrayDate[1], arrayDate[0]);

                    /*var diff  = new Date(dateFin - dateDebut);
                     var days  = diff/1000/60/60/24;
                     var weeks = Math.ceil(days/7);*/

                    var interval = dateFin.getTime() - dateDebut.getTime();
                    var msecPerDay = 1000 * 60 * 60 * 24;
                    var days = Math.floor(interval / msecPerDay );
                    var weeks = Math.ceil(days/7);

                    $('#duree').val(weeks + " semaines");
                }
            }
        }
        catch(err) {
            $('#duree').val("");
        }
    }

    function deleteIntituleActivite() {
        // Obtention de la référence
        var idStage = $('#idStage').val();
        var oTR = $(this).parent().parent();
        var idIntitule = oTR.attr("idintitule");

        // Perform the ajax post
        $.post("/stage/deleteIntitule", { "idStage": idStage, "idIntitule": idIntitule },
            function (data) {
                // Successful requests get here
                // Update the page elements
                if (data.status == 1) {
                    var oTR = $('#intitule' + data.id);
                }
                else {
                    alert("L'activité n'a pas pu être retirée !")
                }
            }
        );
    }

    $('#situation_datedebut').bind('change', dateChange);
    $('#situation_datefin').bind('change', dateChange);

    function deleteSituationActivite() {
        // Obtention de la référence
        var situationReference = $('#situationReference').val();
        var oTR = $(this).parent().parent();
        var activiteId = oTR.attr("activiteId");

        // Perform the ajax post
        $.post("/situation/removeactivite", { "id": activiteId, "reference": situationReference },
            function (data) {
                // Successful requests get here
                // Update the page elements
                if (data.status == 1) {
                    var oTR = $('#activite' + data.id);
                    oTR.attr('class', 'tr_nonactif');
                    var spanok = oTR.find('#spanok');
                    spanok.hide();
                    var oRetirer = oTR.find('#deleteSituationActivite');
                    oRetirer.hide();
                    var oAjouter = oTR.find('#addSituationActivite');
                    oAjouter.attr("style", "display: 'block'");
                    oAjouter.show()

                    if ($('#showSituationActivitiesOnly').prop('checked')) {
                        oTR.hide();
                    }
                }
                else {
                    alert("L'activité n'a pas pu être retirée !")
                }
            }
        );
    }

    function addSituationActivite() {
        // Obtention de la référence
        var situationReference = $('#situationReference').val();
        var oTR = $(this).parent().parent();
        var activiteId = oTR.attr("activiteId");

        // Perform the ajax post
        $.post("/situation/addactivite", { "id": activiteId, "reference": situationReference },
            function (data) {
                // Successful requests get here
                // Update the page elements
                if (data.status == 1) {
                    var oTR = $('#activite' + data.id);
                    oTR.attr('class', 'tr_actif');
                    var spanok = oTR.find('#spanok');
                    spanok.show();
                    var oRetirer = oTR.find('#deleteSituationActivite');
                    oRetirer.attr("style", "display: 'block'");
                    oRetirer.show();
                    var oAjouter = oTR.find('#addSituationActivite');
                    oAjouter.hide();
                }
                else {
                    alert("L'activité n'a pas pu être ajoutée !")
                }
            }
        );
    }

    $('#showSituationActivitiesOnly').click( function () {

        if ($(this).prop('checked')) {
            $("#listeActivites > tbody > tr.tr_nonactif").each(function() {
                var tr = $(this);
                tr.hide();
            });
        } else {
            $("#listeActivites > tbody > tr").each(function() {
                var tr = $(this);
                tr.show();
            });
        }
    });

    $('a[name=addSituationActivite]').bind('click', addSituationActivite);
    $('a[name=deleteSituationActivite]').bind('click', deleteSituationActivite);



    $('#radioParcoursSLAM').click( function () {
        $('#divParcoursSLAM').show();
        $('#divParcoursSISR').hide();
    });
    $('#radioParcoursSISR').click( function () {
        $('#divParcoursSLAM').hide();
        $('#divParcoursSISR').show();
    });

    $('#situationE4').click( function () {

        var disabled = ! ($(this).prop('checked'));
        $("[id^='e4_']").each(function (index, value) {
            $(this).attr("disabled", disabled);
        });
    });

    // Bouton collapse
    $('.collapse').on('shown.bs.collapse', function(){
        $(this).parent().find(".glyphicon-plus").removeClass("glyphicon-plus").addClass("glyphicon-minus");
    }).on('hidden.bs.collapse', function(){
        $(this).parent().find(".glyphicon-minus").removeClass("glyphicon-minus").addClass("glyphicon-plus");
    });
});
