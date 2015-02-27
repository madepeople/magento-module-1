var paynovapayment_jQuery = jQuery.noConflict(true);

function paynova_paynovapayment_validategovernmentid(governmentid) {
    if (governmentid.length<12 || governmentid.length>13) {
        return false;
    }

    var pattern=/^[\d\-]+$/;
    if (governmentid.search(pattern)==-1) {
        return false;
    }

    if ( (parseInt(governmentid.substring(2, 0)) != 19) && (parseInt(governmentid.substring(2, 0)) !=20) ) {
        return false;
    }
    if ( (parseInt(governmentid.substring(6, 4)) < 1) || (parseInt(governmentid.substring(6, 4)) > 12)) {
        return false;
    }
    if ( (parseInt(governmentid.substring(8, 6)) < 1) || (parseInt(governmentid.substring(8, 6)) > 31)) {
        return false;
    }
    return true;
}

function paynova_paynovapayment_validateoutput() {
    if (!paynovapayment_jQuery('#paynova_paynovapayment_error').is(':empty')){
        return false;
    }
    return true;
}


function paynova_paynovapayment_getAddress(paynova_paynovapayment_governmentid){

    if (paynova_paynovapayment_validategovernmentid(paynova_paynovapayment_governmentid)){

        paynovapayment_jQuery("#paynova_paynovapayment_output").html(paynova_paynovapayment_waittxt);
        paynovapayment_jQuery("#paynovapayment_continue").prop("disabled",true);
        paynovapayment_jQuery.ajax({
            type:'POST',
            url:paynova_paynovapayment_getAddressUrl,
            data: {'governmentid': paynova_paynovapayment_governmentid},
            success: function(output) {
                var jsonData = JSON.parse(output);
                var address_output;
                if (jsonData.isSuccess==1) {
                    var firstName = jsonData.name["firstName"];
                    var lastName = jsonData.name["lastName"];
                    var Street = jsonData.address["Street"];
                    var City = jsonData.address["City"];
                    var postalCode = jsonData.address["postalCode"];


                    var email = jsonData.email;
                    address_output = paynova_paynovapayment_invoicetxt+":<br/>"+firstName+" "+lastName+"<br/>"+Street+" "+postalCode+" "+City;
                    paynovapayment_jQuery("#paynova_paynovapayment_error").html("");
                    paynovapayment_jQuery("#paynova_paynovapayment_output").html(address_output);
                    if (email){
                        paynovapayment_jQuery("#paynova_paynovapayment_orderemail").html(email);
                    } else {
                        email = paynovapayment_jQuery("#billing\\:email").val();
                    }
                    paynovapayment_jQuery("#paynova_paynovapayment_orderemail").html(email);
                    //paynova_paynovapayment_getPaymentMethods();
                } else {
                    var i;
                    var error_output ="";
                        for (i = 0; i < jsonData.errors.length; i++) {
                            error_output += "Error: "+jsonData.errors[i].errorCode+". Field: "+jsonData.errors[i].fieldName+". Message: "+jsonData.errors[i].message+".<br/>";
                        }
                    paynovapayment_jQuery("#paynova_paynovapayment_output").html("");
                    paynovapayment_jQuery("#paynova_paynovapayment_error").html(error_output);
                }
                paynovapayment_jQuery("#paynovapayment_continue").prop("disabled",false);
            }
        });
    } else {
        paynovapayment_jQuery("#paynova_paynovapayment_error").html(paynova_paynovapayment_govermentInvalidtxt);
    }
}
function paynova_paynovapayment_installment_getAddress(paynova_paynovapayment_installment_governmentid){

    if (paynova_paynovapayment_validategovernmentid(paynova_paynovapayment_installment_governmentid)){

        paynovapayment_jQuery("#paynova_paynovapayment_installment_output").html(paynova_paynovapayment_waittxt);
        paynovapayment_jQuery("#paynovapayment_installment_continue").prop("disabled",true);
        paynovapayment_jQuery.ajax({
            type:'POST',
            url:paynova_paynovapayment_getAddressUrl,
            data: {'governmentid': paynova_paynovapayment_installment_governmentid},
            success: function(output) {

                var jsonData = JSON.parse(output);
                var address_output;
                if (jsonData.isSuccess==1) {
                    var firstName = jsonData.name["firstName"];
                    var lastName = jsonData.name["lastName"];
                    var Street = jsonData.address["Street"];
                    var City = jsonData.address["City"];
                    var postalCode = jsonData.address["postalCode"];


                    var email = jsonData.email;
                    address_output = paynova_paynovapayment_invoicetxt+":<br/>"+firstName+" "+lastName+"<br/>"+Street+" "+postalCode+" "+City;
                    paynovapayment_jQuery("#paynova_paynovapayment_installment_error").html("");
                    paynovapayment_jQuery("#paynova_paynovapayment_installment_output").html(address_output);
                    if (email){
                        paynovapayment_jQuery("#paynova_paynovapayment_orderemail").html(email);
                    } else {
                        email = paynovapayment_jQuery("#billing\\:email").val();
                    }
                    paynovapayment_jQuery("#paynova_paynovapayment_orderemail").html(email);
                    //paynova_paynovapayment_getPaymentMethods();
                } else {
                    var i;
                    var error_output ="";
                    for (i = 0; i < jsonData.errors.length; i++) {
                        error_output += "Error: "+jsonData.errors[i].errorCode+". Field: "+jsonData.errors[i].fieldName+". Message: "+jsonData.errors[i].message+".<br/>";
                    }
                    paynovapayment_jQuery("#paynova_paynovapayment_installment_output").html("");
                    paynovapayment_jQuery("#paynova_paynovapayment_installment_error").html(error_output);
                }
                paynovapayment_jQuery("#paynovapayment_installment_continue").prop("disabled",false);
            }
        });
    } else {
        paynovapayment_jQuery("#paynova_paynovapayment_installment_error").html(paynova_paynovapayment_govermentInvalidtxt);
    }
}

