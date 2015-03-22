@extends('center::template')

@section('title')
	{{ @trans('center::messages.import') }}
@endsection

@section('main')
	{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
		URL::action('\LeftRight\Center\Controllers\ObjectController@index')=>trans('center::messages.objects'),
		trans('center::messages.import'),
		]) !!}

	@if (count($tables))
		{{ Table::rows($tables)
		->column('Name', 'string', trans('center::messages.import_table'))
		->column('Rows', 'integer', trans('center::messages.import_rows'))
		->column('Data_length', 'integer', trans('center::messages.import_size'))
		->draw('tables')
		}}
	@else
	<div class="alert alert-warning">
		@lang('center::messages.import_empty')
	</div>
	@endif
@endsection

@section('side')
	<p>@lang('center::messages.import_help')</p>
@endsection