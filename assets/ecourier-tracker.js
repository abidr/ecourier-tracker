jQuery(function() {

	// Get the form.
	var form = jQuery('#ecourier-parcel-insert-submit');

	// Get the messages div.
	var formMessages = jQuery('#ecourier-message');

	// Set up an event listener for the contact form.
	jQuery(form).submit(function(e) {
		// Stop the browser from submitting the form.
		e.preventDefault();

		// Serialize the form data.
		var formData = jQuery(form).serialize();

		// Submit the form using AJAX.
		jQuery.ajax({
			type: 'POST',
			url: jQuery(form).attr('action'),
			data: formData
		})
		.done(function(response) {
			// Make sure that the formMessages div has the 'success' class.
			jQuery(formMessages).removeClass('error');
			jQuery(formMessages).addClass('success');

			// Set the message text.
			jQuery(formMessages).text(response);

			// Hide Submit Button
			jQuery('.ecourier-submit-btn').fadeOut();


		})
		.fail(function(data) {
			// Make sure that the formMessages div has the 'error' class.
			jQuery(formMessages).removeClass('success');
			jQuery(formMessages).addClass('error');

			// Set the message text.
			if (data.responseText !== '') {
				jQuery(formMessages).text(data.responseText);
			} else {
				jQuery(formMessages).text('Oops! An error occured and your message could not be sent.');
			}
		});

	});

});