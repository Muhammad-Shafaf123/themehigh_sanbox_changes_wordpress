(function( $ ) {
	'use strict';

	// Wwhen the DOM is ready:
	$(function() {
		// Get the modal
		var modal = $("#th-wpsb-modal");
		var close = modal.find('.close');

		$( '#new-wpsb' ).on( 'click', function(ev) {
			ev.preventDefault();
			$(modal).fadeIn();
	        $.ajax({
	            url: thwpsb_args.ajaxurl,
	            data: {
	               	action : 'thwpsb_create_sandbox',
	                context: 'frontend',
					_wpnonce: thwpsb_args.nonce,
	            },
	            success: function( resp ){
					$(modal).find('.preparing').fadeOut();
					$(modal).find('.ready').fadeIn();
	                // on success redirect to admin sandbox
	                if( resp ){
						//set_document_cookie(resp);

	                    window.top.location.href = resp;
	                    return;
	                }

	            },
	            error: function(){
					$(modal).fadeOut();
	            }
	        })

		})

		$( close ).on( 'click', function(ev) {
			ev.preventDefault();
			$(modal).fadeOut();
		})

		$( '.thwpsb-countdown-wrapper .close' ).on( 'click', function(ev) {
			ev.preventDefault();
			$( '.thwpsb-countdown-wrapper' ).fadeOut();
		});


	});

	function set_document_cookie(){
		console.log('set_document_cookie');
		var date = new Date();
	    var minutes = thwpsb_args.sb_lifetime;
	    date.setTime(date.getTime() + (minutes * 60 * 1000));
		console.log(date);
	    // document.cookie("thwpsb_path", resp, { expires: date });
		document.cookie = "username=John pp";
	}

	//When the window is loaded:
	$( window ).load(function() {

		if(document.getElementById('thwpsb-countdown') != null){
			var target = document.getElementById('thwpsb-countdown');
			var countDown = target.innerHTML;
			var timer = setInterval(function () {
			  if(countDown === 1) {
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
