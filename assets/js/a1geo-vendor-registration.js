/**
 * a1geo-vendor-registration.js
 *
 * Hide the radio button and simulate clicking radio
 */
( function () {
	// Wait for DOM to be ready.
    window.addEventListener('load', (event) => {          
        setTimeout(function() {
            jQuery(".dokan-role-seller").click();    
        },200);
    });	
} )();


//const websiteInput = document.getElementById("seller-url");
//
//if (websiteInput !== null) {
//    // Listen for the 'invalid' event to show the custom message
//    websiteInput.addEventListener("invalid", function(event) {
//        event.preventDefault(); // Prevent the default message
//        websiteInput.setCustomValidity("Please provide a full and valid website address!"); // Set the custom message
//    });
//    console.log("Check website");
//    // Listen for the 'input' event to clear the custom message as the user types
//    websiteInput.addEventListener("input", function(event) {
//        websiteInput.setCustomValidity(""); // Reset the message
//    });  
//}

