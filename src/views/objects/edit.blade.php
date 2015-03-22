@extends('center::template')

@section('title')
	@lang('center::messages.objects_edit', ['title'=>$object->title])
@endsection

@section('main')

	{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
		URL::action('\LeftRight\Center\Controllers\ObjectController@index')=>trans('center::messages.objects'),
		URL::action('\LeftRight\Center\Controllers\InstanceController@index', $object->name)=>$object->title,
		trans('center::messages.objects_edit'),
		]) !!}

	{{ Form::open(['class'=>'form-horizontal', 'url'=>URL::action('\LeftRight\Center\Controllers\ObjectController@update', $object->name), 'method'=>'put']) }}
	
	<div class="form-group">
		{{ Form::label('title', trans('center::messages.objects_title'), ['class'=>'control-label col-sm-2']) }}
	    <div class="col-sm-10">
			{{ Form::text('title', $object->title, ['class'=>'required form-control', 'autofocus'=>'autofocus']) }}
	    </div>
	</div>
	
	<div class="form-group">
		{{ Form::label('list_grouping', trans('center::messages.objects_list_grouping'), ['class'=>'control-label col-sm-2']) }}
	    <div class="col-sm-10">
			{{ Form::text('list_grouping', $object->list_grouping, ['class'=>'form-control', 'data-provide'=>'typeahead', 'data-source'=>$list_groupings]) }}
	    </div>
	</div>
		
	<div class="form-group">
		{{ Form::label('name', trans('center::messages.objects_name'), ['class'=>'control-label col-sm-2']) }}
	    <div class="col-sm-10">
			{{ Form::text('name', $object->name, ['class'=>'required form-control']) }}
	    </div>
	</div>
		
	<div class="form-group">
		{{ Form::label('model', trans('center::messages.objects_model'), ['class'=>'control-label col-sm-2']) }}
	    <div class="col-sm-10">
			{{ Form::text('model', $object->model, ['class'=>'required form-control']) }}
	    </div>
	</div>
		
	<div class="form-group">
		{{ Form::label('url', trans('center::messages.objects_url'), ['class'=>'control-label col-sm-2']) }}
	    <div class="col-sm-10">
			{{ Form::text('url', $object->url, ['class'=>'form-control']) }}
	    </div>
	</div>
			
	<div class="form-group">
		{{ Form::label('order_by', trans('center::messages.objects_order_by'), ['class'=>'control-label col-sm-2']) }}
		<div class="col-sm-10">
			{{ Form::select('order_by', $order_by, $object->order_by, ['class'=>'form-control']) }}
		</div>
	</div>
	
	<div class="form-group">
		{{ Form::label('direction', trans('center::messages.objects_direction'), ['class'=>'control-label col-sm-2']) }}
		<div class="col-sm-10">
			{{ Form::select('direction', $direction, $object->direction, ['class'=>'form-control']) }}
		</div>
	</div>
		
	<!-- (not implemented yet)
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-10">
			<div class="checkbox">
				<label>
					{{ Form::checkbox('singleton', 'on', $object->singleton) }}
					@lang('center::messages.objects_singleton')
				</label>
			</div>
		</div>
	</div>
	-->

	@if (count($group_by_field))
	<div class="form-group">
		{{ Form::label('group_by_field', trans('center::messages.objects_group_by'), ['class'=>'control-label col-sm-2']) }}
		<div class="col-sm-10">
			{{ Form::select('group_by_field', $group_by_field, $object->group_by_field, ['class'=>'form-control']) }}
		</div>
	</div>
	@endif

	@if (count($related_objects))
	<div class="form-group">
		{{ Form::label('group_by_field', trans('center::messages.objects_related'), ['class'=>'control-label col-sm-2']) }}
		<div class="col-sm-10">
			@foreach ($related_objects as $related_object_id=>$related_object_title)
			<label class="checkbox-inline">
				{{ Form::checkbox('related_objects[]', $related_object_id, in_array($related_object_id, $links)) }} {{ $related_object_title }}
			</label>
			@endforeach
		</div>
	</div>
	@endif
	
	<div class="form-group">
		{{ Form::label('permissions', trans('center::messages.objects_permissions'), ['class'=>'control-label col-sm-2']) }}
		<div class="col-sm-10">
			<div class="checkbox">
				<label>
					{{ Form::checkbox('can_see', 'on', $object->can_see) }}
					@lang('center::messages.objects_can_see')
				</label>
			</div>
			<div class="checkbox">
				<label>
					{{ Form::checkbox('can_create', 'on', $object->can_create) }}
					@lang('center::messages.objects_can_create')
				</label>
			</div>
			<div class="checkbox">
				<label>
					{{ Form::checkbox('can_edit', 'on', $object->can_edit) }}
					@lang('center::messages.objects_can_edit')
				</label>
			</div>
		</div>
	</div>

	<div class="form-group">
		{{ Form::label('list_help', trans('center::messages.objects_list_help'), ['class'=>'control-label col-sm-2']) }}
		<div class="col-sm-10">
			{{ Form::textarea('list_help', $object->list_help, ['class'=>'form-control']) }}
		</div>
	</div>

	<div class="form-group">
		{{ Form::label('form_help', trans('center::messages.objects_form_help'), ['class'=>'control-label col-sm-2']) }}
		<div class="col-sm-10">
			{{ Form::textarea('form_help', $object->form_help, ['class'=>'form-control']) }}
		</div>
	</div>

	<div class="form-group">
	    <div class="col-sm-10 col-sm-offset-2">
			{{ Form::submit(trans('center::messages.site_save'), ['class'=>'btn btn-primary']) }}
			{{ HTML::link(URL::action('\LeftRight\Center\Controllers\InstanceController@index', $object->name), trans('center::messages.site_cancel'), ['class'=>'btn btn-default']) }}
	    </div>
	</div>

	{{ Form::close() }}
	
@endsection

@section('side')
	
	<p>@lang('center::messages.objects_edit_help', ['title'=>$object->title])</p>

	@if (!$dependencies)
		{{ Form::open(['method'=>'delete', 'action'=>['ObjectController@destroy', $object->name]]) }}
		<button type="submit" class="btn btn-default btn-xs">@lang('center::messages.objects_destroy')</button>
		{{ Form::close() }}
	@else
		<p>@lang('center::messages.objects_dependencies', ['dependencies', $dependencies])</p>
	@endif

@endsection