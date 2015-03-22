@extends('center::template')

@section('title')
	@lang('center::messages.users_create')
@endsection

@section('main')

	{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
		URL::action('\LeftRight\Center\Controllers\ObjectController@index')=>trans('center::messages.objects'),
		URL::action('UserController@index')=>trans('center::messages.users'),
		trans('center::messages.users_create'),
		]) !!}

	{{ Form::open(['class'=>'form-horizontal', 'url'=>URL::action('UserController@store')]) }}
	
	<div class="form-group">
		{{ Form::label('name', trans('center::messages.users_name'), ['class'=>'control-label col-sm-2']) }}
	    <div class="col-sm-10">
			{{ Form::text('name', false, ['class'=>'form-control required']) }}
	    </div>
	</div>

	<div class="form-group">
		{{ Form::label('email', trans('center::messages.users_email'), ['class'=>'control-label col-sm-2']) }}
	    <div class="col-sm-10">
			{{ Form::email('email', false, ['class'=>'form-control required']) }}
	    </div>
	</div>
	
	<div class="form-group">
		{{ Form::label('role', trans('center::messages.users_role'), ['class'=>'control-label col-sm-2']) }}
	    <div class="col-sm-10">
	    	@foreach ($roles as $role_id=>$role)
			<div class="radio">
				<label>
					{{ Form::radio('role', $role_id, $role_id == 3) }}
					<strong>{{ $role }}</strong> &middot; {{ trans('center::messages.users_role_' . Str::slug($role)) }}
				</label>
			</div>
			@endforeach
		</div>
	</div>

	<div class="form-group">
	    <div class="col-sm-10 col-sm-offset-2">
			{{ Form::submit(trans('center::messages.site_save'), ['class'=>'btn btn-primary']) }}
			{{ HTML::link(URL::action('UserController@index'), trans('center::messages.site_cancel'), ['class'=>'btn btn-default']) }}
	    </div>
	</div>
	
	{{ Form::close() }}
		
@endsection

@section('side')
	<p>@lang('center::messages.users_form_help')</p>
@endsection