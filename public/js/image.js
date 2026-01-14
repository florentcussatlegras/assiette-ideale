
$(document).ready(function(){

    $('.custom-file-input').on('change', function(event){

        var input = event.currentTarget;
        var deletePics = $('.delete-pic');

        if (input.files && input.files[0]) {

            $('#custom-file-preview').empty();

            $.each(input.files, function(i, value){
                
                var reader = new FileReader();

                if(deletePics[0]) {
                    var index = $('.delete-pic').last().data('rank') + 1 + i;
                    reader.onload = function (e) {
                        $('#custom-file-preview').append('<div class="w-24 h-24 relative image-fit mb-5 mr-5 cursor-pointer zoom-in"><img class="rounded-md" id="preview-img" src="' + e.target.result + '" alt="your image" /><a data-rank="' + index + '" href="#" title="Supprimer l\'image?" class="delete-pic tooltip w-5 h-5 flex items-center justify-center absolute rounded-full text-white bg-theme-24 right-0 top-0 -mr-2 -mt-2"> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x w-4 h-4"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg> </a></div>');
                    }
                }else{
                    reader.onload = function (e) {
                        $('#custom-file-preview').append('<div class="w-24 h-24 relative image-fit mb-5 mr-5 cursor-pointer zoom-in"><img class="rounded-md" id="preview-img" src="' + e.target.result + '" alt="your image" /><a data-rank="' + i + '" href="#" title="Supprimer l\'image?" class="delete-pic tooltip w-5 h-5 flex items-center justify-center absolute rounded-full text-white bg-theme-24 right-0 top-0 -mr-2 -mt-2"> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x w-4 h-4"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg> </a></div>');
                    }
                }

                reader.readAsDataURL(value);

            });

        }

    });


    $(document).on('click', '.delete-pic', function(event){
        event.preventDefault();
        $("#dish_form_picRankForDelete").val($(this).data('rank'));
        $("form[name='dish_form']").submit();
    });


    $('#user_profil_pictureFile').on('change', function(event){

        var input = event.currentTarget;
        if (input.files && input.files[0]) {
            $('#user_profil_picture_preview').empty();
            $.each(input.files, function(index, value){
                var reader = new FileReader();
                reader.onload = function (e) {
                    $('#user_profil_picture_preview').attr('src', e.target.result);
                }
                reader.readAsDataURL(value);
            });
        }

    });

});