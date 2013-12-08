/**
 * This script is for /fund/assign action
 * Most of the event are related for assigning of hedgefund to portfolio
 */
jQuery(document).ready(function(){
	
	/**
	 * Search the name of the hedgefund using ajax
	 */
	jQuery("#search_name").on("click", function(e){
		var name = jQuery("#inputName").val();
		jQuery.get("/hedgefunds/search/?q="+name, function(response){
			response = jQuery.parseJSON(response);
			var output = "";
			jQuery.each(response, function(i, item){
				output += "<option value='"+item.id+"'>"+item.fund_name+"</option>";
			})
			jQuery("#fund_id_create").html(output);
		})
	})
	
	jQuery("#search_name_update").on("click", function(e){
		var name = jQuery("#inputUpdateName").val();
		jQuery.get("/hedgefunds/search/?q="+name, function(response){
			response = jQuery.parseJSON(response);
			var output = "";
			jQuery.each(response, function(i, item){
				output += "<option value='"+item.id+"'>"+item.fund_name+"</option>";
			})
			jQuery("#fund_id_update").html(output);
		})
	});
	/**
	 * Shows the add new fund form
	 */
	jQuery("#add_new_fund").on("click", function(e){
		jQuery('#myModal').modal({
		  keyboard: false
		})
		
		jQuery("#myModal input[type=text], #myModal select").val("");
		e.preventDefault();
	})
	/**
	 * Submit data to /fund/add to create new hedgefund
	 */
	jQuery("#myModal form").on("submit", function(){
		var data = jQuery(this).serialize();
		jQuery.post("/fund/add/", data, function(response){
			response = jQuery.parseJSON(response);
			if (response.success){
				alert("The fund has been successfully added.")
				window.location.reload();
			}
		});
		return false;
	});
	/**
	 * Submit data to /fund/update to update hedgefund
	 */
	jQuery("#myUpdateModal form").on("submit", function(){
		var data = jQuery(this).serialize();
		jQuery.post("/fund/update/", data, function(response){
			response = jQuery.parseJSON(response);
			if (response.success){
				alert("This fund has been successfully update.")
				window.location.reload();
			}
		});
		return false;
	});
	
})
/**
 * Load hedgefund into the form from ajax service
 */
jQuery(document).on("click", ".update_fund", function(){
	var id = jQuery(this).attr("data-id");
	jQuery("#myUpdateModal input, #myUpdateModal select")
	jQuery.get("/fund/get-info/?id="+id, function(response){
		response = jQuery.parseJSON(response);
		if (response.success){
			jQuery("#inputUpdateId").val(response.fund.id);
			jQuery("#inputUpdatePortfolioId").val(response.fund.portfolio_id);
			jQuery("#inputUpdateName").val(response.fund.name);
			var option = "<option value='"+response.fund.fund_id+"'>"+response.fund.name+"</option>";
			jQuery("#fund_id_update").html(option);
			jQuery("#myUpdateModal input[name=number_of_fund]").each(function(){
				if (jQuery(this).val()==parseInt(response.fund.number_of_fund)){
					jQuery(this).attr("checked", "checked");
				}
			});
			jQuery("#selectUpdateSubsetId").val(response.fund.subset_id);
			jQuery("#selectUpdateCategoryId").val(response.fund.category_id);
			jQuery("#inputUpdateWeightVariable").val(response.fund.weight_variable);
			jQuery("#inputUpdateWeightFixed").val(response.fund.weight_fixed);
			jQuery('#myUpdateModal').modal({
			  keyboard: false
			})
		}
	});
});
/**
 * Event to delete a fund
 */
jQuery(document).on("click", ".delete_fund", function(){
	var me = jQuery(this);
	var ans = confirm("Do you want to delete this fund?");
	if (ans){
		var id = jQuery(this).attr("data-id");
		jQuery.get("/fund/delete/?id="+id, function(response){
			alert("Fund has been successfully been deleted")
			me.parent().parent().fadeOut(100, function(){
				jQuery(this).remove();
			})
		})
	}
});


