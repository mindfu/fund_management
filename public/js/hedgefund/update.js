
/**
 * This script is for /hedgefund/update action
 * Most of the event are related to hedgefund management
 */

function redirect_to_hedgefunds(){
	window.location.href = "/hedgefunds/";
}

jQuery(document).ready(function(){
	jQuery(".monthly_performance").on("blur", function(){
		var monthly_performance = jQuery(this).val();
		if (jQuery.trim(monthly_performance)!=""){
			var data = {
				year:jQuery(this).attr("data-year"),
				month:jQuery(this).attr("data-month"),
				fund_id:jQuery(this).attr("data-fund_id"),
				value:jQuery.trim(jQuery(this).val())
			};
			
			jQuery.post("/hedgefunds/update-performance/", data, function(response){
				
			});
		}
	});
	
	jQuery("#hedgefund_update_form").validate({
		errorPlacement:function(error, element){
	
			element.parent().addClass("has-error");
			error.appendTo(element.parent())				

		},
		success:function(label, element){
			label.parent().removeClass("has-error");
		},
		submitHandler:function(){
			var data = jQuery("#hedgefund_update_form").serialize();
			jQuery.post("/hedgefunds/save/", data, function(response){
				response = jQuery.parseJSON(response);
				var html = "";
				if (response.success){
					html = '<div class="alert alert-success alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>Fund has been saved!</div>';
					jQuery("#save_result").html(html)
					setTimeout(redirect_to_hedgefunds, 2000);
				}else{
					html = '<div class="alert alert-danger alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>Something went wrong. Please check the required fields.</div>';
					jQuery("#save_result").html(html)
				}
			});
			return false;
		},
		rules:{
			fund_name:"required",
			general_partner_fname:"required",
			general_partner_lname:"required",
			general_partner_title:"required",
			street_1:"required",
			city:"required",
			state:"required",
			country_id:"required",
			continent_id:"required",
			phone:"required",
			fax:"required",
			email:{
				required:true,
				email:true
			},
			contact_person_fname:"required",
			contact_person_lname:"required",
			contact_person_title:"required",
			firm_assets:"required",
			fund_assets:"required",
			minimum_investment:"required",
			management_fee:"required",
			incentive_fee:"required",
			early_redemption_fee:"required"
		}
	});
});
