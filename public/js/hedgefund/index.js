/**
 * Scripts used in /hedgefund/ action
 */
jQuery(document).ready(function(){
	jQuery(".delete_fund").on("click", function(e){
		var me = jQuery(this);
		var ans = confirm("Do you want to delete this fund?");
		if (ans){
			jQuery.get(me.attr("href"), function(response){
				response = jQuery.parseJSON(response);
				if (response.success){
					me.parent().parent().remove();
					alert("Fund has been successfully been deleted.");
				}
			})
		}
		e.preventDefault();
	})
	
});
