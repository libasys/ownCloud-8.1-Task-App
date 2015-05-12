
$(document).ready(function(){
	
	$('#body-public').addClass('appbody-aufgaben');
	$('#body-public').removeClass('appbody-gallery');
	
	$("#showLocation").tooltip({
				items : "img, [data-geo], [title]",
				position : {
					my : "left+15 center",
					at : "right center"
				},
				content : function() {
					var element = $(this);
					if (element.is("[data-geo]")) {
						var text = element.text();
						return "<img class='map' alt='" + text + "' src='http://maps.google.com/maps/api/staticmap?" + "zoom=14&size=350x350&maptype=terrain&sensor=false&center=" + text + "'>";
					}
					if (element.is("[title]")) {
						return element.attr("title");
					}
					if (element.is("img")) {
						return element.attr("alt");
					}
				}
			});
	
});