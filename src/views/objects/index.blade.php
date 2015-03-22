@extends('center::template')

@section('title')
	{{ @trans('center::objects.plural') }}
@endsection

@section('main')

	{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
		trans('center::objects.plural'),
		]) !!}

	@if (Auth::user()->role < 3)
	<div class="btn-group">
		<a class="btn btn-default" href="{{ URL::action('\LeftRight\Center\Controllers\UserController@index') }}">
			<i class="glyphicon glyphicon-user"></i>
			@lang('center::users.plural')
		</a>
		@if (Auth::user()->role < 2)
		<a class="btn btn-default" href="{{ URL::action('\LeftRight\Center\Controllers\ImportController@index') }}">
			<i class="glyphicon glyphicon-list-alt"></i>
			@lang('center::import.import')
		</a>
		<a class="btn btn-default" href="{{ URL::action('\LeftRight\Center\Controllers\ObjectController@create') }}">
			<i class="glyphicon glyphicon-plus"></i>
			@lang('center::objects.create')
		</a>
		@endif
	</div>
	@endif

	@if (count($objects))
		{!! \LeftRight\Center\Libraries\Table::rows($objects)
			->column('title', 'string', trans('center::objects.singular'))
			->column('count', 'integer', trans('center::objects.count'))
			->column('updated_name', 'updated_name', trans('center::site.updated_name'))
			->column('updated_at', 'updated_at', trans('center::site.updated_at'))
			->groupBy('list_grouping')
			->draw('objects')
			!!}
	@else
	<div class="alert alert-warning">
		@lang('center::objects.empty')
	</div>
	@endif

@endsection

@section('side')
	<p>@lang('center::objects.help')</p>
	<p><a href="{{ URL::action('\LeftRight\Center\Controllers\LoginController@getLogout') }}" class="btn btn-default btn-xs">@lang('center::site.logout')</a>
@endsection