@extends('center::template')

@section('title')
	@lang('center::objects.create')
@endsection

@section('main')

	{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
		URL::action('\LeftRight\Center\Controllers\ObjectController@index')=>trans('center::objects.plural'),
		trans('center::objects.create'),
		]) !!}

	{!! Form::open(['class'=>'form-horizontal', 'url'=>URL::action('\LeftRight\Center\Controllers\ObjectController@store')]) !!}

	<div class="form-group">
		{!! Form::label('title', trans('center::objects.title'), ['class'=>'control-label col-sm-2']) !!}
	    <div class="col-sm-10">
			{!! Form::text('title', false, ['class'=>'required form-control', 'autofocus'=>'autofocus']) !!}
	    </div>
	</div>

	<div class="form-group">
		{!! Form::label('list_grouping', trans('center::objects.list_grouping'), ['class'=>'control-label col-sm-2']) !!}
	    <div class="col-sm-10">
			{!! Form::text('list_grouping', false, ['class'=>'form-control', 'data-provide'=>'typeahead', 'data-source'=>$list_groupings]) !!}
	    </div>
	</div>

	<div class="form-group">
		{!! Form::label('order_by', trans('center::objects.order_by'), ['class'=>'control-label col-sm-2']) !!}
	    <div class="col-sm-10">
			{!! Form::select('order_by', $order_by, 'precedence', ['class'=>'form-control']) !!}
	    </div>
	</div>

	<div class="form-group">
		{!! Form::label('direction', trans('center::objects.direction'), ['class'=>'control-label col-sm-2']) !!}
	    <div class="col-sm-10">
			{!! Form::select('direction', $direction, 'asc', ['class'=>'form-control']) !!}
	    </div>
	</div>

	<div class="form-group">
	    <div class="col-sm-10 col-sm-offset-2">
			{!! Form::submit(trans('center::site.save'), ['class'=>'btn btn-primary']) !!}
			{!! HTML::link(URL::action('\LeftRight\Center\Controllers\ObjectController@index'), trans('center::site.cancel'), ['class'=>'btn btn-default']) !!}
	    </div>
	</div>

	{!! Form::close() !!}
		
@endsection

@section('side')
	<p>@lang('center::objects.create_help')</p>
@endsection