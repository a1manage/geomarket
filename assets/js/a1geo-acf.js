/**
 * a1geo-acf.js
 *
 * Hide/Unhide ACF fields group based on selected product category
 */
( function () {
    jQuery( function () {
        display_category_fields();

        jQuery( document ).on( 'change', '.dokan_chosen_product_cat', function () {
            display_category_fields();
        } );

        if ( window.wp && wp.hooks && wp.hooks.addAction ) {
            wp.hooks.addAction( 'dokan_selected_multistep_category', 'dokan-multistep-category', function ( selectedCategory ) {
                display_category_fields( selectedCategory );
            } );
        }

        if ( window.MutationObserver ) {
            var observer = new MutationObserver( function ( mutations ) {
                var acfFieldsAdded = mutations.some( function ( mutation ) {
                    return Array.prototype.some.call( mutation.addedNodes, function ( node ) {
                        return node.nodeType === 1 && ( node.matches( '.acf-form-fields' ) || node.querySelector( '.acf-form-fields' ) );
                    } );
                } );

                if ( acfFieldsAdded ) {
                    display_category_fields();
                }
            } );
            observer.observe( document.body, { childList: true, subtree: true } );
        }
    } );
} )();

function display_category_fields(selected_cat) {    
	jQuery( ".acf-fields.acf-form-fields .acf-field" ).hide(); // hide all custom fields
    jQuery( ".acf-field-67eba1074833d" ).show(); //show Datasheet link field
    jQuery( ".acf-field-67f07860a5e24" ).show(); //show Original Product Link field   
    var categoryIds = Array.isArray( selected_cat ) ? selected_cat : [ selected_cat ];
    if ( typeof selected_cat === 'undefined' || selected_cat === null ) {
        categoryIds = jQuery( '.dokan_chosen_product_cat' ).map( function () {
            return jQuery( this ).val();
        } ).get();
    }
    categoryIds = categoryIds.filter( function ( categoryId ) {
        return undefined !== categoryId && null !== categoryId && '' !== categoryId && '0' !== categoryId;
    } );
    if ( ! categoryIds.length ) {
        return;
    }

    categoryIds.forEach( function ( categoryId ) {
    switch ( categoryId.toString() )
    {
        case "25": 
            enable_instrument_resistivity();// instrument resistivity
            break;
        case "27": 
            enable_instrument_seismic(); // instrument sesimic
            break;
        case "28": 
            enable_instrument_tem(); // instrument tem
            break;
        case "30": 
            enable_instrument_drilling(); // instrument drilling devices
            break;
        case "29": 
            enable_instrument_gravity(); // instrument gravity
            break;
        case "31": 
            enable_instrument_magnetic(); // instrument magnetic
            break;
        case "32": 
            enable_instrument_radioactivity(); // instrument radioactivity
            break;
        case "33": 
            enable_instrument_borehole(); // instrument borehole geophysical
            break;
        case "57": 
            enable_instrument_oem(); // instrument EM
            break;
        case "58":             
            enable_instrument_airborne(); // instrument airborne geophysical            
            break;
        case "59":
            enable_ground_penetrating(); // ground penetrating radar
            break;
        case "60":
            enable_other_geophysical(); // Other Geophysical instrument
            break;        
        case "35":
            enable_service_resistivity_survey(); // Resistivity survey
            break;
        case "36":
            enable_service_seismic_survey(); // Seismic survey
            break;
        case "37":
            enable_service_em_survey(); // EM survey
            break;
        case "38":
            enable_service_gravity_survey(); // Gravity survey
            break;    
        case "39":
            enable_service_drilling(); // Drilling service
            break;      
        case "40":
            enable_service_magnetic_survey(); // Magnetic survey
            break; 
        case "41":
            enable_service_radio_survey(); // Radioactivity survey
            break;  
        case "42":
            enable_service_borehole_survey(); // Borehole geophysics survey
            break; 
        case "62":
            enable_service_airbone_survey(); // Airbone geophysics survey
            break;
        case "63":
            enable_service_ground_penetrating_survey(); // Ground penetrating survey
            break; 
        case "64":
            enable_service_other_survey(); // Other geophysical survey
            break;  
        case "73":
            enable_service_tem_survey(); // TEM survey
            break;  
        case "66":
            enable_accesory_cables();// Accessory cables
            break;  
        case "67":
            enable_accesory_geophones();// Accessory geophones
            break;      
        case "68":
            enable_accesory_tablet();// Accessory table/laptop
            break;      
        case "69":
            enable_accesory_winches();// Accessory winches
            break;  
        case "70":
            enable_accesory_reels();// Accessory reels
            break;  
        case "71":
            enable_accesory_seismic_sources();// Accessory seismic sources
            break;  
        case "72":
            enable_accesory_sensors();// Accessory sensors
            break;  
        case "74":
            enable_accesory_batteries();// Accessory batteries
            break;
        case "61":
            enable_geophysics_software_seismic(); // Geophysics software seismic
            break;    
        case "48":
            enable_secondhand_instruments();// Secondhand instruments
            break; 
        case "75":
            enable_rented_instruments();// Rented instruments
            break; 
        case "77":
            enable_instrument_general_data();// General data recording
            break;
        case "78":
            enable_service_groundwater_investigation();// Groundwater investigation            
            break;            
        case "79":
            enable_service_civil_engineering();// Civil engineering application
            break;            
        case "80":
            enable_service_mineral_exploration();// Mineral exploration services
            break;                
        case "81":
            enable_service_archaeology_geophysics();// Archaeology geophysics
            break;   
        case "82":
            enable_service_landfill_investigations();// Landfill/waste site investigations
            break;
        case "83":
            enable_service_geological_hazards();// Geological hazards assessment
            break;   
        case "84":
            enable_service_geological_structure();// Geological structure mapping
            break;   
        case "85":
            enable_service_oil_exploration();// Oil and gas exploration services
            break;   
        case "86":
            enable_service_education();// Education and training services
            break;   
        case "87":
            enable_service_environment_assessment();// Environment assessment
            break;       
        case "89":
            enable_geophysics_software_others();// Geophysics software others
            break;  
        case "175":
            enable_geophysics_software_r_ip_em_tem();
            break;
        case "90":
            enable_site_dam_monitoring();// Site/Dam monitoring
            break;  
        case "91":
            enable_application_others();// Application others
            break;
        case "92":
            enable_accessory_others(); // Other devices
            break;
        case "97":
            enable_service_data_processing(); // Service data processing
            break;            
        case "98":
            enable_service_comprehensive(); // Service comprehenshive 
            break;
        case "99":
            enable_application_geothermal(); // Application Geothermal exploration
            break;
        case "168":
            enable_positioning_survey_service(); // Positioning survey service
            break;    
        case "172":
            enable_positioning_topographic_devices();
            break;
        case "173":
            enable_data_processing_seismic();
            break;
        case "174":
            enable_data_processing_r_ip_em_tem();
            break;
            
        default: 
            break;   
    }
    } );
}

