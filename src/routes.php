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
		Route::get('cleanup', function(){
			FileController::findOrphans();
			FileController::cleanup();
		});		
		
		# Test routes
		Route::group(['prefix' => 'test'], function(){ 
			Route::get('/image', 'FileController@test');
			Route::get('/slug', function(){
				$phrases = [
					'',
					'and',
					'this is a normal test',
					'this is a really really really long test because it\'s amazing and great and am i at 50 YET???',
				];
				foreach ($phrases as $phrase) {
					echo '<p>' . $phrase . ' becomes <em>' . Slug::make($phrase, ['', 'normal-test', 'normal-test-1']) . '</em></p>';
				}
			});
			Route::get('/slug/object/{object_id}', function($object_id){
				$object = DB::table(DB_OBJECTS)->find($object_id);
				Slug::setForObject($object);
				die('object was ' . $object->name);
			});
		});

		# Instance routing, optionally with linked_id for related objects
		Route::get('/{table_name}/delete/{row_id}',							'RowController@delete');
		Route::get('/{table_name}',											'RowController@index');
		Route::get('/{table_name}/export',									'RowController@export');
		Route::get('/{table_name}/permissions',								'TableController@permissions');
		Route::put('/{table_name}/permissions',								'TableController@savePermissions');
		Route::get('/{table_name}/create/{linked_field?}/{linked_row?}',	'RowController@create');
		Route::post('/{table_name}/reorder',								'RowController@reorder');
		Route::post('/{table_name}',										'RowController@store');
		Route::get('/{table_name}/{row_id}/{linked_field?}/{linked_row?}',	'RowController@edit');
		Route::put('/{table_name}/{row_id}',								'RowController@update');
		Route::delete('/{table_name}/{row_id}', 							'RowController@destroy');
	
	});
	
});