function paynova_paynovapayment_getPaymentMethods(){
    paynovapayment_jQuery("#paynova_paynovapayment_installment_paymentmethodseerror").html("");
    paynovapayment_jQuery("#paynova_paynovapayment_installment_paymentmethodsoutput").html("");
    paynovapayment_jQuery.ajax({
            type:'POST',
            url:paynova_paynovapayment_getPayMethodsUrl,
            success: function(output) {
                var error_output;
                var options_output ="";
                var jsonData = JSON.parse(output);
                var i;

                if (jsonData.isSuccess==1) {

                    options_output += '<select name="payment[paynova_paynovapayment_installment_paymentmethod]" id="payment[paynova_paynovapayment_installment_paymentmethod]" onchange="switchInstallment(this.options[this.selectedIndex])">';

                    for (i = 0; i < jsonData.options.length; i++) {


                        if ((jsonData.options.length-1)==i){
                            options_output +=
                                '<option value="'+jsonData.options[i].productId+'" id="'+jsonData.options[i].productId+'" uri="'+jsonData.options[i].uri+'" installmentText="'+jsonData.options[i].installmentText+'">'
                                    +' <label for="'+jsonData.options[i].productId+'">'+jsonData.options[i].displayName+'</label></option>';
                        } else {
                            options_output +=
                                '<option value="'+jsonData.options[i].productId+'" id="'+jsonData.options[i].productId+'" name="payment[paynova_paynovapayment_paymentmethod]" uri="'+jsonData.options[i].uri+'" installmentText="'+jsonData.options[i].installmentText+'">'
                                    +' <label for="'+jsonData.options[i].productId+'">'+jsonData.options[i].displayName+'</label></option>';
                        }


                    }
                    options_output += '</select>';
                    paynovapayment_jQuery("#paynova_paynovapayment_installment_paymentmethodseerror").html("");
                    paynovapayment_jQuery("#paynova_paynovapayment_installment_paymentmethodsoutput").html(options_output);
                } else {
                    var statusmsg = jsonData.statusMessage
                    error_output = statusmsg;
                    paynovapayment_jQuery("#paynova_paynovapayment_installment_paymentmethodsoutput").html("");
                    paynovapayment_jQuery("#paynova_paynovapayment_installment_paymentmethodseerror").html(error_output);
                }
            }
        });
}
function switchInstallment(option){
    //console.log(option.value);
    if(option.value == ''){
        document.getElementById('paynova_paynovapayment_installment_terms').style.display = "none";
        document.getElementById('paynova_paynovapayment_installment_installmenttext').style.display = "none";
        return;
    }

    tocLink = option.getAttribute('uri');
    tocLinkObj = document.getElementById('paynova_paynovapayment_installment_terms_link').setAttribute('href',tocLink);
    document.getElementById('paynova_paynovapayment_installment_terms_link').setAttribute('target', '_blank');
    document.getElementById('paynova_paynovapayment_installment_terms').style.display = "block";
    document.getElementById('paynova_paynovapayment_installment_installmenttext').style.display = "block";
    if(option.getAttribute('installmentText') != '') {
        paynovapayment_jQuery("#paynova_paynovapayment_installment_installmenttext").html(option.getAttribute('installmentText'));
    }else{
        document.getElementById('paynova_paynovapayment_installment_installmenttext').style.display = "none";
    }


}
function paynova_paynovapayment_getOrderEmail(){
    paynovapayment_jQuery.ajax({
        type:'POST',
        url:paynova_paynovapayment_getOrderEmailUrl,
        success: function(output) {
            if (output.length){
               paynovapayment_jQuery("#paynova_paynovapayment_orderemail").html(output);
            } else {
                paynovapayment_jQuery( "#billing\\:email" ).change(function() {
                    var paynova_paynovapayment_billingmail = paynovapayment_jQuery( this ).val();
                   paynovapayment_jQuery("#paynova_paynovapayment_orderemail").html(paynova_paynovapayment_billingmail);
                });
            }
        }
    });
}