function enable_instrument_resistivity() {    
    jQuery( ".acf-field-67f0af356b33e" ).show(); //show P1 field
    jQuery( ".acf-field-67f0b07778e67" ).show(); //show P2 field
    jQuery( ".acf-field-67f0b09078e68" ).show(); //show P3 field
    jQuery( ".acf-field-67f0b0c390566" ).show(); //show P4 field
    jQuery( ".acf-field-67f0b0d366137" ).show(); //show P5 field
    jQuery( ".acf-field-67f0b110fd1bd" ).show(); //show P6 field
    jQuery( ".acf-field-67f0b17611a45" ).show(); //show P7 field
    jQuery( ".acf-field-67f0b187770cf" ).show(); //show P8 field    
}

function enable_instrument_seismic() {    
    jQuery( ".acf-field-67f9e370083fe" ).show(); //show P1 field
    jQuery( ".acf-field-67f9e93c3ae2d" ).show(); //show P2 field
    jQuery( ".acf-field-67f9e9613ae2e" ).show(); //show P3 field
    jQuery( ".acf-field-67f9e9703ae2f" ).show(); //show P4 field
    jQuery( ".acf-field-67f9e9873ae30" ).show(); //show P5 field
    jQuery( ".acf-field-67f9e9963ae31" ).show(); //show P6 field
    jQuery( ".acf-field-67f9e9a23ae32" ).show(); //show P7 field
    jQuery( ".acf-field-67f9e9ac3ae33" ).show(); //show P8 field
}

function enable_instrument_tem() {    
    jQuery( ".acf-field-68133f8052066" ).show(); //show P1 field
    jQuery( ".acf-field-681340df9df1c" ).show(); //show P2 field
    jQuery( ".acf-field-681340f39df1d" ).show(); //show P3 field
    jQuery( ".acf-field-681341069df1e" ).show(); //show P4 field
    jQuery( ".acf-field-681341586576a" ).show(); //show P5 field
    jQuery( ".acf-field-6813416f6576b" ).show(); //show P6 field
    jQuery( ".acf-field-681341926576c" ).show(); //show P7 field
    jQuery( ".acf-field-681341a66576d" ).show(); //show P8 field
}

function enable_instrument_oem() {    
    jQuery( ".acf-field-6815b28e5066a" ).show(); //show P1 field
    jQuery( ".acf-field-6815b30d5066b" ).show(); //show P2 field
    jQuery( ".acf-field-6815b31e5066c" ).show(); //show P3 field
    jQuery( ".acf-field-6815b34c5066d" ).show(); //show P4 field
    jQuery( ".acf-field-6815b36a5066e" ).show(); //show P5 field
    jQuery( ".acf-field-6815b3785066f" ).show(); //show P6 field
    jQuery( ".acf-field-6815b38850670" ).show(); //show P7 field
    jQuery( ".acf-field-6815b3a250671" ).show(); //show P8 field
}

function enable_instrument_gravity() {    
    jQuery( ".acf-field-6815c61932adf" ).show(); //show P1 field
    jQuery( ".acf-field-6815c61932b1c" ).show(); //show P2 field
    jQuery( ".acf-field-6815c61932b57" ).show(); //show P3 field
    jQuery( ".acf-field-6815c61932b93" ).show(); //show P4 field
    jQuery( ".acf-field-6815c61932bce" ).show(); //show P5 field
    jQuery( ".acf-field-6815c61932c09" ).show(); //show P6 field
    jQuery( ".acf-field-6815c61932c43" ).show(); //show P7 field
    jQuery( ".acf-field-6815c61932c7e" ).show(); //show P8 field
}

function enable_instrument_magnetic() {    
    jQuery( ".acf-field-6815d316b40be" ).show(); //show P1 field
    jQuery( ".acf-field-6815d316b4100" ).show(); //show P2 field
    jQuery( ".acf-field-6815d316b413b" ).show(); //show P3 field
    jQuery( ".acf-field-6815d316b4175" ).show(); //show P4 field
    jQuery( ".acf-field-6815d316b41b0" ).show(); //show P5 field
    jQuery( ".acf-field-6815d316b41ea" ).show(); //show P6 field
    jQuery( ".acf-field-6815d316b4225" ).show(); //show P7 field
    jQuery( ".acf-field-6815d316b425f" ).show(); //show P8 field
}

function enable_instrument_radioactivity() {    
    jQuery( ".acf-field-6819cea9061b1" ).show(); //show P1 field
    jQuery( ".acf-field-6819cee6061b2" ).show(); //show P2 field
    jQuery( ".acf-field-6819cef8061b3" ).show(); //show P3 field
    jQuery( ".acf-field-6819cf06061b4" ).show(); //show P4 field
    jQuery( ".acf-field-6819cf2b061b5" ).show(); //show P5 field
    jQuery( ".acf-field-6819cf3c061b6" ).show(); //show P6 field
    jQuery( ".acf-field-6819cf4f061b7" ).show(); //show P7 field
    jQuery( ".acf-field-6819cf75061b8" ).show(); //show P8 field
}

