jQuery(document).ready( function($) {

   jQuery("#c_country_id").on('change', function(e) {
      e.preventDefault(); 
      var country_id = $(this).val();

      jQuery.ajax({
         type : "post",
         url : myAjax.ajaxurl,
         data : {action: "get_states", country_id : country_id},
         success: function(response) {
            jQuery("#c_state_id").html(response);
         }
      })   

   })

});