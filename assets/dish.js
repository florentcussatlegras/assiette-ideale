import $ from 'jquery'

$(function(){

  $('.add-another-collection-step-recipe').click(function (e) {

      var list = $($(this).attr('data-list-selector'));
      // Try to find the counter of the list or use the length of the list
      var counter = list.data('widget-counter') || list.children().length;

      // grab the prototype template
      var newWidget = list.attr('data-prototype');
      // replace the "__name__" used in the id and name of the prototype
      // with a number that's unique to your emails
      // end name attribute looks like name="contact[emails][2]"
      newWidget = newWidget.replace(/__name__/g, counter);
      // Increase the counter
      counter++;
      // And store it, the length cannot be used if deleting widgets is allowed
      list.data('widget-counter', counter);

      // create a new list element and add it to the list
      var newElem = $(list.attr('data-widget-tags')).html(newWidget);
      newElem.appendTo(list);

      //newElem.append('<button type="button" class="flex items-center mb-2 remove-collection-step-recipe" style="float:right"><i data-feather="trash-2" class="w-4 h-4"></i>Supprimer</button>');
      newElem.append('<div class="flex items-center mb-4 text-sm"><a href="#" class="text-a remove-collection-step-recipe"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash-2 w-4 h-4 mr-1"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>Supprimer</a></div>');
  });

  $('#step-recipe-fields-list').on('click', '.remove-collection-step-recipe', function (e) {

      e.preventDefault();
      $(this).parent().parent('li').remove();

  });
  

  $('.select-all').bind('click', function(){

    if($(this).is(':checked') == true){
      $('.select-food').prop('checked', 'checked');
    }else{
      $('.select-food').prop('checked', false);
    }

  });

  setValueFormSessionAjax = function()
  {
    name = $("#dish_name").val();
    lengthPerson = $("#dish_lengthPersonForRecipe").val();
    preparationTime = $("#dish_preparationTime").val();
    preparationTimeTypeOfTime = $("#dish_preparationTimeTypeOfTime").val();
    cookingTime = $("#dish_cookingTime").val();
    cookingTimeTypeOfTime = $("#dish_cookingTimeTypeOfTime").val();

    var spices = [];
    $("input[name='dish[spices][]']").each(function(){
      if($(this).is(":checked")){
        spices.push($(this).val());
      }
    });

    var stepRecipes = [];
    $("#step-recipe-fields-list").contents('li').each(function(){
      rank = $(this).find('.rank-step-recipe').val();
      description = $(this).find('.description-step-recipe').val();
      stepRecipes.push({'rank' : rank, 'description' : description});
    });

    params = {
              'name' : name, 
      'lengthPerson' : lengthPerson, 
    'preparationTime' : preparationTime,
'preparationTimeTypeOfTime' : preparationTimeTypeOfTime,
        'cookingTime' : cookingTime,
'cookingTimeTypeOfTime' : cookingTimeTypeOfTime,
            'spices' : spices, 
        'stepRecipes' : stepRecipes
    };

    url = Routing.generate('dish_form_set_in_session', params);
    console.log(url);

    $.ajax({
      url : url,
      type : 'GET',
      dataType : 'json',
      success : function(code_html, statut){
        console.log(statut);
      },
      error : function(resultat, statut, erreur){
        console.log(statut + ':' + erreur);
      }
    });    
  }

  getListFood = function(foodGroupId, foodGroupSlug, dishId, foodId, rankf)
  {
    setValueFormSessionAjax();

    $url = Routing.generate('food_list', {'foodGroupId': foodGroupId, 'foodGroupSlug' : foodGroupSlug, 'dishId' : dishId, 'foodToEdit' : foodId, 'rankf' : rankf});
    console.log($url);
    window.location.href = $url;
  }

  $(".fg-link > .picto").click(function(e){
  
    e.preventDefault();

    getListFood($(this).data('foodgroupid'), $(this).data('foodgroupslug'), $(this).data('dishid'), null, null);

  });

  $(".edit-dish-food").click(function(e){

    e.preventDefault();
    
    getListFood($(this).data('foodgroupid'), $(this).data('foodgroupslug'), $(this).data('dishid'), $(this).data('foodid'), $(this).data('rankf'));

  });

  $(".show-quantity").click(function(e){
    
    e.preventDefault();

    showEditQuantityFields($(this));

  });


  $('.remove-dish-food').click(function(e){

    e.preventDefault();

    if(confirm('Etes-vous sûr de vouloir supprimer cet aliment?'))
    {
      setValueFormSessionAjax();

      $id = '' != $(this).data("id") ? $(this).data("id") : null;
      $dishId = '' != $(this).data("dishid") ? $(this).data("dishid") : null;

      $url = Routing.generate('food_choice_remove', {'id': $id, 'dishId': $dishId, 'um' : $(this).data("unitmeasureid"), 'rankf': $(this).data("rankfood")});

      window.location.href = $url;
    }

  });

  $('#remove-selected-food').bind('click', function(){

  
      foodSelected = false;
      listIds = '';

      $( '.select-food' ).each(function( index ) {
        
        if($(this).is(':checked'))
        {
          foodSelected = true;
          listIds = $(this).data("foodid") + 'rank' + $(this).data("rankfood") + '-' + listIds;
        }

      });

      // alert(foodSelected);

      if(foodSelected == true)
      {

        setValueFormSessionAjax();
        
        $dishId = '' != $(this).data("dishid") ? $(this).data("dishid") : null;

        // alert(listIds);

        url = Routing.generate('food_choice_remove_selected', {'listIds': listIds, 'dishId': $dishId});

        // alert(url);

        window.location.href = url;

      }

      return false;

  });
  
  //EDIT QUANTITY FOOD AJAX

  //Affiche le champ select avec la quantité actuelle et la liste des unités de mesure

  function showEditQuantityFields($this)
  {
    $url = Routing.generate('food_show_quantity_fields_ajax', {'dishId' : $this.data('dishid'), 'foodId' : $this.data('foodid'), 'qty' : $this.data('quantity'), 'unitMeasureId' : $this.data('unitmeasureid'), 'rankf': $this.data('rankfood')});

    $.ajax({
        url : $url,
        type : 'GET',
        dataType : 'html',
        beforeSend :function(xhr) {
        $this.hide().siblings('.loading').show();
        },
        success : function(code_html, statut){
        $this.siblings('.edit-quantity').show().html(code_html);
        $this.siblings('.loading').hide();
        },
        error : function(resultat, statut, erreur){
        console.log(statut + ':' + erreur);
        }
    });                  
  }

  //VALIDATION

  $(document).on('click', '.choice-food', function( event ){

      event.preventDefault();

      foodId = $(this).data('foodid');
      var qty = $('.quantity-'+foodId).val();

      if (qty == ''){

        alert('Veuillez indiquer une quantité !');

      }else{

        if($(this).hasClass('fromform'))
        {
          setValueFormSessionAjax();
          $measureUnit = $(this).siblings('.measureUnit').val();
        }else{
          $measureUnit = $(this).parent().siblings('.measureUnit').val();
        }

        var $url = Routing.generate('food_choice_add', {'foodId': foodId, 'dishId': $(this).data('dishid'), 'fromLibrary' : true, 'qty': qty, 'measureUnit': $measureUnit, 'rankf' : $(this).data('rankf'), 'foodToEdit' : $(this).data('foodtoedit')});
        console.log($url);
        window.location.href = $url;

      }

  });

  $('#container-result-search-food-in-all-group').on('click', '.select-quantity-food', function (e) {

    e.preventDefault();

    $qty = $(this).siblings('.quantity-food').val();

    if ($qty == ''){

      alert('Veuillez indiquer une quantité !');

    }else if($.isNumeric($qty) == false || $qty <= 0){

      alert('Veuillez indiquer une quantité valide!');

    }else{  

      setValueFormSessionAjax();

      var $url = Routing.generate('food_choice_add', {'foodId': $(this).data('foodid'), 'dishId' : $(this).data("dishid"), 'qty': $qty, 'measureUnit': $(this).siblings('.unit-measure-symbol-food').val()});
      console.log($url);
      window.location.href = $url;

    }
    
  });

  maxsize = 0;

  $('.info-food').each(function(){
      height = $(this).height();
      if (height > maxsize){
          maxsize = height;
      }
  });
  $('.info-food').each(function(){
      $(this).height(maxsize);
  });

  //ANNULATION
  $(document).on('click', '.cancel-edit-food', function(e){

    e.preventDefault();
    $(this).parent().parent().hide();
    // $(this).parent().parent().parent().siblings('.show-quantity').show();
    $(this).parent().parent().siblings('.show-quantity').show();
  });

  $('#container-result-search-food-in-all-group').on('click', '.close-food-list', function (e) {
    $('#container-result-search-food-in-all-group').fadeOut();
  });

  //SEARCH AJAX

  function delay(callback, ms) {
    var timer = 0;
    return function() {
      var context = this, args = arguments;
      clearTimeout(timer);
      timer = setTimeout(function () {
        callback.apply(context, args);
      }, ms || 0);
    };
  }

  //Dans tous les groupes

  $('#input-search-food-in-all-group').bind('keyup', delay(function(){

      $this = $(this);

      if($this.val() != ''){

        $('.icon-x-search-food').css('visibility', 'visible');
        $('.icon-search-food').hide();
        $('.loading-search-food').show();
          
        $url = Routing.generate('food_choice_search_ajax', {'keyword' : $this.val(), 'dishId' : $this.data('dishid')});
        
        console.log($url);

        jQuery.ajax({
          url : $url,
          type : 'GET',
          dataType: 'html',
          success : function(html, statut){

            $('.icon-search-food').show();
            $('.loading-search-food').hide();
            $this.parent().parent().siblings('#container-result-search-food-in-all-group').show().html(html);

          },
          error : function(resultat, statut, erreur){
            console.log('erreur chargement liste aliment');
          }

        });

      }else{

        $('.icon-x-search-food').css('visibility', 'hidden');
        $('#container-result-search-food-in-all-group').hide();

      }

    }, 500)
  );

  //Groupe par groupe

  $('.search-food').bind('click', function(e){
    e.preventDefault();
    $(this).siblings('.container-input-search-food').toggle('fade');
  });


  $('.input-search-food').bind('keyup', delay(function(){

      $this = $(this);

      if($this.val() != ''){
          
        $url = Routing.generate('food_choice_ajax', {'foodGroupCode' : $this.data('group'), 'keyword' : $this.val(), 'dishId' : $this.data('dishid')});
        
        console.log($url);

        jQuery.ajax({
          url : $url,
          type : 'GET',
          dataType: 'html',
          success : function(code_html, statut){

            console.log(code_html);
            $this.siblings('.container-result-search-food').show().html(code_html);

          },
          error : function(resultat, statut, erreur){
            console.log(resultat);
          }

        });

      }else{

        $('.container-result-search-food').hide();

      }

    }, 500)
  );

  $('.icon-x-search-food').bind('click', function(){
    $('#input-search-food-in-all-group').val('');
    $('#container-result-search-food-in-all-group').hide();
    $(this).css('visibility', 'hidden');
  });

  $(document).keyup(function(e) {
    if (e.keyCode === 27) $('.icon-x-search-food').trigger('click');   // esc
  });

  $('#container-result-search-food-in-all-group').bind('click', function(e){
    e.stopPropagation();
  });

  $(document).click(function(){  
    $('.icon-x-search-food').trigger('click');
  });

  var lengthPersonInput = $("#dish_lengthPersonForRecipe");
  
  $('.add-person').bind('click', function(){

      newValue = parseInt(lengthPersonInput.val()) + 1;

      lengthPersonInput.val(newValue);

  });
  
  $('.substract-person').bind('click', function(){

      oldValue = lengthPersonInput.val();

      if(oldValue > 1)
      {
        newValue = oldValue - 1;
      }

      lengthPersonInput.val(newValue);

  });

  $('.pic-download').bind('click', function(e){

    e.preventDefault();

    console.log($('.pic-dish'));
    
    if($('.pic-dish').val() !== '')
    {   

      setValueFormSessionAjax();

      $url = Routing.generate('picture_dish_download', {'id': $(this).data('dishid')});
      
      $('#formAddDish').attr('action', $url).submit();

    }else{
      
      alert('Veuillez choisir une nouvelle image !');
    
    }

  });

  $('.remove-pic').bind('click', function(e){

    e.preventDefault();

    if(confirm('Etes-vous sûr de vouloir supprimer cette image?'))
    {
      setValueFormSessionAjax();

      $url = Routing.generate('picture_dish_remove', {'id': $(this).data("dishid")});

      window.location.href = $url;
    }

  });


});