function enable_instrument_borehole() {    
    jQuery( ".acf-field-6819d66f226f6" ).show(); //show P1 field
    jQuery( ".acf-field-6819d6a5226f7" ).show(); //show P2 field
    jQuery( ".acf-field-6819d6af226f8" ).show(); //show P3 field    
    jQuery( ".acf-field-6819d6be226f9" ).show(); //show P4 field   
    jQuery( ".acf-field-6819d6cd226fa" ).show(); //show P5 field   
    jQuery( ".acf-field-6819d6f9226fb" ).show(); //show P6 field   
    jQuery( ".acf-field-6819d704226fc" ).show(); //show P7 field
    jQuery( ".acf-field-6819d712226fd" ).show(); //show P8 field
}

function enable_instrument_airborne() {        
    jQuery( ".acf-field-6819dec15a204" ).show(); //show P1 field
    jQuery( ".acf-field-6819dec15a245" ).show(); //show P2 field
    jQuery( ".acf-field-6819dec15a282" ).show(); //show P3 field    
    jQuery( ".acf-field-6819dec15a2be" ).show(); //show P4 field
    jQuery( ".acf-field-6819dec15a2f9" ).show(); //show P5 field
    jQuery( ".acf-field-6819dec15a333" ).show(); //show P6 field
    jQuery( ".acf-field-6819dec15a36e" ).show(); //show P7 field
    jQuery( ".acf-field-6819dec15a3a8" ).show(); //show P8 field
}

function enable_instrument_drilling() {    
    jQuery( ".acf-field-681c74cdd2aea" ).show(); //show P1 field
    jQuery( ".acf-field-681c74f2d2aeb" ).show(); //show P2 field
    jQuery( ".acf-field-681c7502d2aec" ).show(); //show P3 field    
    jQuery( ".acf-field-681c7514d2aed" ).show(); //show P4 field 
    jQuery( ".acf-field-681c7526d2aee" ).show(); //show P5 field 
    jQuery( ".acf-field-681c7535d2aef" ).show(); //show P6 field
    jQuery( ".acf-field-681c7544d2af0" ).show(); //show P7 field
    jQuery( ".acf-field-681c7554d2af1" ).show(); //show P8 field
}

function enable_ground_penetrating() {    
    jQuery( ".acf-field-684a9d4f61971" ).show(); //show P1 field
    jQuery( ".acf-field-684a9db961972" ).show(); //show P2 field
    jQuery( ".acf-field-684a9dca61973" ).show(); //show P3 field    
    jQuery( ".acf-field-684a9ddc61974" ).show(); //show P4 field
    jQuery( ".acf-field-684a9df261975" ).show(); //show P5 field
    jQuery( ".acf-field-684a9e0661976" ).show(); //show P6 field
    jQuery( ".acf-field-684a9e1261977" ).show(); //show P7 field
    jQuery( ".acf-field-684a9e1d61978" ).show(); //show P8 field
}

function enable_other_geophysical() {    
    jQuery( ".acf-field-684cd293ab8ed" ).show(); //show P1 field
    jQuery( ".acf-field-684cd2d6ab8ee" ).show(); //show P2 field
    jQuery( ".acf-field-684cd2eaab8ef" ).show(); //show P3 field    
    jQuery( ".acf-field-684cd308ab8f0" ).show(); //show P4 field
    jQuery( ".acf-field-684cd313ab8f1" ).show(); //show P5 field
    jQuery( ".acf-field-684cd322ab8f2" ).show(); //show P6 field
    jQuery( ".acf-field-684cd332ab8f3" ).show(); //show P7 field
    jQuery( ".acf-field-684cd351ab8f4" ).show(); //show P8 field
}

function enable_geophysics_software_seismic() {    
    jQuery( ".acf-field-684cf77efd392" ).show(); //show P1 field
    jQuery( ".acf-field-684cf7b0fd393" ).show(); //show P2 field
    jQuery( ".acf-field-684cf7bffd394" ).show(); //show P3 field    
    jQuery( ".acf-field-684cf7cdfd395" ).show(); //show P4 field
    jQuery( ".acf-field-684cf7dcfd396" ).show(); //show P5 field
    jQuery( ".acf-field-684cf7e7fd397" ).show(); //show P6 field
    jQuery( ".acf-field-684cf7f6fd398" ).show(); //show P7 field
    jQuery( ".acf-field-684cf801fd399" ).show(); //show P8 field
}

function enable_service_resistivity_survey() {    
    jQuery( ".acf-field-684d23d3b18ef" ).show(); //show P1 field
    jQuery( ".acf-field-684d2409b18f0" ).show(); //show P2 field
    jQuery( ".acf-field-684d2430b18f1" ).show(); //show P3 field    
    jQuery( ".acf-field-684d243fb18f2" ).show(); //show P4 field
    jQuery( ".acf-field-684d244fb18f3" ).show(); //show P5 field
    jQuery( ".acf-field-684d2464b18f4" ).show(); //show P6 field
    jQuery( ".acf-field-684d2470b18f5" ).show(); //show P7 field
    jQuery( ".acf-field-684d247ab18f6" ).show(); //show P8 field
}

function enable_service_seismic_survey() {    
    jQuery( ".acf-field-684d26f45cda9" ).show(); //show P1 field
    jQuery( ".acf-field-684d272e5cdaa" ).show(); //show P2 field
    jQuery( ".acf-field-684d27585cdab" ).show(); //show P3 field    
    jQuery( ".acf-field-684d27695cdac" ).show(); //show P4 field
    jQuery( ".acf-field-684d27805cdad" ).show(); //show P5 field
    jQuery( ".acf-field-684d27885cdae" ).show(); //show P6 field
    jQuery( ".acf-field-684d27915cdaf" ).show(); //show P7 field
    jQuery( ".acf-field-684d279c5cdb0" ).show(); //show P8 field
}

function enable_service_em_survey() {    
    jQuery( ".acf-field-685f93a3e3623" ).show(); //show P1 field
    jQuery( ".acf-field-685f93eae3624" ).show(); //show P2 field
    jQuery( ".acf-field-685f9407e3625" ).show(); //show P3 field    
    jQuery( ".acf-field-685f9419e3626" ).show(); //show P4 field
    jQuery( ".acf-field-685f9437e3627" ).show(); //show P5 field
    jQuery( ".acf-field-685f9444e3628" ).show(); //show P6 field
    jQuery( ".acf-field-685f9453e3629" ).show(); //show P7 field
    jQuery( ".acf-field-685f9464e362a" ).show(); //show P8 field
}

