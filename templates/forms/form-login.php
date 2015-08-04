<div id="login" class="xcp-form" style="display:none;">
  <h2>ExpenseCalcPro Login</h2>
  <h3 id="info">Enter login credentials to proceed, or click new user to signup.</h3>
  <p class="error" style="display:none;">The following errors occurred.</p>
  <ul class="errors">
  </ul>
  <table id="login-form">
  	<tr class="form-row signup">
      <td><label for="xcpTxtFirstName" class="xcp-form-label">First Name:</label></td>
      <td><input type="text" id="xcpTxtFirstName" class="xcp-form-input" /></td>
    </tr>
    <tr class="form-row signup">
      <td><label for="xcpTxtLastName" class="xcp-form-label">Last Name:</label></td>
      <td><input type="text" id="xcpTxtLastName" class="xcp-form-input" /></td>
    </tr>
    <tr class="form-row">
      <td><label for="txtEmail" class="xcp-form-label">Email:</label></td>
      <td><input type="email" id="xcpTxtEmail" class="xcp-form-input" /></td>
    </tr>
    <tr class="form-row">
      <td><label for="txtPassword" class="xcp-form-label">Password:</label></td>
      <td><input type="password" id="xcpTxtPassword" class="xcp-form-input" /></td>
    </tr>
    <tr class="form-row signup">
      <td><label for="txtConfirmPass" class="xcp-form-label">Confirm Password:</label></td>
      <td><input type="password" id="xcpTxtConfirmPass" class="xcp-form-input" /></td>
    </tr>
  </table>
</div>
<script>
(function($) {
	$(document).ready(function(e) {
		var options = {
			title: "Welcome Back",
			autoOpen: true,
			modal: true,
			width: 'auto',
			buttons: {
				'Login': formHash,
				'New User': userSignUp
			}
		}
		
		$("#login").dialog(options);
		
		$('#xcpTxtEmail').val('jusmark123@yahoo.com');
		$('#xcpTxtPassword, #xcpTxtConfirmPass').val('Hjvlk69a');
    });
	
	function loginAjax(data) {
		
		$.post("./php/ajax.php", data, function(response) {
			response = $.parseJSON(response);
			
			if( response.success ) {
				location.reload();
			} else {
				$('.errors').empty().show();
				for( var i in response.errors ) {
					$('.errors').prepend('<li class="error">' + response.errors[i] + '</li>');
				}
			}
		});
	}
	
	function userSignUp() {
		var buttons = $('#login').dialog( 'option', 'buttons' );
		var newButtons = {
			'Sign Me Up': function() {
				regFormHash()
			},
			'Returning User': function() {
				$('#confirmPass').hide();
				$(this).dialog( 'option', 'buttons', buttons );
			}
		};
		
		$('#xcpTxtConfirmPass').on( 'blur', function() {
			$('.errors').empty();
			if( $('#xcpTxtPassword').val() != $(this).val() ) {
				$('.errors').append('<li class="error">Your password and confirmation do not match.</li>').show();
			} else {
				$('.errors').empty();	
			}
		});
		
		$('.signup').show();
		$('#login').dialog( 'option', 'buttons', newButtons );		
		$('#info').text("Enter email and create a password to proceed");
		//$('#xcpTxtEmail').val('');
		//$('#xcpTxtPassword').val('');
		$('.error').hide();
		$('.errors').empty();
	}
	
	function formHash() {
		var data = {
			action: 'loginFunctions',
			call: 'login',
			email: $("#xcpTxtEmail").val(),
			password: hex_sha512($('#xcpTxtPassword').val())
		}
		
		loginAjax( data );
	}
	
	function regFormHash() {
		var errors = [];
		var firstName = $('#xcpTxtFirstName').val();
		var lastName = $('#xcpTxtLastName').val();
		var email = $('#xcpTxtEmail').val();
		var password = $('#xcpTxtPassword').val();
		var passConf = $('#xcpTxtConfirmPass').val();
		
		if( firstName == '' || lastName == '' ||  email == '' || password == '' || passConf == '' ) {
			
			$('.errors').append('<li class="error">You must provide all the requested details. Please try again.').show();
			
			$('#xcpTxtEmail').focus();
			
			return false;
		}
		
		if( password.length < 6) {
			errors.push( 'Passwords must be at least 6 characters long.' );
		} 
		
		var re = /(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,}/; 
		
		if( ! re.test( password ) ) {
			errors.push( 'Passwords must contain' );
			errors.push( 'at least one number, ' );
			errors.push( 'one lowercase and one uppercase letter' );
		}
		
		if( errors.length > 0 )  {
			for( var i in errors ) {
				$('.errors').append('<li class="error">' + errors[i] + '</li>');	
			}
			
			$('.errors').show();
			return false;
		} else {
			var data = {
				action: 'loginFunctions',
				call: 'signup',
				firstName: firstName,
				lastName: lastName,
				email: $("#xcpTxtEmail").val(),
				password: hex_sha512( password )
			}
			
			loginAjax( data );
		}
	}
})(jQuery);
</script>