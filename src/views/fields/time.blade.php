<div class="form-group field-{{ $field->type }} @if ($errors->has($field->name)) has-error @endif">
	<label class="control-label col-sm-2">{{ $field->title }}</label>
	<div class="col-sm-10">
		<div class="input-group time" data-date-format="hh:mm A">
			<span class="input-group-addon"><span class="glyphicon glyphicon-time"></span></span>
			{!! Form::text($field->name, @$row->{$field->name}, ['class'=>'form-control ' . $field->type . ($field->required ? ' required' : '')]) !!}
		</div>
	</div>
</div>