function enable_service_gravity_survey() {    
    jQuery( ".acf-field-685f95090bb7b" ).show(); //show P1 field
    jQuery( ".acf-field-685f952d0bb7c" ).show(); //show P2 field
    jQuery( ".acf-field-685f953c0bb7d" ).show(); //show P3 field    
    jQuery( ".acf-field-685f954d0bb7e" ).show(); //show P4 field
    jQuery( ".acf-field-685f95690bb7f" ).show(); //show P5 field
    jQuery( ".acf-field-685f95790bb80" ).show(); //show P6 field
    jQuery( ".acf-field-685f95940bb81" ).show(); //show P7 field
    jQuery( ".acf-field-685f95a40bb82" ).show(); //show P8 field
}

function enable_service_drilling() {    
    jQuery( ".acf-field-685f9994a6b06" ).show(); //show P1 field
    jQuery( ".acf-field-685f99b6a6b07" ).show(); //show P2 field
    jQuery( ".acf-field-685f99c0a6b08" ).show(); //show P3 field    
    jQuery( ".acf-field-685f99c7a6b09" ).show(); //show P4 field
    jQuery( ".acf-field-685f99daa6b0a" ).show(); //show P5 field
    jQuery( ".acf-field-685f99e6a6b0b" ).show(); //show P6 field
    jQuery( ".acf-field-685f99eea6b0c" ).show(); //show P7 field
    jQuery( ".acf-field-685f99f9a6b0d" ).show(); //show P8 field
}

function enable_service_magnetic_survey() {    
    jQuery( ".acf-field-685f9605fd41c" ).show(); //show P1 field
    jQuery( ".acf-field-685f964efd41d" ).show(); //show P2 field
    jQuery( ".acf-field-685f965ffd41e" ).show(); //show P3 field    
    jQuery( ".acf-field-685f9670fd41f" ).show(); //show P4 field
    jQuery( ".acf-field-685f968efd420" ).show(); //show P5 field
    jQuery( ".acf-field-685f969cfd421" ).show(); //show P6 field
    jQuery( ".acf-field-685f96a5fd422" ).show(); //show P7 field
    jQuery( ".acf-field-685f96b1fd423" ).show(); //show P8 field
}

function enable_service_radio_survey() {    
    jQuery( ".acf-field-685f96fd56260" ).show(); //show P1 field
    jQuery( ".acf-field-685f973056261" ).show(); //show P2 field
    jQuery( ".acf-field-685f973c56262" ).show(); //show P3 field    
    jQuery( ".acf-field-685f975256263" ).show(); //show P4 field
    jQuery( ".acf-field-685f976556264" ).show(); //show P5 field
    jQuery( ".acf-field-685f976e56265" ).show(); //show P6 field
    jQuery( ".acf-field-685f977656266" ).show(); //show P7 field
    jQuery( ".acf-field-685f978156267" ).show(); //show P8 field
}

function enable_service_borehole_survey() {    
    jQuery( ".acf-field-685f9806e00ad" ).show(); //show P1 field
    jQuery( ".acf-field-685f9830e00ae" ).show(); //show P2 field
    jQuery( ".acf-field-685f9840e00af" ).show(); //show P3 field    
    jQuery( ".acf-field-685f984ee00b0" ).show(); //show P4 field
    jQuery( ".acf-field-685f9868e00b1" ).show(); //show P5 field
    jQuery( ".acf-field-685f9873e00b2" ).show(); //show P6 field
    jQuery( ".acf-field-685f987de00b3" ).show(); //show P7 field
    jQuery( ".acf-field-685f9887e00b4" ).show(); //show P8 field
}

function enable_service_airbone_survey() {    
    jQuery( ".acf-field-685f98d868c0b" ).show(); //show P1 field
    jQuery( ".acf-field-685f98f968c0c" ).show(); //show P2 field
    jQuery( ".acf-field-685f990668c0d" ).show(); //show P3 field    
    jQuery( ".acf-field-685f991568c0e" ).show(); //show P4 field
    jQuery( ".acf-field-685f993268c0f" ).show(); //show P5 field
    jQuery( ".acf-field-685f993a68c10" ).show(); //show P6 field
    jQuery( ".acf-field-685f994368c11" ).show(); //show P7 field
    jQuery( ".acf-field-685f994e68c12" ).show(); //show P8 field
}

function enable_service_ground_penetrating_survey() {    
    jQuery( ".acf-field-685f9a5a2dd2f" ).show(); //show P1 field
    jQuery( ".acf-field-685f9a7f2dd30" ).show(); //show P2 field
    jQuery( ".acf-field-685f9a892dd31" ).show(); //show P3 field    
    jQuery( ".acf-field-685f9a982dd32" ).show(); //show P4 field
    jQuery( ".acf-field-685f9ab22dd33" ).show(); //show P5 field
    jQuery( ".acf-field-685f9ab82dd34" ).show(); //show P6 field
    jQuery( ".acf-field-685f9aca2dd35" ).show(); //show P7 field
    jQuery( ".acf-field-685f9ad52dd36" ).show(); //show P8 field
}

function enable_service_other_survey() {    
    jQuery( ".acf-field-685f9b2431930" ).show(); //show P1 field
    jQuery( ".acf-field-685f9b4531931" ).show(); //show P2 field
    jQuery( ".acf-field-685f9b5031932" ).show(); //show P3 field    
    jQuery( ".acf-field-685f9b5e31933" ).show(); //show P4 field
    jQuery( ".acf-field-685f9b7731934" ).show(); //show P5 field
    jQuery( ".acf-field-685f9b8031935" ).show(); //show P6 field
    jQuery( ".acf-field-685f9b8731936" ).show(); //show P7 field
    jQuery( ".acf-field-685f9b9031937" ).show(); //show P8 field
}

