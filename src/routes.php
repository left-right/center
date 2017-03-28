<?php
	
Route::group(['prefix' => config('center.prefix'), 'namespace' => 'LeftRight\Center\Controllers'], function(){

	# Will return login screen if not logged in
	Route::get('/', 						'TableController@index');

	# Unprotected login routes
	Route::post('/login', 					'LoginController@postIndex');
	Route::get('/reset',					'LoginController@getReset');
	Route::post('/reset',					'LoginController@postReset');
	Route::get('/change/{email}/{token}',	'LoginController@getChange');
	Route::post('/change',					'LoginController@postChange');

	Route::group(['middleware' => 'user'], function(){
			
		# Special routes
		Route::get('/logout', 'LoginController@logout');
		Route::post('/upload/image', 'FileController@image');
		Route::get('/cleanup', 'FileController@cleanup');
		
		# Instance routing, optionally with linked_id for related objects
		Route::get('/{table_name}/delete/{row_id}',							'RowController@delete');
		Route::get('/{table_name}',											'RowController@index');
		Route::get('/{table_name}/export',									'TableController@export');
		Route::get('/{table_name}/create/{linked_field?}/{linked_row?}',	'RowController@create');
		Route::post('/{table_name}/reorder',								'RowController@reorder');
		Route::post('/{table_name}',										'RowController@store');
		Route::get('/{table_name}/pdf/{row_id}',							'RowController@pdf');
		Route::get('/{table_name}/{row_id}/{linked_field?}/{linked_row?}',	'RowController@edit');
		Route::put('/{table_name}/{row_id}',								'RowController@update');
		Route::delete('/{table_name}/{row_id}', 							'RowController@destroy');
	
	});
	
});