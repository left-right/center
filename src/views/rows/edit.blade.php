@extends('center::template')

@section('title')
	@lang('center::site.edit')
@endsection

@section('main')

	@if ($linked_field)
		{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
			URL::action('\LeftRight\Center\Controllers\TableController@index')=>trans('center::site.home'),
			URL::action('\LeftRight\Center\Controllers\RowController@index', $table->fields->{$linked_field}->source)=>config('center.tables.' . $table->fields->{$linked_field}->source)->title,
			URL::action('\LeftRight\Center\Controllers\RowController@edit', [$table->fields->{$linked_field}->source, $linked_row])=>trans('center::site.edit'),
			trans('center::' . $table->name . '.edit'),
			]) !!}
	@else
		{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
			action('\LeftRight\Center\Controllers\TableController@index')=>trans('center::site.home'),
			action('\LeftRight\Center\Controllers\RowController@index', $table->name)=>$table->title,
			trans('center::site.edit'),
			]) !!}
	@endif

	@include('center::notifications')

	{!! Form::open(['class'=>'form-horizontal edit ' . $table->name, 'url'=>action('\LeftRight\Center\Controllers\RowController@update', [$table->name, $row->id]), 'method'=>'put']) !!}
	
	@foreach ($table->fields as $field)
		@if (!$field->hidden)
			@if (view()->exists('center.' . $table->name . '.' . $field->name))
				@include('center.' . $table->name . '.' . $field->name)
			@else
				@include('center::fields.' . $field->type)
			@endif
		@endif
	@endforeach
	
	<div class="form-group">
		<div class="col-sm-10 col-sm-offset-2">
			{!! Form::submit(trans('center::site.save'), ['class'=>'btn btn-primary']) !!}
			{!! HTML::link(\LeftRight\Center\Libraries\Trail::last(action('\LeftRight\Center\Controllers\RowController@index', $table->name)), trans('center::site.cancel'), ['class'=>'btn btn-default']) !!}
		</div>
	</div>

	{!! Form::close() !!}

	@foreach ($links as $link)

	<div class="related">
		<h3>{{ $link['table']->title }}</h3>

		<div class="btn-group">
			<a class="btn btn-default" id="create" href="{{ action('\LeftRight\Center\Controllers\RowController@create', [$link['table']->name, $link['linked_field'], $link['linked_row']]) }}">
				{!! config('center.icons.create') !!}
				@lang('center::site.create')
			</a>
		</div>
		
		{!! LeftRight\Center\Controllers\RowController::table($link['table'], $link['columns'], $link['rows']) !!}
	</div>
	
	@endforeach

@endsection

@section('side')

	@if (Lang::has('center::' . $table->name . '.help.edit'))
		<p>{!! nl2br(trans('center::' . $table->name . '.help.edit')) !!}</p>
	@endif

	@if ($table->deletable)
	{!! Form::open(['method'=>'delete', 'action'=>['\LeftRight\Center\Controllers\RowController@destroy', $table->name, $row->id]]) !!}
		{!! Form::submit(trans('center::site.destroy'), ['class'=>'btn btn-default btn-xs']) !!}
	{!! Form::close() !!}
	@endif

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