function enable_service_tem_survey() {    
    jQuery( ".acf-field-685f919ab4c35" ).show(); //show P1 field
    jQuery( ".acf-field-685f9280f38ff" ).show(); //show P2 field
    jQuery( ".acf-field-685f928ff3900" ).show(); //show P3 field    
    jQuery( ".acf-field-685f92b1f3901" ).show(); //show P4 field
    jQuery( ".acf-field-685f9314f3902" ).show(); //show P5 field
    jQuery( ".acf-field-685f9323f3903" ).show(); //show P6 field
    jQuery( ".acf-field-685f932df3904" ).show(); //show P7 field
    jQuery( ".acf-field-685f9338f3905" ).show(); //show P8 field
}

function enable_accesory_cables() {    
    jQuery( ".acf-field-686cef3c31754" ).show(); //show P1 field
    jQuery( ".acf-field-686cefb331755" ).show(); //show P2 field
    jQuery( ".acf-field-686cefd531756" ).show(); //show P3 field    
    jQuery( ".acf-field-686cefe531757" ).show(); //show P4 field
    jQuery( ".acf-field-686cefef31758" ).show(); //show P5 field
    jQuery( ".acf-field-686ceffc31759" ).show(); //show P6 field
    jQuery( ".acf-field-686cf0073175a" ).show(); //show P7 field
    jQuery( ".acf-field-686cf06deba16" ).show(); //show P8 field
}

function enable_accesory_geophones() {
    jQuery( ".acf-field-686cf0fa7e3b5" ).show(); //show P1 field
    jQuery( ".acf-field-686cf17a7e3b6" ).show(); //show P2 field
    jQuery( ".acf-field-686cf1897e3b7" ).show(); //show P3 field    
    jQuery( ".acf-field-686cf1a07e3b8" ).show(); //show P4 field
    jQuery( ".acf-field-686cf1b27e3b9" ).show(); //show P5 field
    jQuery( ".acf-field-686cf1bc7e3ba" ).show(); //show P6 field
    jQuery( ".acf-field-686cf1c87e3bb" ).show(); //show P7 field
    jQuery( ".acf-field-686cf1d47e3bc" ).show(); //show P8 field
}
function enable_accesory_tablet() {
    jQuery( ".acf-field-686cf23748e91" ).show(); //show P1 field
    jQuery( ".acf-field-686cf27c48e92" ).show(); //show P2 field
    jQuery( ".acf-field-686cf28848e93" ).show(); //show P3 field    
    jQuery( ".acf-field-686cf29348e94" ).show(); //show P4 field
    jQuery( ".acf-field-686cf29d48e95" ).show(); //show P5 field
    jQuery( ".acf-field-686cf2ad48e96" ).show(); //show P6 field
    jQuery( ".acf-field-686cf2bc48e97" ).show(); //show P7 field
    jQuery( ".acf-field-686cf2c648e98" ).show(); //show P8 field
}

function enable_accesory_winches() {
    jQuery( ".acf-field-686cf59670a17" ).show(); //show P1 field
    jQuery( ".acf-field-686cf60170a18" ).show(); //show P2 field
    jQuery( ".acf-field-686cf60b70a19" ).show(); //show P3 field    
    jQuery( ".acf-field-686cf61770a1a" ).show(); //show P4 field
    jQuery( ".acf-field-686cf62470a1b" ).show(); //show P5 field
    jQuery( ".acf-field-686cf63070a1c" ).show(); //show P6 field
    jQuery( ".acf-field-686cf63770a1d" ).show(); //show P7 field
    jQuery( ".acf-field-686cf64170a1e" ).show(); //show P8 field
}

function enable_accesory_reels() {
    jQuery( ".acf-field-686cf75f1e15d" ).show(); //show P1 field
    jQuery( ".acf-field-686cf7831e15e" ).show(); //show P2 field
    jQuery( ".acf-field-686cf79c1e15f" ).show(); //show P3 field    
    jQuery( ".acf-field-686cf7a31e160" ).show(); //show P4 field
    jQuery( ".acf-field-686cf7aa1e161" ).show(); //show P5 field
    jQuery( ".acf-field-686cf7b41e162" ).show(); //show P6 field
    jQuery( ".acf-field-686cf7bf1e163" ).show(); //show P7 field
    jQuery( ".acf-field-686cf7c71e164" ).show(); //show P8 field
}

function enable_accesory_seismic_sources() {
    jQuery( ".acf-field-686cf83f4c554" ).show(); //show P1 field
    jQuery( ".acf-field-686cf86a4c555" ).show(); //show P2 field
    jQuery( ".acf-field-686cf8754c556" ).show(); //show P3 field    
    jQuery( ".acf-field-686cf8814c557" ).show(); //show P4 field
    jQuery( ".acf-field-686cf8884c558" ).show(); //show P5 field
    jQuery( ".acf-field-686cf8914c559" ).show(); //show P6 field
    jQuery( ".acf-field-686cf89a4c55a" ).show(); //show P7 field
    jQuery( ".acf-field-686cf8a34c55b" ).show(); //show P8 field
}

function enable_accesory_sensors() {
    jQuery( ".acf-field-686cf9f56ec3e" ).show(); //show P1 field
    jQuery( ".acf-field-686cfa1c6ec3f" ).show(); //show P2 field
    jQuery( ".acf-field-686cfa296ec40" ).show(); //show P3 field    
    jQuery( ".acf-field-686cfa356ec41" ).show(); //show P4 field
    jQuery( ".acf-field-686cfa436ec42" ).show(); //show P5 field
    jQuery( ".acf-field-686cfa4a6ec43" ).show(); //show P6 field
    jQuery( ".acf-field-686cfa626ec44" ).show(); //show P7 field
    jQuery( ".acf-field-686cfa6c6ec45" ).show(); //show P8 field
}

