<div class="form-group field-{{ $field->type }} @if ($errors->has($field->name)) has-error @endif">
	<label class="control-label col-sm-2">{{ $field->title }}</label>
	<div class="col-sm-10">
		{!! Form::email($field->name, @$row->{$field->name}, ['class'=>'form-control ' . $field->type . ($field->required ? ' required' : '')]) !!}
	</div>
</div>