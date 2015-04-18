@extends('center::template')

@section('title')
	@lang('center::site.create')
@endsection

@section('main')

	{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
		URL::action('\LeftRight\Center\Controllers\TableController@index')=>trans('center::site.home'),
		URL::action('\LeftRight\Center\Controllers\RowController@index', $table->name)=>$table->title,
		trans('center::site.create'),
		]) !!}

	@include('center::notifications')

	{!! Form::open(['class'=>'form-horizontal', 'url'=>URL::action('\LeftRight\Center\Controllers\RowController@store', [$table->name, $linked_id])]) !!}
	
	{!! Form::hidden('return_to', $return_to) !!}

	@foreach ($table->fields as $field)
		@if ($linked_id && $field->id == $table->group_by_field)
			{!! Form::hidden($field->name, $linked_id) !!}
		@elseif (!$field->hidden && $field->type != 'slug')
			@include('center::fields.' . $field->type)
		@endif
	@endforeach
	
	<div class="form-group">
		<div class="col-sm-10 col-sm-offset-2">
			{!! Form::submit(trans('center::site.save'), ['class'=>'btn btn-primary']) !!}
			{!! HTML::link($return_to, trans('center::site.cancel'), ['class'=>'btn btn-default']) !!}
		</div>
	</div>

	{!! Form::close() !!}

@endsection

@section('side')
	@if (Lang::has('center::' . $table->name . '.help.create'))
		<p>{!! nl2br(trans('center::' . $table->name . '.help.create')) !!}</p>
	@endif
@endsection