function enable_accesory_batteries() {
    jQuery( ".acf-field-686cfaa708acb" ).show(); //show P1 field
    jQuery( ".acf-field-686cfac908acc" ).show(); //show P2 field
    jQuery( ".acf-field-686cfad408acd" ).show(); //show P3 field    
    jQuery( ".acf-field-686cfae808ace" ).show(); //show P4 field
    jQuery( ".acf-field-686cfaef08acf" ).show(); //show P5 field
    jQuery( ".acf-field-686cfaf608ad0" ).show(); //show P6 field
    jQuery( ".acf-field-686cfafe08ad1" ).show(); //show P7 field
    jQuery( ".acf-field-686cfb0808ad2" ).show(); //show P8 field
}

function enable_secondhand_instruments() {
    jQuery( ".acf-field-68720bc052855" ).show(); //show P1 field
    jQuery( ".acf-field-68720bc05289c" ).show(); //show P2 field
    jQuery( ".acf-field-68720bc0528de" ).show(); //show P3 field    
    jQuery( ".acf-field-68720bc05291f" ).show(); //show P4 field
    jQuery( ".acf-field-68720bc052962" ).show(); //show P5 field
    jQuery( ".acf-field-68720bc0529a2" ).show(); //show P6 field
    jQuery( ".acf-field-68720bc0529e4" ).show(); //show P7 field
    jQuery( ".acf-field-68720bc052a26" ).show(); //show P8 field
}

function enable_rented_instruments() {
    jQuery( ".acf-field-68720f9916034" ).show(); //show P1 field
    jQuery( ".acf-field-68720f9916074" ).show(); //show P2 field
    jQuery( ".acf-field-68720f99160b2" ).show(); //show P3 field    
    jQuery( ".acf-field-68720f99160ef" ).show(); //show P4 field
    jQuery( ".acf-field-68720f9916129" ).show(); //show P5 field
    jQuery( ".acf-field-68720f9916164" ).show(); //show P6 field
    jQuery( ".acf-field-68720f991619e" ).show(); //show P7 field
    jQuery( ".acf-field-68720f99161d8" ).show(); //show P8 field
}

function enable_instrument_general_data() {
    jQuery( ".acf-field-68b6c316f5147" ).show(); //show P1 field
    jQuery( ".acf-field-68b6c372f5148" ).show(); //show P2 field
    jQuery( ".acf-field-68b6c382f5149" ).show(); //show P3 field    
    jQuery( ".acf-field-68b6c38bf514a" ).show(); //show P4 field
    jQuery( ".acf-field-68b6c39cf514b" ).show(); //show P5 field
    jQuery( ".acf-field-68b6c3a6f514c" ).show(); //show P6 field
    jQuery( ".acf-field-68b6c3b3f514d" ).show(); //show P7 field
    jQuery( ".acf-field-68b6c3c5f514e" ).show(); //show P8 field
}

function enable_service_groundwater_investigation() {
    jQuery( ".acf-field-68b6cfad70ace" ).show(); //show P1 field
    jQuery( ".acf-field-68b6d02c70acf" ).show(); //show P2 field
    jQuery( ".acf-field-68b6d03770ad0" ).show(); //show P3 field    
    jQuery( ".acf-field-68b6d04070ad1" ).show(); //show P4 field
    jQuery( ".acf-field-68b6d04770ad2" ).show(); //show P5 field
    jQuery( ".acf-field-68b6d04f70ad3" ).show(); //show P6 field
    jQuery( ".acf-field-68b6d06070ad4" ).show(); //show P7 field
    jQuery( ".acf-field-68b6d07970ad5" ).show(); //show P8 field
}

function enable_service_civil_engineering() {
    jQuery( ".acf-field-68b6d1091f08d" ).show(); //show P1 field
    jQuery( ".acf-field-68b6d1381f08e" ).show(); //show P2 field
    jQuery( ".acf-field-68b6d1401f08f" ).show(); //show P3 field    
    jQuery( ".acf-field-68b6d1491f090" ).show(); //show P4 field
    jQuery( ".acf-field-68b6d1561f091" ).show(); //show P5 field
    jQuery( ".acf-field-68b6d1631f092" ).show(); //show P6 field
    jQuery( ".acf-field-68b6d16b1f093" ).show(); //show P7 field
    jQuery( ".acf-field-68b6d1741f094" ).show(); //show P8 field
}

function enable_service_mineral_exploration() {
    jQuery( ".acf-field-68b961a0c09bf" ).show(); //show P1 field
    jQuery( ".acf-field-68b961a0c0a0d" ).show(); //show P2 field
    jQuery( ".acf-field-68b961a0c0a4d" ).show(); //show P3 field    
    jQuery( ".acf-field-68b961a0c0a96" ).show(); //show P4 field
    jQuery( ".acf-field-68b961a0c0ad9" ).show(); //show P5 field
    jQuery( ".acf-field-68b961a0c0b1a" ).show(); //show P6 field
    jQuery( ".acf-field-68b961a0c0b5b" ).show(); //show P7 field
    jQuery( ".acf-field-68b961a0c0b9c" ).show(); //show P8 field
}

function enable_service_archaeology_geophysics() {
    jQuery( ".acf-field-68b9627b55702" ).show(); //show P1 field
    jQuery( ".acf-field-68b9627b55743" ).show(); //show P2 field
    jQuery( ".acf-field-68b9627b55784" ).show(); //show P3 field    
    jQuery( ".acf-field-68b9627b557c4" ).show(); //show P4 field
    jQuery( ".acf-field-68b9627b55800" ).show(); //show P5 field
    jQuery( ".acf-field-68b9627b5583c" ).show(); //show P6 field
    jQuery( ".acf-field-68b9627b55877" ).show(); //show P7 field
    jQuery( ".acf-field-68b9627b558b1" ).show(); //show P8 field
}

function enable_service_landfill_investigations() {
    jQuery( ".acf-field-68b963617c9ee" ).show(); //show P1 field
    jQuery( ".acf-field-68b963617ca30" ).show(); //show P2 field
    jQuery( ".acf-field-68b963617ca6e" ).show(); //show P3 field    
    jQuery( ".acf-field-68b963617caa9" ).show(); //show P4 field
    jQuery( ".acf-field-68b963617cae3" ).show(); //show P5 field
    jQuery( ".acf-field-68b963617cb1d" ).show(); //show P6 field
    jQuery( ".acf-field-68b963617cb58" ).show(); //show P7 field
    jQuery( ".acf-field-68b963617cb96" ).show(); //show P8 field
}

