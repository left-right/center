@extends('center::template')

@section('title')
	{{ $table->title }}
@endsection

@section('main')

	{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
		action('\LeftRight\Center\Controllers\TableController@index')=>trans('center::tables.plural'),
		$table->title,
		]) !!}

	<div class="btn-group">
		<a class="btn btn-default" id="create" href="{{ action('\LeftRight\Center\Controllers\RowController@export', $table->name) }}">
			<i class="glyphicon glyphicon-circle-arrow-down"></i>
			@lang('center::rows.export')
		</a>
		@if ($table->user_can_create)
			<a class="btn btn-default" id="create" href="{{ action('\LeftRight\Center\Controllers\RowController@create', $table->name) }}">
				<i class="glyphicon glyphicon-plus"></i>
				@lang('center::rows.create')
			</a>
		@endif
	</div>

	@if (count($instances))
		@if ($table->nested)
			<div class="nested" data-draggable-url="{{ action('\LeftRight\Center\Controllers\RowController@reorder', $table->name) }}">
				<div class="legend">
					Title
					<div class="updated_at">Updated</div>
				</div>
				@include('center::rows.nested', ['instances'=>$instances])
			</div>
		@else
			{!! \LeftRight\Center\Controllers\RowController::table($table, $columns, $instances) !!}
		@endif
	@else
	<div class="alert alert-warning">
		@if ($searching)
			@lang('center::instances.search_empty', ['title'=>strtolower($table->title)])
		@else
			@lang('center::instances.empty', ['title'=>strtolower($table->title)])
		@endif
	</div>
	@endif
@endsection

@section('side')
	{!! Form::open(['method'=>'get', 'id'=>'search']) !!}
	<div class="form-group @if (\Request::has('search')) has_input @endif">
	{!! Form::text('search', Request::input('search'), ['class'=>'form-control', 'placeholder'=>'Search']) !!}
	<i class="glyphicon glyphicon-remove-circle"></i>
	</div>
	@foreach ($filters as $name=>$options)
	<div class="form-group">
		{!! Form::select($name, $options, Request::input($name), ['class'=>'form-control']) !!}
	</div>
	@endforeach
	{!! Form::close() !!}
	<p>{{ nl2br(trans('center::tables.' . $table->name . '.help')) }}</p>
@endsection

@section('script')
	<script>
	/*$(document).keypress(function(e){
		if (e.which == 99) {
			location.href = $("a#create").addClass("active").attr("href");
		}
	});*/

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