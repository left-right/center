@extends('center::template')

@section('title')
	{{ $object->title }}
@endsection

@section('main')

	{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
		URL::action('\LeftRight\Center\Controllers\ObjectController@index')=>trans('center::messages.objects'),
		$object->title,
		]) !!}

	<div class="btn-group">
		@if (Auth::user()->role < 2)
			<a class="btn btn-default" href="{{ URL::action('\LeftRight\Center\Controllers\ObjectController@edit', $object->name) }}">
				<i class="glyphicon glyphicon-cog"></i> 
				@lang('center::messages.objects_edit', ['title'=>$object->title])
			</a>
			<a class="btn btn-default" href="{{ URL::action('\LeftRight\Center\Controllers\FieldController@index', $object->name) }}">
				<i class="glyphicon glyphicon-list"></i>
				@lang('center::messages.fields')
			</a>
		@endif
		<a class="btn btn-default" id="create" href="{{ URL::action('\LeftRight\Center\Controllers\InstanceController@export', $object->name) }}">
			<i class="glyphicon glyphicon-circle-arrow-down"></i>
			@lang('center::messages.instances_export')
		</a>
		@if ($object->can_create)
			<a class="btn btn-default" id="create" href="{{ URL::action('\LeftRight\Center\Controllers\InstanceController@create', $object->name) }}">
				<i class="glyphicon glyphicon-plus"></i>
				@lang('center::messages.instances_create')
			</a>
		@endif
	</div>

	@if (count($instances))
		@if ($object->nested)
			<div class="nested" data-draggable-url="{{ URL::action('\LeftRight\Center\Controllers\InstanceController@reorder', $object->name) }}">
				<div class="legend">
					Title
					<div class="updated_at">Updated</div>
				</div>
				@include('center::instances.nested', ['instances'=>$instances])
			</div>
		@else
			{!! \LeftRight\Center\Controllers\InstanceController::table($object, $columns, $instances) !!}
		@endif
	@else
	<div class="alert alert-warning">
		@if ($searching)
			@lang('center::messages.instances_search_empty', ['title'=>strtolower($object->title)])
		@else
			@lang('center::messages.instances_empty', ['title'=>strtolower($object->title)])
		@endif
	</div>
	@endif
		
@endsection

@section('side')
	{!! Form::open(['method'=>'get', 'id'=>'search']) !!}
	<div class="form-group @if (Input::has('search')) has_input @endif">
	{!! Form::text('search', Request::input('search'), ['class'=>'form-control', 'placeholder'=>'Search']) !!}
	<i class="glyphicon glyphicon-remove-circle"></i>
	</div>
	@foreach ($filters as $name=>$options)
	<div class="form-group">
		{!! Form::select($name, $options, Request::input($name), ['class'=>'form-control']) !!}
	</div>
	@endforeach
	{!! Form::close() !!}
	<p>{{ nl2br($object->list_help) }}</p>
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