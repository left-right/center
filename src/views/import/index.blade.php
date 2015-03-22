@extends('center::template')

@section('title')
	{{ @trans('center::import.import') }}
@endsection

@section('main')
	{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
		URL::action('\LeftRight\Center\Controllers\ObjectController@index')=>trans('center::objects.plural'),
		trans('center::import.import'),
		]) !!}

	@if (count($tables))
		{{ Table::rows($tables)
		->column('Name', 'string', trans('center::import.table'))
		->column('Rows', 'integer', trans('center::import.rows'))
		->column('Data_length', 'integer', trans('center::import.size'))
		->draw('tables')
		}}
	@else
	<div class="alert alert-warning">
		@lang('center::import.empty')
	</div>
	@endif
@endsection

@section('side')
	<p>@lang('center::import.help')</p>
@endsection