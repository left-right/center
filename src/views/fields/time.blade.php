<div class="form-group field-{{ $field->type }} @if ($errors->has($field->name)) has-error @endif">
	<label class="control-label col-sm-2" for="{{ $field->name }}">{{ $field->title }}</label>
	<div class="col-sm-10">
		<div class="input-group time" data-date-format="hh:mm A">
			<span class="input-group-addon">{!! config('center.icons.time') !!}</span>
			<input type="text" class="form-control time @if ($field->required) required @endif" @if (isset($row->{$field->name})) value="{{ date('m/d/Y', strtotime($row->{$field->name})) }}" @endif name="{{ $field->name }}" id="{{ $field->name }}">
		</div>
	</div>
</div>