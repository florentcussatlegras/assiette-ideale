import $ from 'jquery';
import Swal from 'sweetalert2';

$(function(){

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
        console.log(url);

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

    // MODIFICATION D'UN PLAT
     $(document).on('click', '.update-dish', function(e){
        e.preventDefault();

        $(this).closest('.meal').find('.select-fgp').each(function(){
            $(this).attr('checked', false);
        });
        $(this).closest('.meal').find('.btn-foodgroup-parent-slide').each(function(){
            $(this).attr('style', 'background-color: #fff; border: 1px solid ' + $(this).attr('data-color') + '; color:' + $(this).attr('data-color'));
        });

        callAjaxSearchDishes($(this), 0);
    });

    // RECHERCHE D'UN PLAT DANS LE SLIDE SELON LE GROUPE
    $(document).on('click', '.btn-foodgroup-parent-slide', function(e){
        e.preventDefault();
       
        callAjaxSearchDishes($(this), 0);	
    });

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

                break;
            default:
            
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

    $(document).on('input', '.n-portion', function(){

        $('.wrapper-dishOrFood').removeClass('border-2 border-sky-600');
        $('.dish-or-food-name').removeClass('text-sky-600 font-semibold');

        const wrapper = $(this).closest('.wrapper-dishOrFood');
        const name = wrapper.find('.dish-or-food-name');

        if ($(this).val() && $(this).val() !== "Aucune") {
            wrapper.addClass('border-2 border-sky-600');
            name.addClass('text-sky-600 font-semibold');
        } else {
            wrapper.removeClass('border-2 border-sky-600');
            name.removeClass('text-sky-600 font-semibold');
        }

        let nPortion = ($(this).val() === "Aucune") ? 1 : parseInt($(this).val(), 10);

        // On met à jour l'energie total après la séelection de l'aliment/plat
        // Container qui affiche l'énergie totale
        const $containerEnergyTotalMobile= $("#sidebarTotalEnergyMobile");
        const $containerEnergyTotalDesktop = $("#sidebarTotalEnergyDesktop");
        const urlEnergyTotal = Routing.generate('meal_day_energy_estimate_with_new_selection', {
            'typeAddItem' : 'dish',
             'id' : $(this).data('dish-id'), 
             'nPortion' : nPortion, 
             'rankDish' : $(this).data('rank-dish'), 
             'rankMeal' : $(this).data('rank-meal')}
        );

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

        const containerAlerts = $("#alerts-Dish-" + $(this).data('dish-id'));

        const url = Routing.generate('meal_day_update_alert_on_dish_on_update_portion', {'id' : $(this).data('dish-id'), 'nPortion' : nPortion, 'rankDish' : $(this).data('rank-dish'), 'rankMeal' : $(this).data('rank-meal')});

        // Ajouter le spinner AVANT l'appel fetch
        containerAlerts.html(
            '<span class="rounded-full flex items-center justify-center border-green-500" style="width: 40px; height: 40px">' +
                '<div class="dot-loader">' +
                    '<span></span><span></span><span></span>' +
                '</div>' +
            '</span>'
        );

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
                return response.text();
            })
            .then(data => {
                containerAlerts.empty().html(data);
            })
            .catch(error => {
                console.error(error);
                containerAlerts.html('<span class="text-red-500">Erreur…</span>');
            });

    });

    $(document).on('input', '.quantity-food', function () {

        const $input = $(this);
        const $wrapper = $input.closest('.wrapper-dishOrFood');
        const $name = $wrapper.find('.dish-or-food-name');

        const value = $input.val();
        const quantity = (!value || value === 'Aucune') ? 0 : parseFloat(value);
        const unitMeasure = $input.siblings('.unit-measure').val();

        // Reset des autres éléments
        $('.wrapper-dishOrFood')
            .not($wrapper)
            .removeClass('border-2 border-sky-600');

        $('.dish-or-food-name')
            .not($name)
            .removeClass('text-sky-600 font-semibold');

        // Toggle style élément actif
        const hasValue = value && value !== '';

        $wrapper.toggleClass('border-2 border-sky-600', hasValue);
        $name.toggleClass('text-sky-600 font-semibold', hasValue);

        // Delay propre
        delay(() => {
            const result = updateAlertFoods($input, quantity, unitMeasure);
            duplicateFilter(value, result);
        }, 500);

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

        var $containerAlerts = $('#alerts-Food-' + $this.data('food-id'));
        var url = Routing.generate('meal_day_update_alert_on_food_on_update_quantity', {'id' : $this.data('food-id'), 'quantity' : quantity, 'unitMeasure' : unitMeasure, 'rankDish' : $this.data('rank-dish'), 'rankMeal' : $this.data('rank-meal')});

        $.ajax({
            url : url,
            beforeSend: function( xhr ) {
                // Ajout d’un spinner pendant le chargement
                $containerAlerts.html(
                    '<span class="rounded-full flex items-center justify-center" style="width: 40px; height: 40px">' +
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

    // // MODALE LISTE MODELE REPAS
    // $(document).on('click', '.expend-down', function(e){
    //     e.preventDefault();
    //     $(this).hide().siblings('.expend-up').show();
    //     $('#list-model-meal-' + $(this).data('rank')).fadeIn(300);
    // });

    // $(document).on('click', '.expend-up', function(e){
    //     e.preventDefault();
    //     $(this).hide().siblings('.expend-down').show();
    //     $('#list-model-meal-' + $(this).data('rank')).fadeOut(300);
    // });

});