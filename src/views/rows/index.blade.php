@extends('center::template')

@section('title')
	{{ $table->title }}
@endsection

@section('main')

	{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
		action('\LeftRight\Center\Controllers\TableController@index')=>trans('center::site.home'),
		$table->title,
		]) !!}

	<div class="btn-group">
		@if (\LeftRight\Center\Controllers\LoginController::checkPermission(config('center.db.users'), 'edit'))
		<a class="btn btn-default" href="{{ action('\LeftRight\Center\Controllers\TableController@permissions', $table->name) }}">
			{!! config('center.icons.permissions') !!}
			@lang('center::site.permissions')
		</a>
		@endif
		@if ($table->export)
		<a class="btn btn-default" href="{{ action('\LeftRight\Center\Controllers\TableController@export', $table->name) }}">
			{!! config('center.icons.export') !!}
			@lang('center::site.export')
		</a>
		@endif
		@if ($table->creatable && \LeftRight\Center\Controllers\LoginController::checkPermission($table->name, 'create'))
			<a class="btn btn-default" href="{{ action('\LeftRight\Center\Controllers\RowController@create', $table->name) }}">
				{!! config('center.icons.create') !!}
				@lang('center::site.create')
			</a>
		@endif
	</div>
	
	@include('center::notifications')

	@if (count($rows))
		@if ($table->nested)
			<div class="nested" data-draggable-url="{{ action('\LeftRight\Center\Controllers\RowController@reorder', $table->name) }}">
				<div class="legend">
					Title
					<div class="updated_at">Updated</div>
				</div>
				@include('center::site.nested', ['instances'=>$rows])
			</div>
		@else
			{!! \LeftRight\Center\Controllers\RowController::table($table, $columns, $rows) !!}
			<div class="text-center">
				{!! $rows->appends(Input::all())->render() !!}
			</div>
		@endif
	@else
	<div class="alert alert-warning">
		@if ($searching)
			@lang('center::site.search_empty', ['title'=>strtolower($table->title)])
		@else
			@lang('center::site.empty', ['title'=>strtolower($table->title)])
		@endif
	</div>
	@endif
@endsection

@section('side')
	@if ($table->search || $table->filters)
		{!! Form::open(['method'=>'get', 'id'=>'search']) !!}
		@if ($table->search)
			<div class="form-group @if (\Request::has('search')) has_input @endif">
			{!! Form::text('search', Request::input('search'), ['class'=>'form-control', 'placeholder'=>'Search']) !!}
			<i class="glyphicon glyphicon-remove-circle"></i>
			</div>
		@endif
		@foreach ($filters as $name=>$options)
		<div class="form-group">
			{!! Form::select($name, $options, Request::input($name), ['class'=>'form-control']) !!}
		</div>
		@endforeach
	{!! Form::close() !!}
	@endif
	@if (Lang::has('center::' . $table->name . '.help.index'))
		<p>{!! nl2br(trans('center::' . $table->name . '.help.index')) !!}</p>
	@endif
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