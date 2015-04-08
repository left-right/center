@extends('center::template')

@section('title')
	{{ @trans('center::site.home') }}
@endsection

@section('main')

	{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
		trans('center::site.home'),
		]) !!}

	@include('center::notifications')

	{!! \LeftRight\Center\Libraries\Table::rows($objects)
		->column('title', 'string', trans('center::site.table'))
		->column('count', 'integer', trans('center::site.count'))
		->column('updated_name', 'updated_name', trans('center::site.updated_name'))
		->column('updated_at', 'updated_at', trans('center::site.updated_at'))
		->groupBy('list_grouping')
		->draw('tables')
		!!}
	
@endsection

@section('side')
	<p>@lang('center::site.help')</p>
	<p><a href="{{ action('\LeftRight\Center\Controllers\LoginController@logout') }}" class="btn btn-default btn-xs">@lang('center::site.logout')</a>
@endsection