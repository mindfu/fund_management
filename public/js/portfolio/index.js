/**
 * Scripts used in /portfolio/ action
 * Mostly related in listing portfolio and deleting portfolio
 */
var page = 1;
var rows = 100;


/**
 * Function responsible formatting the ajax service
 */
function renderResponse(response){
	var output = "";
	jQuery.each(response.portfolios, function(i, item){
		output += "<tr>";
			output+="<td>"+(i+1)+"</td>";
			output+="<td>"+item.name+"</td>";
			output+="<td>"+item.date_updated+"</td>";
			
			var buttons = '<a href="/portfolio/calculate?id='+item.id+'" class="btn btn-xs btn-primary">View</a>&nbsp;<a href="/portfolio/update?id='+item.id+'" class="btn btn-xs btn-primary">Edit</a>&nbsp;<a href="/portfolio/delete?id='+item.id+'" class="delete_portfolio btn btn-xs btn-danger">Delete</a>';
			output+="<td>"+buttons+"</td>";
			
		output += "</tr>";
	});
	
	jQuery("#portfolio_list tbody").html(output);
	
	response.page = parseInt(response.page);
	var start = ((response.page-1)*rows)+1;
	var end = start + rows;
	if (response.count < end){
		end = response.count;
	}
	
	jQuery(".start_count").html(start);
	jQuery(".end_count").html(end);
	jQuery(".total_count").html(response.count);
	
	
	//render pagination
	var pagination = "";
	var i = 0;
	
	pagination += "<li><a href='#' class='prev'>&laquo;</a></li>";
	
	for(i=0;i<response.total_page;i++){
		if (i==response.page){
			pagination += "<li class='active'><a href='#' class='page_link' data-page='"+(i+1)+"'>"+(i+1)+"</a></li>";		
		}else{
			pagination += "<li><a href='#' class='page_link' data-page='"+(i+1)+"'>"+(i+1)+"</a></li>";	
			
		}
	}
	
	pagination += "<li><a href='#' class='nextv'>&raquo;</a></li>";
	
	jQuery(".pagination").html(pagination);
}

jQuery(document).ready(function(){
	jQuery("#add_new_portfolio").on("click", function(e){
		window.location.href = "/portfolio/setup";
	})
	
	
	jQuery.get("/portfolio/list", function(response){
		response = jQuery.parseJSON(response);
		renderResponse(response);
	});
});

/**
 * Deletes a portfolio in the db
 */
jQuery(document).on("click", ".delete_portfolio", function(e){
	var ans = confirm("Do you want to delete this portfolio?");
	if (ans){
		var me  = jQuery(this);
		jQuery.get(me.attr("href"), function(response){
			me.parent().parent().remove();
			alert("The portfolio has been deleted");
		})
	}
	e.preventDefault();
})

/**
 * Transfer to another page
 */
jQuery(document).on("click", ".page_link", function(e){
	jQuery.get("/portfolio/list?page="+jQuery(this).attr("data-page"), function(response){
		response = jQuery.parseJSON(response);
		renderResponse(response);
	});
	e.preventDefault();
});
