import $ from 'jquery';
import Swal from 'sweetalert2';

$(function(){

    // var selectTypeMeal = function()
    // {
    //     $('.type').each(function(){
    //         if($(this).val() == $(this).data('type') && $(this).data('disabled') == "0")
    //         {
    //             var rank = $(this).attr('data-rank-meal');
    //             $(this).attr('checked', true);
    //             $('#modalAddModelMeal-' + rank).find('.type').val($(this).val());
    //             $('#typeModelMeal').val($(this).val());
    //         }else{
    //             $(this).attr('checked', false);
    //         }
    //     });
    // }

    // selectTypeMeal();

    var delay = (function(){
        var timer = 0;
        return function(callback, ms){
            clearTimeout (timer);
            timer = setTimeout(callback, ms);
        };
    })();

    var duplicateFilter=(function(){
        var lastContent;
        return function(content,callback){
        content=$.trim(content);
        if(content!=lastContent){
            callback(content);
        }
        lastContent=content;
        };
    })();

    var showMoreButton = '.load-more';
    var showMoreContent = jQuery(showMoreButton).html();
    // var showMoreLoading = '<span id="loading"><span>&bull;</span><span>&bull;</span><span>&bull;</span></span>';
    var showMoreLoading = `<center id="loading" class="py-2"><svg xmlns="http://www.w3.org/2000/svg" width="40" viewBox="0 0 120 30" fill="#fff"><circle cx="15" cy="15" r="15"><animate attributeName="r" from="15" to="15" begin="0s" dur="0.8s" values="15;9;15" calcMode="linear" repeatCount="indefinite"/><animate attributeName="fill-opacity" from="1" to="1" begin="0s" dur="0.8s" values="1;.5;1" calcMode="linear" repeatCount="indefinite"/></circle><circle cx="60" cy="15" r="9" fill-opacity="0.3">
                                    <animate attributeName="r" from="9" to="9" begin="0s" dur="0.8s" values="9;15;9" calcMode="linear" repeatCount="indefinite"/>
                                    <animate attributeName="fill-opacity" from="0.5" to="0.5" begin="0s" dur="0.8s" values=".5;1;.5" calcMode="linear" repeatCount="indefinite"/>
                                </circle>
                                <circle cx="105" cy="15" r="15">
                                    <animate attributeName="r" from="15" to="15" begin="0s" dur="0.8s" values="15;9;15" calcMode="linear" repeatCount="indefinite"/>
                                    <animate attributeName="fill-opacity" from="1" to="1" begin="0s" dur="0.8s" values="1;.5;1" calcMode="linear" repeatCount="indefinite"/>
                                </circle>
                            </svg>
                        </center>`;

    function callAjaxSearchDishes(obj, page, loadMore = false) {
        
        console.log('ajax call list dishes');
        //let meal = obj.closest('.meal');
        // var rankMeal = meal.data('rank-meal');
        let rankMeal = obj.data('rank-meal');
        let rankDish = obj.data('rank-dish');
        let containerList = $('#container-list-' + rankMeal + '-' + rankDish);
        let inputSearch = containerList.find('.search');
        inputSearch.data('rank-meal', rankMeal);
        inputSearch.data('rank-dish', rankDish);
        let keyword = inputSearch.val();
        let color = obj.attr('data-color');

        if (typeof color !== typeof undefined && color !== false) {
            
            // The user check foodgroup icon
            let check = containerList.find(".checkbox_" + rankMeal + "_" + obj.attr('data-fgp-id'));

            if($(check).is(':checked') == true)
            {
                $(check).prop("checked", false);
                obj.attr('style', 'background-color:' + color);
                obj.siblings('.tick').hide();
            }else{
                console.log('on check');
                // $(check).prop("checked", true);
                $(check).attr('checked', 'checked');
                obj.attr('style', 'border: 2px solid #56c5a0; background-color:' + color);
                obj.siblings('.tick').show();
            }
        }

        var fgp = [];
        containerList.find('.select-fgp').each(function(index){
              if($(this).is(':checked'))
              {
                console.log($(this).val() + ' est coché');
                fgp.push($(this).val());
              }
        });

        let url = Routing.generate('meal_day_list_ajax', {'q' : keyword, 'fgp' : fgp, 'rankMeal' : rankMeal, 'page' : page, 'rankDish' : rankDish});

        $.ajax({
          url : url,
          beforeSend: function( xhr ) {
            containerList.find('.container-load-more').show();
            // containerList.find('.loading-spinner').show();
            containerList.find('.lds-ring').show();

            if(loadMore == false) {
                containerList.find('.list').empty();
                // containerList.find('.loading').show();
                containerList.find('.no-result').hide();
                $('.load-more').hide();
            }else{
                $('.load-more').html(showMoreLoading);
            }
          }
        }).done(function( data) {
       
            if(data.response == "no-results"){
                containerList.find('.container-load-more').show();
                // containerList.find('.loading-spinner').hide();
                containerList.find('.loader').hide();
                containerList.find('.no-result').show();
            }else{
                containerList.find('.container-load-more').hide();
                containerList.find('.no-result').hide();
                // containerList.find('.loading-spinner').hide();
                containerList.find('.loader').show();
                containerList.find('.load-more').show().attr('data-page', page);

                if(loadMore == false) {
                    containerList.find('.list').fadeIn().html(data);
                }else{
                    containerList.find('.load-more-gif').hide();
                    containerList.find('.list').append(data);
                }

                $('.load-more').html(showMoreContent);
                if (containerList.find('.last-block').last().val() == 'true')
                {
                    containerList.find('.container-load-more').hide();
                }
            }

        });

    }

    // AFFICHER LA LISTE DES PLATS
    // $(document).on('click', '.show-list', function(e){
    //     e.preventDefault();
    //     // $(this).closest('.meal').attr('data-rank-dish', $(this).data('rank-dish'));
    //     callAjaxSearchDishes($(this), 0);
    // });

    // MODIFICATION D'UN PLAT
     $(document).on('click', '.update-dish', function(e){
        e.preventDefault();

        $(this).closest('.meal').find('.select-fgp').each(function(){
            $(this).attr('checked', false);
        });
        $(this).closest('.meal').find('.btn-foodgroup-parent-slide').each(function(){
            $(this).attr('style', 'background-color: #fff; border: 1px solid ' + $(this).attr('data-color') + '; color:' + $(this).attr('data-color'));
        });
        // $(this).closest('.meal').attr('data-rank-dish', $(this).data('rank-dish'));
        callAjaxSearchDishes($(this), 0);
    });

    // CHARGEMENT PLATS DANS LE SLIDE
    // $(document).on('click', '.load-more', function(e){
    //     var page = parseInt($(this).attr('data-page')) + 1;
    //     callAjaxSearchDishes($(this), page, true);
    // });

    // RECHERCHE D'UN PLAT DANS LE SLIDE SELON MOT-CLE
    // $(document).on('keyup', '.search', function(e){
    //     e.preventDefault();
    //     var obj = this;
    //     delay(function(){
    //         duplicateFilter($(obj).val(),callAjaxSearchDishes($(obj), 0));
    //     }, 500 );
    // });

    // RECHERCHE D'UN PLAT DANS LE SLIDE SELON LE GROUPE
    $(document).on('click', '.btn-foodgroup-parent-slide', function(e){
        e.preventDefault();
       
        callAjaxSearchDishes($(this), 0);	
    });

    // AJOUT D'UN REPAS
    // $('.add-meal').bind('click', function(e){
        
    //     e.preventDefault();

    //     let types = $('#meal-' + $(this).data('rank-meal')).find('.type');
    //     let typeChecked = false;

    //     types.each(function(index, type){
    //         if($(this).is(':checked') == true) {
    //             typeChecked = true;
    //         }
    //     });

    //     if(typeChecked == false) {
    //         alert('Merci de saisir le type du dernier repas');
    //     }else{
    //         let url = Routing.generate('meal_day_add');
    //         window.location.href = url;
    //         $.ajax({
    //           url : url,
    //           beforeSend: function( xhr ) {
    //           }
    //         }).done(function( data) {
    //             $('.container-meal').append(data).find('.meal').last().hide().fadeIn(300);
    //             //selectTypeMeal();
    //         });
    //     }
    // });


    // FERMETURE DE LA MODALE DES VALEURS NUTRI ET LEUR AFFICHAGE

    var loadNutritionalValues = function() {
        if($('#dish_nutritionalTable_protein').val() !== '') {
            $('#protein-value').text($('#dish_nutritionalTable_protein').val() + ' g');
        }

        if($('#dish_nutritionalTable_lipid').val() !== '') {
            $('#lipid-value').text($('#dish_nutritionalTable_lipid').val() + ' g');
        }

        if($('#dish_nutritionalTable_saturatedFattyAcid').val() !== '') {
            $('#saturated-fatty-acid-value').text($('#dish_nutritionalTable_saturatedFattyAcid').val() + ' g');
        }

        if($('#dish_nutritionalTable_carbohydrate').val() !== '') {
            $('#carbohydrate-value').text($('#dish_nutritionalTable_carbohydrate').val() + ' g');
        }

        if($('#dish_nutritionalTable_sugar').val() != '') {
            $('#sugar-value').text($('#dish_nutritionalTable_sugar').val() + ' g');
        }

        if($('#dish_nutritionalTable_salt').val() != '') {
            $('#salt-value').text($('#dish_nutritionalTable_salt').val() + ' g');
        }

        if($('#dish_nutritionalTable_fiber').val() != '') {
            $('#fiber-value').text($('#dish_nutritionalTable_fiber').val() + ' g');
        }

        if($('#dish_nutritionalTable_energy').val() != '') {
            $('#energy-value').text($('#dish_nutritionalTable_energy').val() + ' Kcal');
        }

        if($('#dish_nutritionalTable_nutriscore').val() != '') {
            $('#nutriscore-value').text($('#dish_nutritionalTable_nutriscore').val());
        }
    }

    loadNutritionalValues();

    $('.close-modal-nutritional-values').bind('click', function(e){
        e.preventDefault();
        
        loadNutritionalValues();
    });


    // AJOUT D'UN PLAT DEPUIS LE CHAMPS DE RECHERCHE

    var redirectToSaveInSession = function(feature, element, datas = null) {
        // var id = $('#dish_id').val()
        // var name = $('#dish_name').val();
        // var lengthPerson = $('#dish_lengthPersonForRecipe').val();
        // var preparationTime = $('#dish_preparationTime').val();
        // var preparationTimeUnitTime = $('#dish_preparationTimeUnitTime').val();
        // var cookingTime = $('#dish_cookingTime').val();
        // var cookingTimeUnitTime = $('#dish_cookingTimeUnitTime').val();

        // var dish = {
        //     "id": $('#dish_id').val(),
        //     "name": $('#dish_name').val(),
        //     "length_person": $('#dish_lengthPersonForRecipe').val(),
        //     "preparation_time": $('#dish_preparationTime').val(),
        //     "preparation_time_unit_time": $('#dish_preparationTimeUnitTime').val(),
        //     "cooking_time": $('#dish_cookingTime').val(),
        //     "cooking_time_unit_time": $('#dish_cookingTimeUnitTime').val()
        // }
        // console.log($('#form_dish').serialize());


        // console.log(dish.serialize());
        //$("input[name='dish[stepRecipes]'"));

        // if(feature == 'remove_pic') {
        //     if (window.confirm("Confirmez-vous vouloir supprimer cette image?")) {
        //         var url = Routing.generate('app_save_dish_in_session', 
        //             {
        //                 'dish_serialized': $('#form_dish').serialize(),
        //                 'remove_pic': true
        //             }
        //         );
        // }

        // if(feature == 'add_food' || feature == 'modify_food') {
        //     var newFoodSerialized = element.parent('form').serialize();
        //     var url = Routing.generate('app_save_dish_in_session', 
        //         {
        //             'dish_serialized': $('#form_dish').serialize(),
        //             'new_food_serialized': newFoodSerialized
        //         }
        //     );
        // }

        // if(feature == 'goto_food') {
        //     var slugFoodGroupToGo = element.children('button').attr('class');
        //     var url = Routing.generate('app_save_dish_in_session', 
        //         {
        //             'dish_serialized': $('#form_dish').serialize(),
        //             'slug_foodgroup_togo': slugFoodGroupToGo
        //         }
        //     );
        // }
        var url = null;

        switch (feature) {
            case 'remove_pic':
                if (window.confirm("Confirmez-vous vouloir supprimer cette image?")) {
                    url = Routing.generate('app_save_dish_in_session', 
                        {
                            'dish_serialized': $('#form_dish').serialize(),
                            'remove_pic': true
                        }
                    );
                }
                window.location.href = url;
                return true;

            case 'goto_food':

                var idFoodGroupToGo = element.children('button').attr('data-food-group-id');
                url = Routing.generate('app_save_dish_in_session', 
                    {
                        'dish_serialized': $('#form_dish').serialize(),
                        'id_foodgroup_togo': idFoodGroupToGo
                    }
                );
                window.location.href = url;
                return true;

            case 'delete_food':

                Swal.fire({
                    title: 'Confirmation',
                    text: 'Etes-vous sûr de vouloir supprimer cet aliment?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Oui',
                    // showLoaderOnConfirm: true,
                }).then((result) => {
                    if(result.isConfirmed) {
          
                        var foodGroupAlias = element.attr('data-foodgroup-alias');
                        var idFood = element.attr('data-foodid');
                        url = Routing.generate('app_save_dish_in_session', 
                            {
                                'dish_serialized': $('#form_dish').serialize(),
                                'delete_food': true,
                                'foodgroup_alias': foodGroupAlias,
                                'id_food': idFood
                            }
                        );

                        window.location.href = url;
                        return true;

                    } else if (result.isDenied) {
                        return false;
                    }
                });

                // if (window.confirm("Confirmez-vous vouloir supprimer cet aliment?")) {
                //     var foodGroupAlias = element.attr('data-foodgroup-alias');
                //     var idFood = element.attr('data-foodid');
                //     var url = Routing.generate('app_save_dish_in_session', 
                //         {
                //             'dish_serialized': $('#form_dish').serialize(),
                //             'delete_food': true,
                //             'foodgroup_alias': foodGroupAlias,
                //             'id_food': idFood
                //         }
                //     );
                //     console.log(url);
                // }
                break;
            default:
                // var foodId = element.attr('data-food-id');
                // const token = element.sibling('.token');
                // const idDish = element.sibling('.id-dish');
                // const foodGroupAlias = element.sibling('.food-group-alias');
                // const foodGroupSlug = element.sibling('.food-group-slug');
                // const foodQuantity = element.sibling('.food-quantity');
                // const foodUnitMeasure = element.sibling('.food-unit-measure');

                // var form = new FormData();
                // console.log(foodId);
                // console.log(foodGroupAlias);



                // console.log($('#formFood-' + foodId).serialize());
                url = Routing.generate('app_save_dish_in_session', 
                    {
                        'dish_serialized': $('#form_dish').serialize(),
                        'new_food_serialized': element.parent('form').serialize()
                    }
                );
                
                window.location.href = url;
                return true;
        }
    }

    $(document).on('click', '.add-food', function(e){
        e.preventDefault();
    
        // console.log($(this).parent('form').serialize());
        // var $newFoodSerialized = $(this).parent('form').serialize();
        // alert($newFoodSerialized);

        redirectToSaveInSession('add_food', $(this));
    });

    $(document).on('click', '.modify-food', function(e){
        e.preventDefault();

        redirectToSaveInSession('modify_food', $(this));
    });

    $(document).on('click', '.delete-food', function(e){
        e.preventDefault();

        redirectToSaveInSession('delete_food', $(this));
    });

    $(document).on('click', '.goto-food', function(e){
        e.preventDefault();

        redirectToSaveInSession('goto_food', $(this));
    });

    $(document).on('click', '.remove-pic', function(e){
        e.preventDefault();

        redirectToSaveInSession('remove_pic', $(this));
    });

    // SUPPRESSION D'UN PLAT
    // $(document).on('click', '.remove-dish', function(e){
    //     e.preventDefault();
    //     let url = Routing.generate('meal_day_remove_dish', {'rankMeal' : $(this).data('rank-meal'), 'rankDish' : $(this).data('rank-dish')});
    //     console.log(url);
    //     window.location.href = url;
    // });

    // MODIFICATION TYPE D'UN PLAT ET ENREGISTREMENT EN SESSION
    // $(document).on('change', '.type', function(e){
    //     e.preventDefault();
    //     $(this).attr('checked', true);
    //     let url = Routing.generate('meal_day_update_type_meal', {'rankMeal' : $(this).data('rank-meal'), 'type' : $(this).val()});
    //     //$('#modalAddModelMeal' + $(this).data('rank-meal')).find('.type', $(this).val());
    //     window.location.href = url;
    //     // $.ajax({
    //     //   url : url,
    //     //   beforeSend: function( xhr ) {

    //     //   }
    //     // }).done(function( data ) {
    //     // 	console.log('OK');
    //     // });
    // });

    // SUPPRESSION D'UN REPAS
    // $(document).on('click', '.remove-meal', function(e){
    //     e.preventDefault();
    //     var url = Routing.generate('meal_day_remove', {'rankMeal' : $(this).data('rank-meal')});
    //     console.log(url);
    //     window.location.href = url;
    // });

    // AJOUTER UN REPAS MODELE
    $(document).on('click', '.add-model-meal', function(e){

        e.preventDefault();

        let rankMeal = $(this).data('rank-meal');
        let src = $(this).data('src');
        let name = $('#name-model-meal-' + rankMeal).val();

        $('.type').each(function(){
            if($(this).is(':checked'))
            {
                type = $(this).val();
            }
        });
        var url = Routing.generate('model_meal_add', {'rankMeal' : rankMeal, 'name' : name, 'type' : type});

        $.ajax({
            url : url,
            beforeSend: function( xhr ) {

            }
        }).done(function( data ) {
            console.log(data);
            if(src == 'list-model-meal')
            {
                window.location.href = Routing.generate('model_meal_list');
            }
	            });
	      
    });

    // AJOUTER REPAS
    // $(document).on('click', '.save-meals', function(e){
        
    //     e.preventDefault();

    //     let types = $('#meal-' + $(this).data('rank-meal')).find('.type');
    //     let typeChecked = false;

    //     types.each(function(index, type){
    //         if($(this).is(':checked') == true) {
    //             typeChecked = true;
    //         }
    //     });

    //     if(typeChecked == false) {
    //         // alert('Veuillez indiquer un type pour chaque repas');
    //         Swal.fire({
    //             title: 'Erreur',
    //             text: "Veuillez indiquer un type pour chaque repas!",
    //             icon: 'warning',
    //             showCancelButton: true,
    //             confirmButtonColor: '#3085d6',
    //             cancelButtonColor: '#d33'
    //         });

    //     }else{

    //         Swal.fire({
    //             title: 'Are you sûr?',
    //             text: "Vous avez des plats déconseillés dans vos repas!",
    //             icon: 'warning',
    //             showCancelButton: true,
    //             confirmButtonColor: '#3085d6',
    //             cancelButtonColor: '#d33'
    //         }).then((result) => {
    //             if (result.isConfirmed) {
    //                 const url = Routing.generate('menu_add');
    //                 window.location.href = url;
    //             }
    //         });

    //     }
    // });

    $(document).on('click', '.show-details-alerts', function(){
        $(this).hide().siblings('.hide-details-alerts').show();
        $(this).parent().siblings('.details-alerts').fadeIn();
    });

    $(document).on('click', '.hide-details-alerts', function(){
        $(this).hide().siblings('.show-details-alerts').show();
        $(this).parent().siblings('.details-alerts').fadeOut();
    });

    $(document).on('input', '.n-portion', function(){

        console.log('change portion');

        let nPortion = ($(this).val() === "Aucune") ? 0 : parseInt($(this).val(), 10);

        // On met à jour l'energie total après la séelection de l'aliment/plat
        // Container qui affiche l'énergie totale
        const $containerEnergyTotalMobile= $("#sidebarTotalEnergyMobile");
        const $containerEnergyTotalDesktop = $("#sidebarTotalEnergyDesktop");
        const urlEnergyTotal = Routing.generate('meal_day_energy_estimate_with_new_selection', {
            'typeAddItem': 'dish',
             'id' : $(this).data('dish-id'), 
             'nPortion' : nPortion, 
             'rankDish' : $(this).data('rank-dish'), 
             'rankMeal' : $(this).data('rank-meal')}
        );

        // open(urlEnergyTotal, '_blank');

        $.ajax({
            url : urlEnergyTotal,
            beforeSend: function( xhr ) {
                // Ajout d’un spinner pendant le chargement
                $containerEnergyTotalMobile.html(
                    '<div class="dot-loader-energy">' +
                        '<span></span><span></span><span></span>' +
                    '</div>' 
                );
                $containerEnergyTotalDesktop.html(
                    '<div class="dot-loader-energy">' +
                        '<span></span><span></span><span></span>' +
                    '</div>'
                );
            }
        }).done(function( data ) {
            $containerEnergyTotalMobile.empty().html(data);
            $containerEnergyTotalDesktop.empty().html(data);
        });

        // var containerAlerts = $(this).parent().parent().parent().siblings('.alerts');
        const containerAlerts = $("#alerts-Dish-" + $(this).data('dish-id'));

        const url = Routing.generate('meal_day_update_alert_on_dish_on_update_portion', {'id' : $(this).data('dish-id'), 'nPortion' : nPortion, 'rankDish' : $(this).data('rank-dish'), 'rankMeal' : $(this).data('rank-meal')});
        console.log('url foo');
        console.log(url);

        // Ajouter le spinner AVANT l'appel fetch
        containerAlerts.html(
            '<span class="rounded-full flex items-center justify-center border-green-500" style="width: 40px; height: 40px">' +
                '<div class="dot-loader">' +
                    '<span></span><span></span><span></span>' +
                '</div>' +
            '</span>'
        );

        // Appel AJAX via fetch
        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur serveur ' + response.status);
                }
                return response.text(); // car tu reçois du HTML
            })
            .then(data => {
                containerAlerts.empty().html(data);
            })
            .catch(error => {
                console.error(error);
                containerAlerts.html('<span class="text-red-500">Erreur…</span>');
            });


        // $.ajax({
        //     url : url,
        //     beforeSend: function( xhr ) {
        //         // Ajout d’un spinner pendant le chargement
        //         containerAlerts.html(
        //             '<span class="rounded-full flex items-center justify-center bg-green-500" style="width: 40px; height: 40px">' +
        //                 '<div class="dot-loader">' +
        //                     '<span></span><span></span><span></span>' +
        //                 '</div>' +
        //             '</span>'
        //         );
        //     }
        // }).done(function( data ) {
        //     containerAlerts.empty().html(data);
        // });


    });

    $(document).on('input', '.quantity-food', function(){

        console.log('change quantity food 1');

        // if($(this).val() != '' && $(this).val() > 0)
        // {
            let newValue = this.value;

            let quantity = newValue === '' || newValue === 'Aucune'
                ? 0 
                : parseFloat(newValue);

            var unitMeasure = $(this).siblings('.unit-measure').val();

            var obj = this;
            delay(function(){
                duplicateFilter($(obj).val(), updateAlertFoods($(obj), quantity, unitMeasure));
            }, 500 );
        // }
    });

    $(document).on('keyup', '.quantity-food', function(){

        console.log('change quantity food 2');

        // if($(this).val() != '' && $(this).val() > 0)
        // {
            var quantity = 0
            if($(this).val() == '') {
                quantity = 0;
            }else{
                quantity = parseFloat($(this).val());
            }
            var unitMeasure = $(this).siblings('.unit-measure').val();

            var obj = this;
            delay(function(){
                duplicateFilter($(obj).val(), updateAlertFoods($(obj), quantity, unitMeasure));
            }, 500 );
        // }
    });

    $(document).on('change', '.unit-measure', function(){
        var quantity = parseInt($(this).siblings('.quantity-food').val());

        if(quantity != '' && quantity > 0)
        {
            var unitMeasure = $(this).val();
            var obj = this;
            delay(function(){
                duplicateFilter($(obj).val(), updateAlertFoods($(obj), quantity, unitMeasure));
            }, 500 );
        }
    });

    var updateAlertFoods = function($this, quantity, unitMeasure)
    {
        console.log('update alert food');

        var $containerEnergyTotalMobile= $("#sidebarTotalEnergyMobile");
        var $containerEnergyTotalDesktop = $("#sidebarTotalEnergyDesktop");
        var urlEnergyTotal = Routing.generate('meal_day_energy_estimate_with_new_selection', {
            'typeAddItem': 'food',
             'id': $this.data('food-id'),
             'quantity' : quantity, 
             'unitMeasure': unitMeasure,
             'rankMeal' : $this.data('rank-meal'),
             'rankDish' : $this.data('rank-dish')
            },
        );

        console.log(urlEnergyTotal);

        $.ajax({
            url : urlEnergyTotal,
            beforeSend: function( xhr ) {
                // Ajout d’un spinner pendant le chargement
                $containerEnergyTotalMobile.html(
                    '<div class="dot-loader-energy">' +
                        '<span></span><span></span><span></span>' +
                    '</div>' 
                );
                $containerEnergyTotalDesktop.html(
                    '<div class="dot-loader-energy">' +
                        '<span></span><span></span><span></span>' +
                    '</div>'
                );
            }
        }).done(function( data ) {
            // console.log(data);
            $containerEnergyTotalMobile.empty().html(data);
            $containerEnergyTotalDesktop.empty().html(data);
        });

        console.log('je modifie les alertes');

        var $containerAlerts = $('#alerts-Food-' + $this.data('food-id'));
        var url = Routing.generate('meal_day_update_alert_on_food_on_update_quantity', {'id' : $this.data('food-id'), 'quantity' : quantity, 'unitMeasure' : unitMeasure, 'rankDish' : $this.data('rank-dish'), 'rankMeal' : $this.data('rank-meal')});

        console.log(url);
        $.ajax({
            url : url,
            beforeSend: function( xhr ) {
                // Ajout d’un spinner pendant le chargement
                $containerAlerts.html(
                    '<span class="rounded-full flex items-center justify-center border border-gray-500" style="width: 40px; height: 40px">' +
                        '<div class="dot-loader">' +
                            '<span></span><span></span><span></span>' +
                        '</div>' +
                    '</span>'
                );
            }
        }).done(function( data ) {
            $containerAlerts.empty().html(data);
        });
    }

    // MODALE LISTE MODELE REPAS
    $(document).on('click', '.expend-down', function(e){
        e.preventDefault();
        $(this).hide().siblings('.expend-up').show();
        $('#list-model-meal-' + $(this).data('rank')).fadeIn(300);
    });

    $(document).on('click', '.expend-up', function(e){
        e.preventDefault();
        $(this).hide().siblings('.expend-down').show();
        $('#list-model-meal-' + $(this).data('rank')).fadeOut(300);
    });

    // $(document).on('click', '.advice', function(){

    //     let detailsAdviceModal = $(this).parent().parent().parent().siblings();
    //     console.log(detailsAdviceModal.siblings());
    //     // console.log($(this).data('title'));
    //     // console.log($(this).data('content-alreadynotrecommended'));
    //     // console.log($(this).data('content-notalreadynotrecommended'));
    //     detailsAdviceModal.children().children().children().children('#titleAdvice').html($(this).data('title'));
    //     detailsAdviceModal.children().children().children().children('#detailsAdviceAlreadyNotRecommended').html($(this).data('content-alreadynotrecommended'));
    //     detailsAdviceModal.children().children().children().children('#detailsAdviceNotAlreadyNotRecommended').html($(this).data('content-notalreadynotrecommended'));

    // });

});