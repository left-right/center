@extends('center::template')

@section('title')
	@lang('center::users.create')
@endsection

@section('main')

	{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
		URL::action('\LeftRight\Center\Controllers\ObjectController@index')=>trans('center::objects.plural'),
		URL::action('\LeftRight\Center\Controllers\UserController@index')=>trans('center::users.plural'),
		trans('center::users.create'),
		]) !!}

	{!! Form::open(['class'=>'form-horizontal', 'url'=>URL::action('\LeftRight\Center\Controllers\UserController@store')]) !!}
	
	<div class="form-group">
		{!! Form::label('name', trans('center::users.name'), ['class'=>'control-label col-sm-2']) !!}
	    <div class="col-sm-10">
			{!! Form::text('name', false, ['class'=>'form-control required']) !!}
	    </div>
	</div>

	<div class="form-group">
		{!! Form::label('email', trans('center::users.email'), ['class'=>'control-label col-sm-2']) !!}
	    <div class="col-sm-10">
			{!! Form::email('email', false, ['class'=>'form-control required']) !!}
	    </div>
	</div>
	
	<div class="form-group">
		{!! Form::label('role', trans('center::users.role'), ['class'=>'control-label col-sm-2']) !!}
	    <div class="col-sm-10">
	    	@foreach ($roles as $role_id=>$role)
			<div class="radio">
				<label>
					{!! Form::radio('role', $role_id, $role_id == 3) !!}
					<strong>{{ $role }}</strong> &middot; {{ trans('center::users.role_' . Illuminate\Support\Str::slug($role)) }}
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
	<p>@lang('center::users.form_help')</p>
@endsection