function enable_service_geological_hazards() {
    jQuery( ".acf-field-68b963fb5dd58" ).show(); //show P1 field
    jQuery( ".acf-field-68b963fb5dd9a" ).show(); //show P2 field
    jQuery( ".acf-field-68b963fb5ddd7" ).show(); //show P3 field    
    jQuery( ".acf-field-68b963fb5de15" ).show(); //show P4 field
    jQuery( ".acf-field-68b963fb5de52" ).show(); //show P5 field
    jQuery( ".acf-field-68b963fb5de8f" ).show(); //show P6 field
    jQuery( ".acf-field-68b963fb5decd" ).show(); //show P7 field
    jQuery( ".acf-field-68b963fb5df0b" ).show(); //show P8 field
}

function enable_service_geological_structure() {
    jQuery( ".acf-field-68b96484163bc" ).show(); //show P1 field
    jQuery( ".acf-field-68b9648416402" ).show(); //show P2 field
    jQuery( ".acf-field-68b9648416445" ).show(); //show P3 field    
    jQuery( ".acf-field-68b9648416486" ).show(); //show P4 field
    jQuery( ".acf-field-68b96484164c7" ).show(); //show P5 field
    jQuery( ".acf-field-68b9648416508" ).show(); //show P6 field
    jQuery( ".acf-field-68b9648416549" ).show(); //show P7 field
    jQuery( ".acf-field-68b9648416587" ).show(); //show P8 field
}

function enable_service_oil_exploration() {
    jQuery( ".acf-field-68b965243e739" ).show(); //show P1 field
    jQuery( ".acf-field-68b965243f6e3" ).show(); //show P2 field
    jQuery( ".acf-field-68b9652440676" ).show(); //show P3 field    
    jQuery( ".acf-field-68b9652441618" ).show(); //show P4 field
    jQuery( ".acf-field-68b96524425b8" ).show(); //show P5 field
    jQuery( ".acf-field-68b9652443587" ).show(); //show P6 field
    jQuery( ".acf-field-68b96524435de" ).show(); //show P7 field
    jQuery( ".acf-field-68b96524444f2" ).show(); //show P8 field
}

function enable_service_education() {
    jQuery( ".acf-field-68b965d08b5c0" ).show(); //show P1 field
    jQuery( ".acf-field-68b965d08b604" ).show(); //show P2 field
    jQuery( ".acf-field-68b965d08b641" ).show(); //show P3 field    
    jQuery( ".acf-field-68b965d08b67e" ).show(); //show P4 field
    jQuery( ".acf-field-68b965d08b6ba" ).show(); //show P5 field
    jQuery( ".acf-field-68b965d08b6f5" ).show(); //show P6 field
    jQuery( ".acf-field-68b965d08b72f" ).show(); //show P7 field
    jQuery( ".acf-field-68b965d08b769" ).show(); //show P8 field
}

function enable_service_environment_assessment() {
    jQuery( ".acf-field-68b96671e48d8" ).show(); //show P1 field
    jQuery( ".acf-field-68b96671e4921" ).show(); //show P2 field
    jQuery( ".acf-field-68b96671e4960" ).show(); //show P3 field    
    jQuery( ".acf-field-68b96671e499c" ).show(); //show P4 field
    jQuery( ".acf-field-68b96671e49d7" ).show(); //show P5 field
    jQuery( ".acf-field-68b96671e4a12" ).show(); //show P6 field
    jQuery( ".acf-field-68b96671e4a4c" ).show(); //show P7 field
    jQuery( ".acf-field-68b96671e4a86" ).show(); //show P8 field
}

function enable_geophysics_software_others() {    
    jQuery( ".acf-field-68bbebe20d468" ).show(); //show P1 field
    jQuery( ".acf-field-68bbebe20d4a9" ).show(); //show P2 field
    jQuery( ".acf-field-68bbebe20d4e7" ).show(); //show P3 field    
    jQuery( ".acf-field-68bbebe20d523" ).show(); //show P4 field
    jQuery( ".acf-field-68bbebe20d55e" ).show(); //show P5 field
    jQuery( ".acf-field-68bbebe20d598" ).show(); //show P6 field
    jQuery( ".acf-field-68bbebe20d5d2" ).show(); //show P7 field
    jQuery( ".acf-field-68bbebe20d60c" ).show(); //show P8 field
}

function enable_geophysics_software_r_ip_em_tem() {
    jQuery( '.acf-field[data-name="r_ip_em_geosoft_1"]' ).show();
    jQuery( '.acf-field[data-name="r_ip_em_geosoft_2"]' ).show();
    jQuery( '.acf-field[data-name="r_ip_em_geosoft_3"]' ).show();
    jQuery( '.acf-field[data-name="r_ip_em_geosoft_4"]' ).show();
    jQuery( '.acf-field[data-name="r_ip_em_geosoft_5"]' ).show();
    jQuery( '.acf-field[data-name="r_ip_em_geosoft_6"]' ).show();
    jQuery( '.acf-field[data-name="r_ip_em_geosoft_7"]' ).show();
    jQuery( '.acf-field[data-name="r_ip_em_geosoft_8"]' ).show();
}

function enable_site_dam_monitoring() {    
    jQuery( ".acf-field-68bfeffba802c" ).show(); //show P1 field
    jQuery( ".acf-field-68bfeffba806e" ).show(); //show P2 field
    jQuery( ".acf-field-68bfeffba80ac" ).show(); //show P3 field    
    jQuery( ".acf-field-68bfeffba80e6" ).show(); //show P4 field
    jQuery( ".acf-field-68bfeffba8121" ).show(); //show P5 field
    jQuery( ".acf-field-68bfeffba815b" ).show(); //show P6 field
    jQuery( ".acf-field-68bfeffba8195" ).show(); //show P7 field
    jQuery( ".acf-field-68bfeffba81d0" ).show(); //show P8 field
}

