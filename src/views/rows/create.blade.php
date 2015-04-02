@extends('center::template')

@section('title')
	@lang('center::rows.create')
@endsection

@section('main')

	{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
		URL::action('\LeftRight\Center\Controllers\TableController@index')=>trans('center::site.home'),
		URL::action('\LeftRight\Center\Controllers\RowController@index', $table->name)=>$table->title,
		trans('center::rows.create'),
		]) !!}

	@include('center::notifications')

	{!! Form::open(['class'=>'form-horizontal', 'url'=>URL::action('\LeftRight\Center\Controllers\RowController@store', [$table->name, $linked_id])]) !!}
	
	{!! Form::hidden('return_to', $return_to) !!}

	@foreach ($table->fields as $field)
		@if ($linked_id && $field->id == $table->group_by_field)
			{!! Form::hidden($field->name, $linked_id) !!}
		@elseif (!$field->hidden)
			<div class="form-group field-{{ $field->type }} @if ($errors->has($field->name)) has-error @endif">
				<label class="control-label col-sm-2">{{ $field->title }}</label>
				<div class="col-sm-10">
					@if ($field->type == 'checkbox')
						{!! Form::checkbox($field->name) !!}
					@elseif ($field->type == 'checkboxes')
						@foreach ($field->options as $option_id=>$option_value)
							<label class="checkbox-inline">
								{!! Form::checkbox($field->name . '[]', $option_id) !!}
								{{ $option_value }}
							</label>
						@endforeach
					@elseif ($field->type == 'color')
						{!! Form::text($field->name, $field->required ? '#ffffff' : null, ['class'=>'form-control ' . $field->type . ' {hash:true,caps:false}' . (!$field->required ?: ' required')]) !!}
					@elseif ($field->type == 'date')
						<div class="input-group date" data-date-format="MM/DD/YYYY">
							<span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
							@if ($field->required)
							<input type="text" class="form-control required" value="{{ date('m/d/Y') }}" name="{{ $field->name }}">
						   	@else
							<input type="text" class="form-control" name="{{ $field->name }}">
						   	@endif
						</div>
					@elseif ($field->type == 'datetime')
						<div class="input-group datetime" data-date-format="MM/DD/YYYY hh:mm A">
							<span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
							@if ($field->required)
							<input type="text" class="form-control required" value="{{ date('m/d/Y h:i A') }}" name="{{ $field->name }}">
						   	@else
							<input type="text" class="form-control" name="{{ $field->name }}">
						   	@endif
						</div>
					@elseif ($field->type == 'email')
						{!! Form::email($field->name, Request::get($field->name), ['class'=>'form-control ' . $field->type . (!$field->required ?: ' required')]) !!}
					@elseif ($field->type == 'html')
						{!! Form::textarea($field->name, Request::get($field->name), ['class'=>'form-control html' . (!$field->required ?: ' required')]) !!}
					@elseif ($field->type == 'image')
						{!! Form::hidden($field->name, Request::get($field->name)) !!}
						<div class="image new" data-field-id="{{ $field->id }}" data-action="{{ action('\LeftRight\Center\Controllers\FileController@image') }}" style="width:{{ $field->screen_width }}px; height:{{ $field->screen_height }}px; line-height:{{ $field->screen_height }}px;">
							<span>{{ $field->width or '&infin;' }} &times; {{ $field->height or '&infin;' }}</span>
						</div>
					@elseif ($field->type == 'images')
						{!! Form::hidden($field->name, null) !!}
						<div class="image new" data-field-id="{{ $field->id }}" data-action="{{ action('\LeftRight\Center\Controllers\FileController@image') }}" style="width:{{ $field->screen_width }}px; height:{{ $field->screen_height }}px; line-height:{{ $field->screen_height }}px;">
							<span>{{ $field->width or '&infin;' }} &times; {{ $field->height or '&infin;' }}</span>
						</div>
					@elseif ($field->type == 'integer')
						{!! Form::integer($field->name, null, ['class'=>'form-control ' . $field->type . ($field->required ? ' required' : '')]) !!}
					@elseif ($field->type == 'money')
						{!! Form::decimal($field->name, null, ['class'=>'form-control ' . $field->type . ($field->required ? ' required' : '')]) !!}
					@elseif ($field->type == 'select')
						{!! Form::select($field->name, $field->options, null, ['class'=>'form-control ' . $field->type . ($field->required ? ' required' : '')]) !!}
					@elseif ($field->type == 'slug')
						{!! Form::text($field->name, null, ['class'=>'form-control ' . $field->type . ($field->required ? ' required' : '')]) !!}
					@elseif ($field->type == 'string')
						{!! Form::text($field->name, null, ['class'=>'form-control ' . $field->type . ($field->required ? ' required' : '')]) !!}
					@elseif ($field->type == 'text')
						{!! Form::textarea($field->name, null, ['class'=>'form-control ' . $field->type . ($field->required ? ' required' : '')]) !!}
					@elseif ($field->type == 'time')
						<div class="input-group time" data-date-format="hh:mm A">
							<span class="input-group-addon"><span class="glyphicon glyphicon-time"></span></span>
							{!! Form::text($field->name, null, ['class'=>'form-control ' . $field->type . ($field->required ? ' required' : '')]) !!}
						</div>
					@elseif ($field->type == 'url')
						{!! Form::url($field->name, null, ['class'=>'form-control ' . $field->type . ($field->required ? ' required' : '')]) !!}
					@elseif ($field->type == 'us_state')
						{!! Form::select($field->name, $field->options, null, ['class'=>'form-control ' . $field->type . ($field->required ? ' required' : '')]) !!}
					@elseif ($field->type == 'user')
						{!! Form::select($field->name, $field->options, null, ['class'=>'form-control ' . $field->type . ($field->required ? ' required' : '')]) !!}
					@elseif ($field->type == 'zip')
						{!! Form::text($field->name, null, ['class'=>'form-control ' . $field->type . ($field->required ? ' required' : ''), 'maxlength'=>5]) !!}
					@endif
				</div>
			</div>
		@endif
	@endforeach
	
	<div class="form-group">
		<div class="col-sm-10 col-sm-offset-2">
			{!! Form::submit(trans('center::site.save'), ['class'=>'btn btn-primary']) !!}
			{!! HTML::link($return_to, trans('center::site.cancel'), ['class'=>'btn btn-default']) !!}
		</div>
	</div>

	{!! Form::close() !!}

@endsection

@section('side')
	@if (Lang::has('center::' . $table->name . '.help.create'))
		<p>{{ nl2br(trans('center::' . $table->name . '.help.create')) }}</p>
	@endif
@endsection