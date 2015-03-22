@extends('center::template')

@section('title')
	@lang('center::messages.objects_create')
@endsection

@section('main')

	{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
		URL::action('\LeftRight\Center\Controllers\ObjectController@index')=>trans('center::messages.objects'),
		trans('center::messages.objects_create'),
		]) !!}

	{{ Form::open(['class'=>'form-horizontal', 'url'=>URL::action('\LeftRight\Center\Controllers\ObjectController@store')]) }}

	<div class="form-group">
		{{ Form::label('title', trans('center::messages.objects_title'), ['class'=>'control-label col-sm-2']) }}
	    <div class="col-sm-10">
			{{ Form::text('title', false, ['class'=>'required form-control', 'autofocus'=>'autofocus']) }}
	    </div>
	</div>

	<div class="form-group">
		{{ Form::label('list_grouping', trans('center::messages.objects_list_grouping'), ['class'=>'control-label col-sm-2']) }}
	    <div class="col-sm-10">
			{{ Form::text('list_grouping', false, ['class'=>'form-control', 'data-provide'=>'typeahead', 'data-source'=>$list_groupings]) }}
	    </div>
	</div>

	<div class="form-group">
		{{ Form::label('order_by', trans('center::messages.objects_order_by'), ['class'=>'control-label col-sm-2']) }}
	    <div class="col-sm-10">
			{{ Form::select('order_by', $order_by, 'precedence', ['class'=>'form-control']) }}
	    </div>
	</div>

	<div class="form-group">
		{{ Form::label('direction', trans('center::messages.objects_direction'), ['class'=>'control-label col-sm-2']) }}
	    <div class="col-sm-10">
			{{ Form::select('direction', $direction, 'asc', ['class'=>'form-control']) }}
	    </div>
	</div>

	<div class="form-group">
	    <div class="col-sm-10 col-sm-offset-2">
			{{ Form::submit(trans('center::messages.site_save'), ['class'=>'btn btn-primary']) }}
			{{ HTML::link(URL::action('\LeftRight\Center\Controllers\ObjectController@index'), trans('center::messages.site_cancel'), ['class'=>'btn btn-default']) }}
	    </div>
	</div>

	{{ Form::close() }}
		
@endsection

@section('side')
	<p>@lang('center::messages.objects_create_help')</p>
@endsection