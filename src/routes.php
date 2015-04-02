<?php
	
Route::group(['prefix' => config('center.prefix'), 'namespace' => 'LeftRight\Center\Controllers'], function(){

	# Will return login screen if not logged in
	Route::get('/', ['as'=>'home', 'uses'=>'TableController@index']);

	# Unprotected login routes
	Route::post('/login', 					'LoginController@postIndex');
	Route::get('/reset',					'LoginController@getReset');
	Route::post('/reset',					'LoginController@postReset');
	Route::get('/change/{email}/{token}',	'LoginController@getChange');
	Route::post('/change',					'LoginController@postChange');

	Route::group(['middleware' => 'user'], function(){
			
		# Special routes
		Route::get('/refresh', 'TableController@refresh');
		Route::get('/logout', 'LoginController@getLogout');
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
		Route::get('/{object_name}/delete/{instance_id}',		'RowController@delete');
		Route::get('/{object_name}',							'RowController@index');
		Route::get('/{object_name}/export',						'RowController@export');
		Route::get('/{object_name}/create/{linked_id?}',		'RowController@create');
		Route::post('/{object_name}/reorder',					'RowController@reorder');
		Route::post('/{object_name}/{linked_id?}',				'RowController@store');
		Route::get('/{object_name}/{instance_id}/{linked_id?}',	'RowController@edit');
		Route::put('/{object_name}/{instance_id}/{linked_id?}',	'RowController@update');
		Route::delete('/{object_name}/{instance_id}', 			'RowController@destroy');
	
		
	});
	
});