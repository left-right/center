<div class="form-group field-{{ $field->type }} @if ($errors->has($field->name)) has-error @endif">
	<label class="control-label col-sm-2" for="{{ $field->name }}">{{ $field->title }}</label>
	<div class="col-sm-10">
		<input type="integer" class="form-control integer @if ($field->required) required @endif" value="{{ @$row->{$field->name} }}" name="{{ $field->name }}" id="{{ $field->name }}">
	</div>
</div>