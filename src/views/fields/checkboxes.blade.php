<div class="form-group field-{{ $field->type }}">
	<label class="control-label col-sm-2">{{ $field->title }}</label>
	<div class="col-sm-10">
		@foreach ($field->options as $option_id => $option_value)
		<label class="checkbox-inline">
			{!! Form::checkbox($field->name . '[]', $option_id, @in_array($option_id, $row->{$field->name})) !!}
			{{ $option_value }}
		</label>
		@endforeach
	</div>
</div>