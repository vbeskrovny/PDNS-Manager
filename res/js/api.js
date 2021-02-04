
var GLOBALS = {};
var row_id = 1;


function globals_init() {
	
	$.post('api2/globals_init', function(data) {
		GLOBALS = data;
	});

}


function signup() {
	

	var form_data = $('#auth_form').serializeArray();
	var all_valid = true;
	
	for (let key in form_data) {
		
		if (form_data[key].name == 'username') {
			if (form_data[key].value == '') {
				all_valid = false;
				$('.auth-form-username').toggleClass('is-invalid', true);
			} else {
				$('.auth-form-username').removeClass('is-invalid');
			}
		} else if (form_data[key].name == 'password') {
			if (form_data[key].value == '') {
				all_valid = false;
				$('.auth-form-password').toggleClass('is-invalid', true);
			} else {
				$('.auth-form-password').removeClass('is-invalid');
			}
		}
		
		
	}
	
	
	$('.auth-form-otp').removeClass('is-invalid');


	if (all_valid) {
	
		$.post('api2/signup', form_data, function(data) {

			$('input#passwordHash').val(data.password_hash);
			$('input#otpSecret').val(data.otp_secret);
			$('input#otpURL').val(data.otp_url);
			$('img#otpIMG').attr('src', data.otp_qr);
			
			
			$('.signup-div').show();
			
		});
		
	}

}



function update_cookie(content) {
	Cookies.set('pdns_auth_cookie', content, { secure: true, expires: 365 });
}


function remove_cookie() {
	Cookies.remove('pdns_auth_cookie', { secure: true });
}



function sign_out() {
	remove_cookie();
	location.reload();
}



function auth_loop() {
	
	var delay = GLOBALS['AUTH_INTERVAL'] * 1000;
	
	setTimeout( function() {
		
		$.get('api2/check_auth', function(data) {
			
			if (data.auth_status) {
				
				update_cookie(data.auth_cookie);
				
				
			} else {
				
				remove_cookie();

				
			}
			
			auth_loop();
			
		});
		

		
	}, delay);
	

}



function do_auth() {
	
	var form_data = $('#auth_form').serializeArray();
	
	$.post('api2/do_auth', form_data, function(data) {
		
		if (data.auth_status) {
			
			update_cookie(data.auth_cookie);
			
			$('.auth-container').hide();
			$('.pdns-container').show();
			refresh_zones();

			
		} else {
			
			remove_cookie();
			
			$('.auth-form-username').toggleClass('is-invalid', true);
			$('.auth-form-password').toggleClass('is-invalid', true);
			$('.auth-form-otp').toggleClass('is-invalid', true);

		}
		
		
		
	});
	
	
	
	return false;
	
}



function save_records() {

	var zone = $('#zones :selected').val();
	if (zone && zone != 0) {

		var form_data = $('#records_form').serializeArray();

		var all_valid = true;
		
		$('#records_form .record-data').each(function( index ) {
			
			if (this.value == '') {
				all_valid = false;
				$(this).toggleClass('is-invalid', true);
			} else {
				$(this).removeClass('is-invalid');
			}
			
		});
		
		
		if (all_valid) {

			$.post('api2/save_records', form_data, function(data) {

				get_records(zone);	

			});
			
		}

		
	}

}


function remove_row(row_id) {
	$('#row_' + row_id).remove();
}


function remove_zone() {
	var zone = $('#zones :selected').val();
	if (zone && zone != 0) {
		if (confirm('Proceed with the "' + zone + '" zone removal?')) {
			$.post('api2/remove_zone/' + zone, function(data) {
				clear_records();
				refresh_zones();
			});
		}
	}	
}



function refresh_records() {
	var zone = $('#zones :selected').val();
	if (zone && zone != 0) {
		get_records(zone);
	}
	refresh_zones(zone);
}



function clear_records() {
	$('#records').empty();
}



function add_zone(params) {
	$.post('api2/add_zone', params, function (data) {
		clear_records();
		refresh_zones();
	});
}


