$(document).ready(function(){

    $('#user_profil_automaticCalculateEnergy').bind('click', function(){
        if($(this).is(':checked')){
            $('#user_profil_energy').val('');
        }else{
            $('#user_profil_energy').focus();
        }
    });

    $('#user_profil_energy').bind('keyup', function(){
        if($(this).val() == '')
        {
            $('#user_profil_automaticCalculateEnergy').prop('checked', 'checked');
        }else{
            $('#user_profil_automaticCalculateEnergy').prop('checked', false);
        }
    });

    $('#user_profil_energy').bind('focus', function(){
        $('#user_profil_automaticCalculateEnergy').prop('checked', false);
    });

});