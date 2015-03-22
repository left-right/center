@extends('center::template')

@section('title')
	@lang('center::users.plural_edit')
@endsection

@section('main')

	{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
		URL::action('\LeftRight\Center\Controllers\ObjectController@index')=>trans('center::objects.plural'),
		URL::action('\LeftRight\Center\Controllers\UserController@index')=>trans('center::users.plural'),
		trans('center::users.plural_edit'),
		]) !!}

	{!! Form::open(['class'=>'form-horizontal', 'url'=>URL::action('\LeftRight\Center\Controllers\UserController@update', $user->id), 'method'=>'put']) !!}

	<div class="form-group">
		{!! Form::label('name', trans('center::users.plural_name'), ['class'=>'control-label col-sm-2']) !!}
	    <div class="col-sm-10">
			{!! Form::text('name', $user->name, ['class'=>'form-control required']) !!}
	    </div>
	</div>
	
	<div class="form-group">
		{!! Form::label('email', trans('center::users.plural_email'), ['class'=>'control-label col-sm-2']) !!}
	    <div class="col-sm-10">
			{!! Form::email('email', $user->email, ['class'=>'form-control required']) !!}
	    </div>
	</div>
	
	<div class="form-group">
		{!! Form::label('role', trans('center::users.plural_role'), ['class'=>'control-label col-sm-2']) !!}
	    <div class="col-sm-10">
	    	@foreach ($roles as $role_id=>$role)
			<div class="radio">
				<label>
					{!! Form::radio('role', $role_id, $role_id == $user->role) !!}
					<strong>{{ $role }}</strong> &middot; {{ trans('center::users.plural_role_' . Illuminate\Support\Str::slug($role)) }}
				</label>
			</div>
			@endforeach
		</div>
	</div>

	<div class="form-group">
	    <div class="col-sm-10 col-sm-offset-2">
			{!! Form::submit(trans('center::site.save'), ['class'=>'btn btn-primary']) !!}
			{!! HTML::link(URL::action('\LeftRight\Center\Controllers\UserController@index'), trans('center::site.cancel'), ['class'=>'btn btn-default']) !!}
	    </div>
	</div>
	
	{!! Form::close() !!}
		
@endsection

@section('side')
	<p>@lang('center::users.plural_edit_help')</p>

	<p><a href="{{ URL::action('\LeftRight\Center\Controllers\UserController@resendWelcome', $user->id) }}" class="btn btn-default btn-xs">@lang('center::users.plural_welcome_resend')</a></p>

	@if (!$user->last_login)
	{!! Form::open(['method'=>'delete', 'action'=>['\LeftRight\Center\Controllers\UserController@destroy', $user->id]]) !!}
	<button type="submit" class="btn btn-default btn-xs">{{ trans('center::users.plural_destroy') }}</button>
	{!! Form::close() !!}
	@endif
@endsection