function get_records(zone) {
	
	$.post('api2/get_records/' + zone, function(data) {

	
		clear_records();


		for (let key in data) {
		
			// console.log(data[key]);
			
			var type = data[key].type;
			var name = data[key].name;
			var records = data[key].records;
			var ttl = data[key].ttl;
			
			// console.log(records);
			
			
			records.forEach(function(value, index, array) {
				
				var content = value.content;
				
				var record_tpl = `<div class="row no-gutters pb-1" id="row_${row_id}">
					<div class="col-1"><input class="form-control form-control-sm record-data" type="text" name="type[]" value="${type}" readonly></div>
					<div class="col-2"><input class="form-control form-control-sm record-data" type="text" name="name[]" value="${name}"></div>
					<div class="col-7"><input class="form-control form-control-sm record-data" type="text" name="content[]" value="${content}"></div>
					<div class="col-1"><input class="form-control form-control-sm record-data" type="text" name="ttl[]" value="${ttl}"></div>
					<div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger shadow-none pl-3 pr-3 btn-block" onClick="remove_row(${row_id})"><i class="far fa-trash-alt"></i></button></div>
				</div>`;
				
				$('#records').append(record_tpl);
				
				row_id++;
				
			});
			
			
			
		
		}
		
		
		var add_record_tpl = `<div class="row no-gutters pt-3" id="add_record_btn_div">
														<div class="col-12">
															<button type="button" class="btn btn-sm btn-outline-success shadow-none btn-block" onClick="add_record('${zone}')">Add new record<i class="fas fa-plus pl-2"></i></button>
														</div>
													</div>`;
		
		$('#records').append(add_record_tpl);		
		
		
	});
	
	
}

function set_type(row_id, type, zone) {
	
	var type_val = '';
	var content_attr = '';
	
	
	if (type != '') {
		
		type_val = type;
	
		if (type == 'TXT') {
			content_attr = '"some text value surrounded by quotes"';
		} else if (type == 'A') {
			content_attr = '127.1.2.3';
		} else if (type == 'CNAME') {
			content_attr = 'domain01.' + zone;
		}

	}

	
	$('#type_field_' + row_id).val(type_val);
	$('#content_field_' + row_id).attr('placeholder', content_attr);
	
	
}



function add_record(zone) {
	
	var dh = $(document).height();
	
	
	var new_record_tpl = `<div class="row no-gutters pb-1" id="row_${row_id}">
		<div class="col-1">
			<select class="form-control form-control-sm shadow-none" onChange="set_type(${row_id}, this.selectedOptions[0].value, '${zone}')">
				<option></option>
				<option value="A">A</option>
				<option value="CNAME">CNAME</option>
				<option value="TXT">TXT</option>
			</select>
			<input class="record-data" type="hidden" name="type[]" value="" id="type_field_${row_id}">
		</div>
		<div class="col-2"><input class="form-control form-control-sm record-data" type="text" name="name[]" value="" placeholder="${zone}" id="name_field_${row_id}"></div>
		<div class="col-7"><input class="form-control form-control-sm record-data" type="text" name="content[]" value="" id="content_field_${row_id}"></div>
		<div class="col-1"><input class="form-control form-control-sm record-data" type="text" name="ttl[]" value="" placeholder="60" id="ttl_field_${row_id}"></div>
		<div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger shadow-none pl-3 pr-3 btn-block" onClick="remove_row(${row_id})"><i class="far fa-trash-alt"></i></button></div>
	</div>`;
	
	
	$(new_record_tpl).insertBefore( $('#add_record_btn_div') );
	row_id++;



	var diff = $(document).height() - dh;
	window.scrollBy(0, diff);

	
	
}



function refresh_zones(default_zone = null) {

	$.post('api2/get_zones', function(data) {
		
		var list = $('#zones').empty();
		list.append($('<option>').attr('value', 0).text(''));

		
		data.forEach(function(value, index, array) {
			list.append($('<option>').attr('value', value).attr('selected', value == default_zone).text(value));
		});
		

		list.append($('<option>').attr('value', 0).text('----------'));
		list.append($('<option>').attr('value', '+1').text('Add new zone'));
		
		
	});

	
}


