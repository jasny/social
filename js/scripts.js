(function($) {
	// Isotope
	var items = $('#portfolio-items');
	items.isotope({
		 itemSelector : '.portfolio-item'	 
	});
	
	// Isotope filtering
	$('#portfolio-filters a').click(function(){
	  var selector = $(this).attr('data-filter');
	  items.isotope({ filter: selector });
	  return false;
	});
	
	// Fancybox
	$('.fb').fancybox();
	
	// Form Labels
	$.fn.formLabels();

        // Make code pretty
        prettyPrint()
    
	// Responsive menu
	$("<select />").appendTo("#navigation");
	
	// Create default option "Go to..."
	$("<option />", {
	"selected": "selected",
	"value": "",
	"text": "Go to..."
	}).appendTo("#navigation select");
	
	// Populate dropdown with menu items
	$(".main-nav a").not('#portfolio-filters a').each(function () {
	var el = $(this);
	$("<option />", {
	  "class": el.attr("class"),
	  "value": el.attr("href"),
	  "text": el.text()
	}).appendTo("nav select");
	});
	
	$("nav select").change(function(e) {
		var select 	= e.target;
 		var option 	= select.options[select.selectedIndex];
 		var trigger = $(option).attr('class');		
		var target = $('h4#'+trigger);
        {
            var top = target.offset().top;
            $('html,body').animate({scrollTop: top}, 1000);
            return false;
        }
	});	
	
	// Smooth Scroll
	$('.main-nav a[href^=#]').click(function() {
		$target = $(this).attr('href');
		$.smoothScroll({
			scrollTarget: $target + ' h4',
			 speed: 1000
		});
		return false;
	});
	
	// Scroll back to top
	$('#btop').click(function(){
		$.smoothScroll({
			scrollTarget: '.logo',
			 speed: 1000
		});
		return false;
	});
	
	// Conditional display	
	$(window).scroll(function () {
		if ($(this).scrollTop() > 400) {
			$('#btop').fadeIn('slow');
		} else {
			$('#btop').fadeOut('slow');
		}
	});

})( jQuery );
