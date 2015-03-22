@extends('center::template')

@section('title')
	@lang('center::objects.edit', ['title'=>$object->title])
@endsection

@section('main')

	{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
		URL::action('\LeftRight\Center\Controllers\ObjectController@index')=>trans('center::objects.plural'),
		URL::action('\LeftRight\Center\Controllers\InstanceController@index', $object->name)=>$object->title,
		trans('center::objects.edit'),
		]) !!}

	{!! Form::open(['class'=>'form-horizontal', 'url'=>URL::action('\LeftRight\Center\Controllers\ObjectController@update', $object->name), 'method'=>'put']) !!}
	
	<div class="form-group">
		{!! Form::label('title', trans('center::objects.title'), ['class'=>'control-label col-sm-2']) !!}
	    <div class="col-sm-10">
			{!! Form::text('title', $object->title, ['class'=>'required form-control', 'autofocus'=>'autofocus']) !!}
	    </div>
	</div>
	
	<div class="form-group">
		{!! Form::label('list_grouping', trans('center::objects.list_grouping'), ['class'=>'control-label col-sm-2']) !!}
	    <div class="col-sm-10">
			{!! Form::text('list_grouping', $object->list_grouping, ['class'=>'form-control', 'data-provide'=>'typeahead', 'data-source'=>$list_groupings]) !!}
	    </div>
	</div>
		
	<div class="form-group">
		{!! Form::label('name', trans('center::objects.name'), ['class'=>'control-label col-sm-2']) !!}
	    <div class="col-sm-10">
			{!! Form::text('name', $object->name, ['class'=>'required form-control']) !!}
	    </div>
	</div>
		
	<div class="form-group">
		{!! Form::label('model', trans('center::objects.model'), ['class'=>'control-label col-sm-2']) !!}
	    <div class="col-sm-10">
			{!! Form::text('model', $object->model, ['class'=>'required form-control']) !!}
	    </div>
	</div>
		
	<div class="form-group">
		{!! Form::label('url', trans('center::objects.url'), ['class'=>'control-label col-sm-2']) !!}
	    <div class="col-sm-10">
			{!! Form::text('url', $object->url, ['class'=>'form-control']) !!}
	    </div>
	</div>
			
	<div class="form-group">
		{!! Form::label('order_by', trans('center::objects.order_by'), ['class'=>'control-label col-sm-2']) !!}
		<div class="col-sm-10">
			{!! Form::select('order_by', $order_by, $object->order_by, ['class'=>'form-control']) !!}
		</div>
	</div>
	
	<div class="form-group">
		{!! Form::label('direction', trans('center::objects.direction'), ['class'=>'control-label col-sm-2']) !!}
		<div class="col-sm-10">
			{!! Form::select('direction', $direction, $object->direction, ['class'=>'form-control']) !!}
		</div>
	</div>
		
	<!-- (not implemented yet)
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-10">
			<div class="checkbox">
				<label>
					{{ Form::checkbox('singleton', 'on', $object->singleton) }}
					@lang('center::objects.singleton')
				</label>
			</div>
		</div>
	</div>
	-->

	@if (count($group_by_field))
	<div class="form-group">
		{!! Form::label('group_by_field', trans('center::objects.group_by'), ['class'=>'control-label col-sm-2']) !!}
		<div class="col-sm-10">
			{!! Form::select('group_by_field', $group_by_field, $object->group_by_field, ['class'=>'form-control']) !!}
		</div>
	</div>
	@endif

	@if (count($related_objects))
	<div class="form-group">
		{!! Form::label('group_by_field', trans('center::objects.related'), ['class'=>'control-label col-sm-2']) !!}
		<div class="col-sm-10">
			@foreach ($related_objects as $related_object_id=>$related_object_title)
			<label class="checkbox-inline">
				{!! Form::checkbox('related_objects[]', $related_object_id, in_array($related_object_id, $links)) !!} {{ $related_object_title }}
			</label>
			@endforeach
		</div>
	</div>
	@endif
	
	<div class="form-group">
		{!! Form::label('permissions', trans('center::objects.permissions'), ['class'=>'control-label col-sm-2']) !!}
		<div class="col-sm-10">
			<div class="checkbox">
				<label>
					{!! Form::checkbox('can_see', 'on', $object->can_see) !!}
					@lang('center::objects.can_see')
				</label>
			</div>
			<div class="checkbox">
				<label>
					{!! Form::checkbox('can_create', 'on', $object->can_create) !!}
					@lang('center::objects.can_create')
				</label>
			</div>
			<div class="checkbox">
				<label>
					{!! Form::checkbox('can_edit', 'on', $object->can_edit) !!}
					@lang('center::objects.can_edit')
				</label>
			</div>
		</div>
	</div>

	<div class="form-group">
		{!! Form::label('list_help', trans('center::objects.list_help'), ['class'=>'control-label col-sm-2']) !!}
		<div class="col-sm-10">
			{!! Form::textarea('list_help', $object->list_help, ['class'=>'form-control']) !!}
		</div>
	</div>

	<div class="form-group">
		{!! Form::label('form_help', trans('center::objects.form_help'), ['class'=>'control-label col-sm-2']) !!}
		<div class="col-sm-10">
			{!! Form::textarea('form_help', $object->form_help, ['class'=>'form-control']) !!}
		</div>
	</div>

	<div class="form-group">
	    <div class="col-sm-10 col-sm-offset-2">
			{!! Form::submit(trans('center::site.save'), ['class'=>'btn btn-primary']) !!}
			{!! HTML::link(URL::action('\LeftRight\Center\Controllers\InstanceController@index', $object->name), trans('center::site.cancel'), ['class'=>'btn btn-default']) !!}
	    </div>
	</div>

	{!! Form::close() !!}
	
@endsection

@section('side')
	
	<p>@lang('center::objects.edit_help', ['title'=>$object->title])</p>

	@if (!$dependencies)
		{!! Form::open(['method'=>'delete', 'action'=>['\LeftRight\Center\Controllers\ObjectController@destroy', $object->name]]) !!}
		<button type="submit" class="btn btn-default btn-xs">@lang('center::objects.destroy')</button>
		{!! Form::close() !!}
	@else
		<p>@lang('center::objects.dependencies', ['dependencies', $dependencies])</p>
	@endif

@endsection