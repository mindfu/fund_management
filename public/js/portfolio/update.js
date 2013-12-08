
/**
 * This script is for /portfolio/update action
 * Most of the event are related to portfolio management
 */

var db =  window.openDatabase("leverage_ratios", "", "Leverage Ratios", 1024*1000);

jQuery(document).ready(function(){
	
	
	db.transaction(function(tx){
		  tx.executeSql('CREATE TABLE IF NOT EXISTS leverage_ratios(id INTEGER PRIMARY KEY, debt FLOAT, equity FLOAT)', []);
	})
	
	jQuery("#leverage_ratio_id").on("change", function(){
		
	})
	
	/**
	 * Updates portfolio information in db
	 */
	jQuery("#setupForm").on("submit", function(){
		jQuery.post("/portfolio/save", jQuery(this).serialize(), function(response){
			response = jQuery.parseJSON(response);
			if (response.success){
				window.location.href = "/fund/assign?id="+response.id
			}else{
				alert("Something went wrong");
			}
		});
		return false;
	});
})
