//= include ../bower_components/jquery/dist/jquery.js
//= include ../bower_components/bootstrap-sass/assets/javascripts/bootstrap.js
//= include ../bower_components/jquery-validate/dist/jquery.validate.js
//= include ../bower_components/moment/moment.js
//= include ../bower_components/eonasdan-bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js
//= include ../bower_components/jquery-ui/jquery-ui.js
//= include ../bower_components/nestedSortable/jquery.ui.nestedSortable.js
//= include ../bower_components/TableDnD/js/jquery.tablednd.js
//= include ../bower_components/bootstrap3-typeahead/bootstrap3-typeahead.js
//= include ../bower_components/JSColor/jscolor.js
//= include ../bower_components/jquery-file-upload/js/jquery.fileupload.js
//= include ../bower_components/jquery-maskedinput/dist/jquery.maskedinput.js
//= include redactor1009/redactor/redactor.js

$(function() {

	//add time and datetime rules to validator
	$.validator.addMethod('time', function(value, element) {  
		return this.optional(element) || /^(0?[1-9]|1[012])(:[0-5]\d) [APap][mM]$/i.test(value);  
	}, 'Please enter a valid time.');

	$.validator.addMethod('datetime', function(value, element) {  
		return this.optional(element) || /^[0,1]?\d\/(([0-2]?\d)|([3][01]))\/([0-9]{4})\s(0?[1-9]|1[012])(:[0-5]\d) [APap][mM]$/i.test(value);  
	}, 'Please enter a valid date and time.');

	//generic form validator
	$('form.create, form.edit').validate({
		onfocusout:false,
    	onkeyup: function(element) { },
		highlight: function(element, errorClass, validClass) {
			$(element).closest('div.form-group').addClass('has-error');
		},
		unhighlight: function(element, errorClass, validClass) {
			$(element).closest('div.form-group').removeClass('has-error');
		},
		errorPlacement: function(error, element) {
			return; //don't show message on page, simply highlight
		}, 
	});

	//autoselect first text element that's not a color (jscolor gets messed up when autoselected)
	$('form input[type=text]:not(.color,.slug)').first().focus();

	//datetimepickers
	$('.input-group.datetime').datetimepicker();
	$('.input-group.date').datetimepicker({pickTime: false});
	$('.input-group.time').datetimepicker({pickDate: false});

	//phone doesn't work internationally
	//$('input.phone').mask('(999) 999-9999');
	//$('#date').mask('99/99/9999',{placeholder:'mm/dd/yyyy'});
	//$('#tin').mask('99-9999999');
	//$('#ssn').mask('999-99-9999');

	//verify addresses with google maps, update dependent fields
	$('input.address').blur(function(){
		var $this = $(this);
		if (!$this.val().length) return;
		$.getJSON('https://maps.googleapis.com/maps/api/geocode/json', { address: $this.val(), sensor: false }, function(data){
			//console.log(data);
			$this.val(data.results[0].formatted_address);
			var name = $this.attr('name');
			$('input.latitude[data-source=' + name + ']').each(function(){
				$(this).val(data.results[0].geometry.location.lat);
			});
			$('input.longitude[data-source=' + name + ']').each(function(){
				$(this).val(data.results[0].geometry.location.lng);
			});

			var state = false;			
			for (var i = 0; i < data.results[0].address_components.length; i++) {
				if (data.results[0].address_components[i].types[0] == 'administrative_area_level_1') {
					state = data.results[0].address_components[i].short_name;
				}
			}
			if (state) {
				$('select.us_state[data-source="' + name + '"]').each(function(){
					$('select.us_state[data-source="' + name + '"] option:selected').attr('selected', false);
					$('select.us_state[data-source="' + name + '"] option[value="' + state + '"]').attr('selected', true);
				});
			}
			var country = false;			
			for (var i = 0; i < data.results[0].address_components.length; i++) {
				if (data.results[0].address_components[i].types[0] == 'country') {
					country = data.results[0].address_components[i].short_name;
				}
			}
			if (country) {
				$('select.country[data-source="' + name + '"]').each(function(){
					$('select.country[data-source="' + name + '"] option:selected').attr('selected', false);
					$('select.country[data-source="' + name + '"] option[value="' + country + '"]').attr('selected', true);
				});
			}
		});
	});
	
	//fix annoying url issue on blur
	$('input.url').blur(function(){
		var val = $(this).val();
		if (!val.length) return;
		if (val.substr(0, 4) != 'http') {
			val = 'http://' + val;
		}
		if (val.split('/').length == 3) val = val + '/';
		$(this).val(val);
	});

	//draggable tables
	$('table.draggable').tableDnD({
		dragHandle: '.draggy',
		onDragClass: 'dragging',
		onDrop: function(table, row) {
			$.post($(table).attr('data-draggable-url'), { 
					order: $(table).tableDnDSerialize(),
					_token: $(table).attr('data-csrf-token')
				}, function(data){
				//window.console.log('success with ' + data);
			}).fail(function() { 
				//window.console.log('error');
			});
		}
	});
	
	//nested sortable
	$('div.nested > ul').nestedSortable({
		listType: 'ul',
		forcePlaceholderSize: true,
		handle: 'div.draggy',
		helper: 'clone',
		items: 'li',
		opacity: 0.8,
		tabSize: 30,
		delay: 300,
		placeholder: 'placeholder',
		tolerance: 'pointer',
		toleranceElement: '> div',
		protectRoot: false,
		update: function(event, ui) {
			var id 				= ui.item.attr('id').substr(5);
			var arrayed 		= $('div.nested > ul').nestedSortable('toArray', {startDepthCount: 0});
			var list 			= new Array();
			var parent_id 		= false;

			for (var i = 0; i < arrayed.length; i++) {
				if (arrayed[i].item_id != 'root') list[list.length] = arrayed[i].item_id;
				if (arrayed[i].item_id == id) parent_id = arrayed[i].parent_id;
			}

			$.post($('div.nested').first().attr('data-draggable-url'), { 
					id : id,
					parent_id : parent_id, 
					list : list.join(',')
			}, function(data){
				//$('.side .inner').html(data);
			});
		}        
	});

	//toggle instance, field, or user active or inactive
	$('table').on('click', 'td.delete a', function(e) {
		e.preventDefault();
		
		//toggle row class
		var parent = $(this).closest('tr');
		parent.toggleClass('inactive');
		if (parent.hasClass('inactive')) {
			var active = 0;
			$(this).find('i').removeClass('glyphicon-ok-circle').addClass('glyphicon-remove-circle');
		} else {
			var active = 1;
			$(this).find('i').removeClass('glyphicon-remove-circle').addClass('glyphicon-ok-circle');
		}
		
		//send ajax update
		$.get($(this).attr('href'), { active: active }, function(data){
			//window.console.log('sent post and data was ' + data);
			parent.find('td.updated_at').html(data);
		}).fail(function() { 
			//window.console.log('error');
		});
	});
	
	//toggle instance inside nested sortable
	$('div.nested').on('click', 'div.delete a', function(e) {
		e.preventDefault();
		
		//toggle row class
		var parent = $(this).closest('div.nested_row');
		parent.toggleClass('inactive');
		if (parent.hasClass('inactive')) {
			var active = 0;
			$(this).find('i').removeClass('glyphicon-ok-circle').addClass('glyphicon-remove-circle');
		} else {
			var active = 1;
			$(this).find('i').removeClass('glyphicon-remove-circle').addClass('glyphicon-ok-circle');
		}
		
		//send ajax update
		$.get($(this).attr('href'), { active: active }, function(data){
			//window.console.log('sent post and data was ' + data);
			parent.find('div.updated_at').html(data);
		}).fail(function() { 
			//window.console.log('error');
		});
	});
	
	//instance index search
	$('form#search').on('change', 'select', function(){
		$('form#search').submit();
	}).on('click', 'i.glyphicon', function(){
	    $('form#search input').val('');
		$('form#search').submit();
	}).on('submit', function(){
        var text = $(this).find(':input').filter(function(){
            return $.trim(this.value).length > 0
        }).serialize();
        if (text.length) text = '?' + text;
        window.location.href = window.location.href.replace('#', '').split('?')[0] + text;
        return false;
    });
	
	
	/* redactor fields
	if (typeof RedactorPlugins === 'undefined') var RedactorPlugins = {};

	RedactorPlugins.advanced = {
	    init: function ()  {
	        this.buttonAdd('advanced', 'Lorem Ipsum', this.testButton);

	        // make your added button as Font Awesome's icon
	        //this.buttonAwesome('advanced', 'glyphicon glyphicon-ok-circle');
	    },
	    testButton: function(buttonName, buttonDOM, buttonObj, e)
	    {
	        alert(buttonName);
        	editor = this;
	        $.getJSON('http://hipsterjesus.com/api/', function(data) {
				$('.redactor_act').removeClass('redactor_act');
	            editor.set(data.text);
	            editor.focusEnd();
	        });
	    }
	};*/

	$('textarea.html').redactor({
		buttonSource: true,
		minHeight: 240,
		maxHeight: 500,
		removeAttr:  [
			['a', 'style'],
			['blockquote', 'style'],
			['em', 'style'],
			['hr', ['style', 'id']],
			//['p', 'style'],
			['span', 'style'],
			['strong', 'style']
    	],
    	initCallback: function() {
    		//console.log(this.code.get());
    	}
		//, plugins: ['advanced']
	});
	
	//slug fields
	function slugify(val) {
		return val.toLowerCase().replace(/-+/g, ' ').replace(/ +/g, ' ').replace(/[^a-z0-9\ ]/g, '').replace(/ /g, '-');		
	}

	function slugifyAndTrim(val) {
		return val.toLowerCase().replace(/-+/g, ' ').replace(/ +/g, ' ').replace(/[^a-z0-9\ ]/g, '').trim().replace(/ /g, '-');		
	}

	//add to textarea, select and any other form elements?
	$('body').on('change', 'input', function(){
		$(this).addClass('modified');
	});

	$('input.slug').each(function() {
		//keyup handler for slug field
		$(this).on('keyup', function() {
			var val = slugify($(this).val());
			if ($(this).val() != val) $(this).val(val);
		}).on('blur', function() {
			$(this).val(slugifyAndTrim($(this).val()));
		});
		
		//keyup handler for source field
		if ($(this).attr('data-source')) {
			var $slug = $(this);
			$('form.create input[name=' + $slug.attr('data-source') + ']').on('keyup', function(){
				if (!$slug.hasClass('modified')) $slug.val(slugifyAndTrim($(this).val()));
			});
		}
	});

	//typeaheads	
	$('input.typeahead').each(function(){
		var $this = $(this);
		$.getJSON($this.attr('data-typeahead'), function(data){
		    $this.typeahead({ source:data });
		});
	});

	//handle remove event
	$('body').on('click', 'form.upload a.remove', function(){
		var $form = $(this).parent('form');
		var $div = $('div[data-form-id=' + $form.attr('id') + ']');
		var $sibs = $div.siblings('div.image');
		var field_id = $div.attr('data-field-id');
		var $hidden = $div.closest('.form-group').find('input[type=hidden]');
		$form.remove();
		$div.remove(); //todo animate
		$hidden.setUploadedIds(field_id);
		$sibs.each(function(){
			$(this).checkUploadForm();
		});

	});
	
	//jquery function to cover a input element, used on page load and when cloning
	jQuery.fn.extend({
		setUploadedIds : function(table_name, field_name) {
			var ids = new Array();
			$('.image[data-table-name=' + table_name + '][data-field-name=' + field_name + ']:not(.new)').each(function(){
				ids[ids.length] = $(this).attr('data-file-id')
			});
			$(this).val(ids.join(','));
		},
		checkUploadForm : function() {
			var offset   = $(this).offset();
			var width    = $(this).width();
			var height   = $(this).height();
			$('form#' + $(this).attr('data-form-id')).css({
				top: offset.top, 
				left: offset.left,
				width: width,
				height: height
			});
		},
		setupUploadForm : function() {
			var random	 = randomStr();
			var offset   = $(this).offset();
			var width    = $(this).width();
			var height   = $(this).height();
			var action	 = $(this).attr('data-action');
			var table_name = $(this).attr('data-table-name');
			var field_name = $(this).attr('data-field-name');
			var multiple = $(this).closest('.form-group').hasClass('field-images');
			var isnew    = $(this).hasClass('new');
			var token	 = $(this).closest('form').find('input[name=_token]').val();

			//set form attr
			$(this).attr('data-form-id', random);

			//create form
			if (multiple) {				
				$('<form id="' + random + '" class="upload upload_image' + (isnew ? ' new' : '') + '">' + 
					'<input type="hidden" name="_token" value="' + token + '">' + 
					'<input type="hidden" name="table_name" value="' + table_name + '">' + 
					'<input type="hidden" name="field_name" value="' + field_name + '">' + 
					'<input type="file" name="image" multiple>' +
					'<a class="remove"><i class="glyphicon glyphicon-remove-circle"></i></a>' +
					'</form>')
					.appendTo('body');
			} else {
				$('<form id="' + random + '" class="upload upload_image">' + 
					'<input type="hidden" name="_token" value="' + token + '">' + 
					'<input type="hidden" name="table_name" value="' + table_name + '">' + 
					'<input type="hidden" name="field_name" value="' + field_name + '">' + 
					'<input type="file" name="image">' +
					'</form>')
					.appendTo('body');
			}

			//position form
			$(this).checkUploadForm();		
				
			//set upload event on form input
			$('form#' + random + ' input[type=file]').fileupload({
				url: 				action,
				type: 				'POST',
				dataType: 			'json', 
				acceptFileTypes : 	/(\.|\/)(jpg|gif|png)$/i,
				autoUpload: 		true,
				add: function(e, data) {
					data.submit();
				},
				fail: function(e, data) {
					//window.console.log(data.jqXHR.responseJSON.error);
					//window.console.log(data.jqXHR.responseText);
				},
				done: function(e, data) {
					//window.console.log(data);

					//get some vars
					var multiple = $(this).prop('multiple');
					var $form = $(this).parent();
					var table_name = $form.find('input[name=table_name]').val();
					var field_name = $form.find('input[name=field_name]').val();
					var $div = $('div.image[data-form-id=' + $form.attr('id') + ']');
					var $hidden = $div.closest('.form-group').find('input[type=hidden]');

					//if multiple, make sure to keep a new one around
					if (multiple && $div.hasClass('new')) {
						$div.clone().addClass('new').removeAttr('id').appendTo($div.parent()).setupUploadForm();
					}

					//adjust dimensions for the parent <form>
					$form.removeClass('new').width(data.result.screenwidth).height(data.result.screenheight);

					//set the image as background on the underlying <div> and resize
					$div.css('backgroundImage', 'url(' + data.result.url + ')')
						.removeClass('new')
						.attr('data-file-id', data.result.file_id)
						.css('lineHeight', data.result.screenheight + 'px')
						.width(data.result.screenwidth)
						.height(data.result.screenheight);

					//update hidden field value that will be passed with this form
					$hidden.setUploadedIds(table_name, field_name);
				}
			});

		}
	});

	//set up image upload <form>s on load
	$('div.form-group.field-image div.image').each(function(){
		$(this).setupUploadForm();
	});

	$('div.form-group.field-images div.image').each(function(){
		$(this).setupUploadForm();
	});

	function randomStr() {
		var m = 36, s = '', r = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		for (var i = 0; i < m; i++) { 
			s += r.charAt(Math.floor(Math.random()*r.length)); 
		}
		return s;
	};

});