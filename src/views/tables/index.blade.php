@extends('center::template')

@section('title')
	{{ @trans('center::tables.plural') }}
@endsection

@section('main')

	{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
		trans('center::tables.plural'),
		]) !!}

	@if (Auth::user()->admin)
		<div class="btn-group">
			<a class="btn btn-default" href="{{ URL::action('\LeftRight\Center\Controllers\TableController@refresh') }}">
				<i class="glyphicon glyphicon-refresh"></i>
				@lang('center::tables.refresh')
			</a>
		</div>
	@endif
	
	@include('center::notifications')

	@if (count($objects))
		{!! \LeftRight\Center\Libraries\Table::rows($objects)
			->column('title', 'string', trans('center::tables.singular'))
			//->column('count', 'integer', trans('center::tables.count'))
			//->column('updated_name', 'updated_name', trans('center::site.updated_name'))
			//->column('updated_at', 'updated_at', trans('tables::site.updated_at'))
			//->groupBy('list_grouping')
			->draw('tables')
			!!}
	@else
		<div class="alert alert-warning">
			@lang('center::tables.empty')
		</div>
	@endif
	
@endsection

@section('side')
	<p>@lang('center::tables.help')</p>
	<p><a href="{{ URL::action('\LeftRight\Center\Controllers\LoginController@getLogout') }}" class="btn btn-default btn-xs">@lang('center::site.logout')</a>
@endsection