function enable_application_others() {
    jQuery( ".acf-field-690ed9795e64d" ).show(); //show P1 field
    jQuery( ".acf-field-690ed9795e68c" ).show(); //show P2 field
    jQuery( ".acf-field-690ed9795e6ca" ).show(); //show P3 field    
    jQuery( ".acf-field-690ed9795e70a" ).show(); //show P4 field
    jQuery( ".acf-field-690ed9795e74c" ).show(); //show P5 field
    jQuery( ".acf-field-690ed9795e788" ).show(); //show P6 field
    jQuery( ".acf-field-690ed9795e7c3" ).show(); //show P7 field
    jQuery( ".acf-field-690ed9795e7fd" ).show(); //show P8 field
}

function enable_accessory_others() {
    jQuery( ".acf-field-690ee0146754d" ).show(); //show P1 field
    jQuery( ".acf-field-690ee01467590" ).show(); //show P2 field
    jQuery( ".acf-field-690ee014675ce" ).show(); //show P3 field    
    jQuery( ".acf-field-690ee0146760b" ).show(); //show P4 field
    jQuery( ".acf-field-690ee01467647" ).show(); //show P5 field
    jQuery( ".acf-field-690ee01467681" ).show(); //show P6 field
    jQuery( ".acf-field-690ee014676bc" ).show(); //show P7 field
    jQuery( ".acf-field-690ee014676f7" ).show(); //show P8 field
}

function enable_service_data_processing() {
    jQuery( ".acf-field-69427c76c6bf1" ).show(); //show P1 field
    jQuery( ".acf-field-69427c76c6c35" ).show(); //show P2 field
    jQuery( ".acf-field-69427c76c6c75" ).show(); //show P3 field    
    jQuery( ".acf-field-69427c76c6cb3" ).show(); //show P4 field
    jQuery( ".acf-field-69427c76c6cef" ).show(); //show P5 field
    jQuery( ".acf-field-69427c76c6d2b" ).show(); //show P6 field
    jQuery( ".acf-field-69427c76c6d67" ).show(); //show P7 field
    jQuery( ".acf-field-69427c76c6da4" ).show(); //show P8 field
}

function enable_service_comprehensive() {
    jQuery( ".acf-field-69490288b01cc" ).show(); //show P1 field
    jQuery( ".acf-field-69490288b021e" ).show(); //show P2 field
    jQuery( ".acf-field-69490288b0269" ).show(); //show P3 field    
    jQuery( ".acf-field-69490288b02b4" ).show(); //show P4 field
    jQuery( ".acf-field-69490288b02f7" ).show(); //show P5 field
    jQuery( ".acf-field-69490288b0338" ).show(); //show P6 field
    jQuery( ".acf-field-69490288b0378" ).show(); //show P7 field
    jQuery( ".acf-field-69490288b03b6" ).show(); //show P8 field
}

function enable_application_geothermal() {
    jQuery( ".acf-field-694911747b8d8" ).show(); //show P1 field
    jQuery( ".acf-field-694911747b91a" ).show(); //show P2 field
    jQuery( ".acf-field-694911747b959" ).show(); //show P3 field    
    jQuery( ".acf-field-694911747b998" ).show(); //show P4 field
    jQuery( ".acf-field-694911747b9d7" ).show(); //show P5 field
    jQuery( ".acf-field-694911747ba13" ).show(); //show P6 field
    jQuery( ".acf-field-694911747ba4e" ).show(); //show P7 field
    jQuery( ".acf-field-694911747ba8b" ).show(); //show P8 field
}

function enable_positioning_survey_service() {
    jQuery( ".acf-field-69f5a479d4979" ).show(); //show P1 field
    jQuery( ".acf-field-69f5a479d49b7" ).show(); //show P2 field
    jQuery( ".acf-field-69f5a479d49f2" ).show(); //show P3 field    
    jQuery( ".acf-field-69f5a479d4a2d" ).show(); //show P4 field
    jQuery( ".acf-field-69f5a479d4a68" ).show(); //show P5 field
    jQuery( ".acf-field-69f5a479d4aa3" ).show(); //show P6 field
    jQuery( ".acf-field-69f5a479d4ade" ).show(); //show P7 field
    jQuery( ".acf-field-69f5a479d4b19" ).show(); //show P8 field
}

function enable_positioning_topographic_devices() {
    jQuery( ".acf-field-41eac42ac4471" ).show();
    jQuery( ".acf-field-41eb94a1c4471" ).show();
    jQuery( ".acf-field-41ec6873c4471" ).show();
    jQuery( ".acf-field-41ed5aa5c4471" ).show();
    jQuery( ".acf-field-41ee3b1dc4471" ).show();
    jQuery( ".acf-field-41ef4b93c4471" ).show();
    jQuery( ".acf-field-41f01ed1c4471" ).show();
    jQuery( ".acf-field-41f0fe8bc4471" ).show();
}

function enable_data_processing_seismic() {
    jQuery( ".acf-field-ef44e676c4471" ).show();
    jQuery( ".acf-field-ef45f89fc4471" ).show();
    jQuery( ".acf-field-ef46f6bdc4471" ).show();
    jQuery( ".acf-field-ef47f359c4471" ).show();
    jQuery( ".acf-field-ef48e1e3c4471" ).show();
    jQuery( ".acf-field-ef49ef06c4471" ).show();
    jQuery( ".acf-field-ef4ac5f8c4471" ).show();
    jQuery( ".acf-field-ef4b958ac4471" ).show();
}

function enable_data_processing_r_ip_em_tem() {
    jQuery( ".acf-field-2e5f4ff2c4491" ).show();
    jQuery( ".acf-field-2e605553c4491" ).show();
    jQuery( ".acf-field-2e6138a9c4491" ).show();
    jQuery( ".acf-field-2e625fc8c4491" ).show();
    jQuery( ".acf-field-2e6361b0c4491" ).show();
    jQuery( ".acf-field-2e645f8ac4491" ).show();
    jQuery( ".acf-field-2e65459bc4491" ).show();
    jQuery( ".acf-field-2e6635abc4491" ).show();
}
