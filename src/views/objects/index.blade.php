@extends('center::template')

@section('title')
	{{ @trans('center::messages.objects') }}
@endsection

@section('main')

	{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
		trans('center::messages.objects'),
		]) !!}

	@if (Auth::user()->role < 3)
	<div class="btn-group">
		<a class="btn btn-default" href="{{ URL::action('\LeftRight\Center\Controllers\UserController@index') }}">
			<i class="glyphicon glyphicon-user"></i>
			@lang('center::messages.users')
		</a>
		@if (Auth::user()->role < 2)
		<a class="btn btn-default" href="{{ URL::action('\LeftRight\Center\Controllers\ImportController@index') }}">
			<i class="glyphicon glyphicon-list-alt"></i>
			@lang('center::messages.import')
		</a>
		<a class="btn btn-default" href="{{ URL::action('\LeftRight\Center\Controllers\ObjectController@create') }}">
			<i class="glyphicon glyphicon-plus"></i>
			@lang('center::messages.objects_create')
		</a>
		@endif
	</div>
	@endif

	@if (count($objects))
		{!! \LeftRight\Center\Libraries\Table::rows($objects)
			->column('title', 'string', trans('center::messages.object'))
			->column('count', 'integer', trans('center::messages.objects_count'))
			->column('updated_name', 'updated_name', trans('center::messages.site_updated_name'))
			->column('updated_at', 'updated_at', trans('center::messages.site_updated_at'))
			->groupBy('list_grouping')
			->draw('objects')
			!!}
	@else
	<div class="alert alert-warning">
		@lang('center::messages.objects_empty')
	</div>
	@endif

@endsection

@section('side')
	<p>@lang('center::messages.objects_help')</p>
	<p><a href="{{ URL::action('\LeftRight\Center\Controllers\LoginController@getLogout') }}" class="btn btn-default btn-xs">@lang('center::messages.site_logout')</a>
@endsection