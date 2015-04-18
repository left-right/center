<div class="form-group field-{{ $field->type }} @if ($errors->has($field->name)) has-error @endif">
	<label class="control-label col-sm-2">{{ $field->title }}</label>
	<div class="col-sm-10">
		@if (isset($row->{$field->name}->id))
		<div class="image" data-table-name="{{ $table->name }}" data-field-name="{{ $field->name }}" data-action="{{ action('\LeftRight\Center\Controllers\FileController@image') }}" style="width:{{ $field->screen_width }}px; height:{{ $field->screen_height }}px; background-image: url({{ $row->{$field->name}->url }});">
			<div class="dimensions">{{ $field->width }} &times; {{ $field->height }}</div>
		</div>
		{!! Form::hidden($field->name, $row->{$field->name}->id) !!}
		@else
		<div class="image new" data-table-name="{{ $table->name }}" data-field-name="{{ $field->name }}" data-action="{{ action('\LeftRight\Center\Controllers\FileController@image') }}" style="width:{{ $field->screen_width }}px; height:{{ $field->screen_height }}px;">
			<div class="dimensions">{{ $field->width or '&infin;' }} &times; {{ $field->height or '&infin;' }}</div>
		</div>
		{!! Form::hidden($field->name, null) !!}
		@endif
	</div>
</div>