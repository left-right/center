@extends('center::template')

@section('title')
	@lang('center::messages.fields_edit')
@endsection

@section('main')

	{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
		URL::action('\LeftRight\Center\Controllers\ObjectController@index')=>trans('center::messages.objects'),
		URL::action('\LeftRight\Center\Controllers\InstanceController@index', $object->name)=>$object->title,
		URL::action('\LeftRight\Center\Controllers\FieldController@index', $object->name)=>trans('center::messages.fields'),
		trans('center::messages.fields_edit'),
		]) !!}

	{{ Form::open(['class'=>'form-horizontal', 'url'=>URL::action('\LeftRight\Center\Controllers\FieldController@update', [$object->name, $field->id]), 'method'=>'put']) }}
	
	<div class="form-group">
		{{ Form::label('title', trans('center::messages.fields_title'), ['class'=>'control-label col-sm-2']) }}
	    <div class="col-sm-10">
			{{ Form::text('title', $field->title, ['class'=>'required form-control', 'autofocus']) }}
	    </div>
	</div>
	
	<div class="form-group">
		{{ Form::label('name', trans('center::messages.fields_name'), ['class'=>'control-label col-sm-2']) }}
	    <div class="col-sm-10">
			{{ Form::text('name', $field->name, ['class'=>'required form-control']) }}
	    </div>
	</div>

	<div class="form-group">
		{{ Form::label('type', trans('center::messages.fields_type'), ['class'=>'control-label col-sm-2']) }}
	    <div class="col-sm-10">
			{{ Form::select('type', $types, $field->type, ['class'=>'form-control', 'disabled']) }}
	    </div>
	</div>
			
	@if (count($related_objects))
	<div class="form-group">
		{{ Form::label('related_object_id', trans('center::messages.fields_related_object'), ['class'=>'control-label col-sm-2']) }}
	    <div class="col-sm-10">
			{{ Form::select('related_object_id', $related_objects, $field->related_object_id, ['class'=>'form-control']) }}
	    </div>
	</div>
	@endif
	
	@if (count($related_fields))
	<div class="form-group">
		{{ Form::label('related_field_id', trans('center::messages.fields_related_field'), ['class'=>'control-label col-sm-2']) }}
	    <div class="col-sm-10">
			{{ Form::select('related_field_id', $related_fields, $field->related_field_id, ['class'=>'form-control']) }}
	    </div>
	</div>
	@endif
	
	<div class="form-group">
		{{ Form::label('visibility', trans('center::messages.fields_visibility'), ['class'=>'control-label col-sm-2']) }}
	    <div class="col-sm-10">
			{{ Form::select('visibility', $visibility, $field->visibility, ['class'=>'form-control']) }}
	    </div>
	</div>

	<div class="form-group">
		{{ Form::label('width', trans('center::messages.fields_width'), ['class'=>'control-label col-sm-2']) }}
	    <div class="col-sm-10">
			{{ Form::text('width', $field->width, ['class'=>'form-control']) }}
	    </div>
	</div>

	<div class="form-group">
		{{ Form::label('height', trans('center::messages.fields_height'), ['class'=>'control-label col-sm-2']) }}
	    <div class="col-sm-10">
			{{ Form::text('height', $field->height, ['class'=>'form-control']) }}
	    </div>
	</div>
	
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-10">
			<div class="checkbox">
				<label>
					{{ Form::checkbox('required', 'on', $field->required, ['disabled']) }}
					@lang('center::messages.fields_required')
				</label>
			</div>
		</div>
	</div>
	
	<div class="form-group">
	    <div class="col-sm-10 col-sm-offset-2">
			{{ Form::submit(trans('center::messages.site_save'), ['class'=>'btn btn-primary']) }}
			{{ HTML::link(URL::action('\LeftRight\Center\Controllers\FieldController@index', $object->name), trans('center::messages.site_cancel'), ['class'=>'btn btn-default']) }}
	    </div>
	</div>

	{{ Form::close() }}
	
@endsection

@section('side')
	<p>@lang('center::messages.fields_edit_help')</p>

	{{ Form::open(['method'=>'delete', 'action'=>['FieldController@destroy', $object->name, $field->id]]) }}
	<button type="submit" class="btn btn-default btn-xs">@lang('center::messages.fields_destroy')</button>
	{{ Form::close() }}	

@endsection