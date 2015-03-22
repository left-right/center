@extends('center::template')

@section('title')
	@lang('center::fields.create')
@endsection

@section('main')

	{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
		URL::action('\LeftRight\Center\Controllers\ObjectController@index')=>trans('center::objects.plural'),
		URL::action('\LeftRight\Center\Controllers\InstanceController@index', $object->name)=>$object->title,
		URL::action('\LeftRight\Center\Controllers\FieldController@index', $object->name)=>trans('center::fields.plural'),
		trans('center::fields.create'),
		]) !!}

	{{ Form::open(['class'=>'form-horizontal', 'url'=>URL::action('\LeftRight\Center\Controllers\FieldController@store', $object->name)]) }}
	
	<div class="form-group">
		{{ Form::label('title', trans('center::fields.title'), ['class'=>'control-label col-sm-2']) }}
	    <div class="col-sm-10">
			{{ Form::text('title', null, ['class'=>'required form-control', 'autofocus']) }}
	    </div>
	</div>

	<div class="form-group">
		{{ Form::label('type', trans('center::fields.type'), ['class'=>'control-label col-sm-2']) }}
	    <div class="col-sm-10">
			{{ Form::select('type', $types, 'string', ['class'=>'form-control']) }}
	    </div>
	</div>
	
	@if (count($related_objects))
	<div class="form-group">
		{{ Form::label('related_object_id', trans('center::fields.related_object'), ['class'=>'control-label col-sm-2']) }}
	    <div class="col-sm-10">
			{{ Form::select('related_object_id', $related_objects, null, ['class'=>'form-control']) }}
	    </div>
	</div>
	@endif
	
	@if (count($related_fields))
	<div class="form-group">
		{{ Form::label('related_field_id', trans('center::fields.related_field'), ['class'=>'control-label col-sm-2']) }}
	    <div class="col-sm-10">
			{{ Form::select('related_field_id', $related_fields, null, ['class'=>'form-control']) }}
	    </div>
	</div>
	@endif
	
	<div class="form-group">
		{{ Form::label('width', trans('center::fields.width'), ['class'=>'control-label col-sm-2']) }}
	    <div class="col-sm-10">
			{{ Form::text('width', null, ['class'=>'form-control']) }}
	    </div>
	</div>

	<div class="form-group">
		{{ Form::label('height', trans('center::fields.height'), ['class'=>'control-label col-sm-2']) }}
	    <div class="col-sm-10">
			{{ Form::text('height', null, ['class'=>'form-control']) }}
	    </div>
	</div>

	<div class="form-group">
		{{ Form::label('visibility', trans('center::fields.visibility'), ['class'=>'control-label col-sm-2']) }}
	    <div class="col-sm-10">
			{{ Form::select('visibility', $visibility, 'normal', ['class'=>'form-control']) }}
	    </div>
	</div>

	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-10">
			<div class="checkbox">
				<label>
					{{ Form::checkbox('required') }}
					@lang('center::fields.required')
				</label>
			</div>
		</div>
	</div>

	<div class="form-group">
	    <div class="col-sm-10 col-sm-offset-2">
			{{ Form::submit(trans('center::site.save'), ['class'=>'btn btn-primary']) }}
			{{ HTML::link(URL::action('\LeftRight\Center\Controllers\FieldController@index', $object->name), trans('center::site.cancel'), ['class'=>'btn btn-default']) }}
	    </div>
	</div>
	
	{{ Form::close() }}
	
@endsection

@section('side')
	<p>@lang('center::fields.create_help')</p>
@endsection