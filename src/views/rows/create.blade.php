@extends('center::template')

@section('title')
	@lang('center::site.create')
@endsection

@section('main')

	@if ($linked_field && $linked_row)
		{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
			URL::action('\LeftRight\Center\Controllers\TableController@index')=>trans('center::site.home'),
			URL::action('\LeftRight\Center\Controllers\RowController@index', $table->fields->{$linked_field}->source)=>config('center.tables.' . $table->fields->{$linked_field}->source)->title,
			URL::action('\LeftRight\Center\Controllers\RowController@edit', [$table->fields->{$linked_field}->source, $linked_row])=>trans('center::site.edit'),
			trans('center::' . $table->name . '.create'),
			]) !!}
	@else
		{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
			URL::action('\LeftRight\Center\Controllers\TableController@index')=>trans('center::site.home'),
			URL::action('\LeftRight\Center\Controllers\RowController@index', $table->name)=>$table->title,
			trans('center::site.create'),
			]) !!}
	@endif

	@include('center::notifications')

	{!! Form::open(['class'=>'form-horizontal create ' . $table->name, 'url'=>URL::action('\LeftRight\Center\Controllers\RowController@store', $table->name)]) !!}
	
	@foreach ($table->fields as $field)
		@if ($linked_row && ($field->name == $linked_field))
			{!! Form::hidden($field->name, $linked_row) !!}
		@elseif (!$field->hidden)
			@include('center::fields.' . $field->type)
		@endif
	@endforeach
	
	<div class="form-group">
		<div class="col-sm-10 col-sm-offset-2">
			{!! Form::submit(trans('center::site.save'), ['class'=>'btn btn-primary']) !!}
			{!! HTML::link(\LeftRight\Center\Libraries\Trail::last(action('\LeftRight\Center\Controllers\RowController@index', $table->name)), trans('center::site.cancel'), ['class'=>'btn btn-default']) !!}
		</div>
	</div>

	{!! Form::close() !!}

@endsection

@section('side')
	@if (Lang::has('center::' . $table->name . '.help.create'))
		<p>{!! nl2br(trans('center::' . $table->name . '.help.create')) !!}</p>
	@endif
@endsection