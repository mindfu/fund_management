/**
 * This script is for /hedgefund/setup action
 * Most of the event are related to hedgefund management
 */

function redirect_to_hedgefunds(){
	window.location.href = "/hedgefunds/";
}

jQuery(document).ready(function(){
	/**
	 * Validate form
	 */
	jQuery("#hedgefund_setup_form").validate({
		errorPlacement:function(error, element){
	
			element.parent().addClass("has-error");
			error.appendTo(element.parent())				

		},
		success:function(label, element){
			label.parent().removeClass("has-error");
		},
		submitHandler:function(){
			//send data for saving into db
			var data = jQuery("#hedgefund_setup_form").serialize();
			jQuery.post("/hedgefunds/create/", data, function(response){
				response = jQuery.parseJSON(response);
				var html = "";
				if (response.success){
					html = '<div class="alert alert-success alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>Fund has been saved!</div>';
					jQuery("#save_result").html(html)		
					redirect_to_hedgefunds();
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
