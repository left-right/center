@extends('center::template')

@section('title')
	{{ @trans('center::import.import') }}
@endsection

@section('main')
	{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
		URL::action('\LeftRight\Center\Controllers\ObjectController@index')=>trans('center::objects.plural'),
		URL::action('ImportController@index')=>trans('center::import.import'),
		$table,
		]) !!}

	@if (!empty($html))
		{{ $html }}
	@else
	<div class="alert alert-warning">
		@lang('center::import.import_table_empty')
	</div>
	@endif
@endsection

@section('side')
	<p>@lang('center::import.import_table_help')</p>
	<p><a href="{{ URL::action('ImportController@drop', $table) }}" class="btn btn-default btn-xs">@lang('center::import.import_table_drop')</a>

@endsection