@extends('center::template')

@section('title')
	{{ @trans('center::site.home') }}
@endsection

@section('main')

	{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
		action('\LeftRight\Center\Controllers\TableController@index')=>trans('center::site.home'),
		action('\LeftRight\Center\Controllers\RowController@index', $table->name)=>$table->title,
		trans('center::site.permissions'),
		]) !!}
	
	@include('center::notifications')

	{!! Form::open(['class'=>'form-horizontal', 'url'=>action('\LeftRight\Center\Controllers\TableController@savePermissions', $table->name), 'method'=>'put']) !!}

	@foreach ($users as $user)
		<div class="form-group">
			<label class="control-label col-sm-2">{{ $user->name }}</label>
			<div class="col-sm-10">
				<div class="input-group">
					{!! Form::select('permissions[' . $user->id . ']', $permission_levels, $user->level, ['class'=>'form-control']) !!}
				</div>
			</div>
		</div>
	@endforeach

	<div class="form-group">
		<div class="col-sm-10 col-sm-offset-2">
			{!! Form::submit(trans('center::site.save'), ['class'=>'btn btn-primary']) !!}
			{!! HTML::link(action('\LeftRight\Center\Controllers\RowController@index', $table->name), trans('center::site.cancel'), ['class'=>'btn btn-default']) !!}
		</div>
	</div>
		
	{!! Form::close() !!}
@endsection

@section('side')
	<p>@lang('center::site.permissions_help')</p>
@endsection