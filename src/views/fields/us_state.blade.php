<div class="form-group field-{{ $field->type }} @if ($errors->has($field->name)) has-error @endif">
	<label class="control-label col-sm-2" for="{{ $field->name }}">{{ $field->title }}</label>
	<div class="col-sm-10">
		{!! Form::select($field->name, $field->options, @$row->{$field->name}, ['id'=>$field->name, 'class'=>'form-control ' . $field->type . ($field->required ? ' required' : ''), 'data-source'=>(empty($field->source) ? null : $field->source)]) !!}
	</div>
</div>