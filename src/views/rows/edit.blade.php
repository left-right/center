@extends('center::template')

@section('title')
	@lang('center::site.edit')
@endsection

@section('main')

	{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
		action('\LeftRight\Center\Controllers\TableController@index')=>trans('center::site.home'),
		action('\LeftRight\Center\Controllers\RowController@index', $table->name)=>$table->title,
		trans('center::site.edit'),
		]) !!}

	{!! Form::open(['class'=>'form-horizontal ' . $table->name, 'url'=>action('\LeftRight\Center\Controllers\RowController@update', [$table->name, $row->id, $linked_id]), 'method'=>'put']) !!}
	
		{!! Form::hidden('return_to', $return_to) !!}

	@foreach ($table->fields as $field)
		{{--
		@if ($linked_id && $field->id == $object->group_by_field)
			{!! Form::hidden($field->name, $linked_id) !!}
		@else
		--}}
		@if (!$field->hidden)
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

	@foreach ($links as $link)

	<div class="related">
		<h3>{{ $link['object']->title }}</h3>

		<div class="btn-group">
			<a class="btn btn-default" id="create" href="{{ action('\LeftRight\Center\Controllers\RowController@create', [$link['object']->name, $row->id]) }}">
				<i class="glyphicon glyphicon-plus"></i> 
				@lang('center::site.create')
			</a>
		</div>
		
		{!! LeftRight\Center\Controllers\RowController::table($link['object'], $link['columns'], $link['instances']) !!}
	</div>
	
	@endforeach

@endsection

@section('side')
	@if (Lang::has('center::' . $table->name . '.help.edit'))
		<p>{!! nl2br(trans('center::' . $table->name . '.help.edit')) !!}</p>
	@endif

	{!! Form::open(['method'=>'delete', 'action'=>['\LeftRight\Center\Controllers\RowController@destroy', $table->name, $row->id]]) !!}
		{!! Form::hidden('return_to', $return_to) !!}
		{!! Form::submit(trans('center::site.destroy'), ['class'=>'btn btn-default btn-xs']) !!}
	{!! Form::close() !!}

@endsection

@section('script')
	<script>
	@if (Session::has('instance_id'))
		var $el = $("table tr#{{ Session::get('instance_id') }}");
		$el
			.after("<div class='highlight'/>")
			.next()
			.width($el.width())
			.height($el.height())
			.css("marginTop", -$el.height())
			.fadeOut(500, function(){
				$("div.highlight").remove();
			});
	@endif
	</script>
@endsection