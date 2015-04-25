@extends('center::template')

@section('title')
	{{ @trans('center::site.home') }}
@endsection

@section('main')

	{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
		trans('center::site.home'),
		]) !!}

	@include('center::notifications')

	{!! $table !!}
	
@endsection

@section('side')
	<p>@lang('center::site.help')</p>
	<p><a href="{{ action('\LeftRight\Center\Controllers\LoginController@logout') }}" class="btn btn-default btn-xs">@lang('center::site.logout')</a>
@endsection