(function( $ ) {
	'use strict';

	// Wwhen the DOM is ready:
	$(function() {
		$( '.thwpsb-countdown-wrapper .close' ).on( 'click', function(ev) {
			ev.preventDefault();
			$( '.thwpsb-countdown-wrapper' ).fadeOut();
		});
	});



	$(function() {
	$("#thwpsb-filter-form").submit(function(e) {

	    e.preventDefault(); // avoid to execute the actual submit of the form.

	    var form = $(this);

		var form_data = jQuery( this ).serializeArray();

		if(!form_data){
			return;
		}

    	$.ajax({
           type: "POST",
           url: ajaxurl,
           data: form_data,
           success: function(data)
           {
			   var chart_data = JSON.parse(data.data);
			   show_chart(chart_data);
           }
         });
	});
	});

	// $(function() {

		function show_chart(filtered_data){
			console.log(filtered_data);
			var ctx = document.getElementById("myChart").getContext("2d");
			var myChart = new Chart(ctx, {
				responsive:true,
			  type: 'line',
			  data: {
					datasets: filtered_data,
			  },
			  options: {
			    scales: {
			      xAxes: [{
			        type: 'time',
					unit: 'day',
					unitStepSize: 1,
				}],
				yAxes: [{
				  ticks: {
					beginAtZero: true,
					callback: function(value) {if (value % 1 === 0) {return value;}}
				  }
				}]
			    }
			  }
			});
		}
	// });


	//When the window is loaded:
	$( window ).load(function() {

		// $(document).ready(function(){
		     //$("#thwpsb-filter-form").submit();
		// });

		if(document.getElementById('thwpsb-countdown') != null){
			var target = document.getElementById('thwpsb-countdown');
			var countDown = target.innerHTML;
			var timer = setInterval(function () {
			  if(countDown <= 1) {
			    clearInterval(timer);
			  }
			  countDown--;
			  var hour=Math.floor(countDown/3600);
			  var min=Math.floor(countDown%3600/60);
			  var sec=Math.floor(countDown%3600%60);
			  target.textContent = (hour=hour<10?"0"+hour:hour) + " : " + (min=min<10?"0"+min:min) + " : " + (sec=sec<10?"0"+sec:sec);
			}, 1000);
		}

	});

})( jQuery );
function sandbox_field_add_item(){
	document.getElementById('thfaqf_new_faq_form').style.display = 'block';
}



jQuery(document).ready(function($) {
//  wp.codeEditor.initialize($('#fancy_textarea_admin_header'), cm_settings);
//	wp.codeEditor.initialize($('#fancy_textarea_admin_footer'), cm_settings);
//	wp.codeEditor.initialize($('#fancy_textarea_frontend_header'), cm_settings);
//	wp.codeEditor.initialize($('#fancy_textarea_frontend_footer'), cm_settings);
})
