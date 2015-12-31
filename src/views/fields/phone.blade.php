<div class="form-group field-{{ $field->type }} @if ($errors->has($field->name)) has-error @endif">
	<label class="control-label col-sm-2" for="{{ $field->name }}">{{ $field->title }}</label>
	<div class="col-sm-10">
		<div class="input-group">
			@if (!empty($row->{$field->name}))
				<a class="input-group-addon" href="tel:{{ $row->{$field->name} }}">{!! config('center.icons.phone') !!}</a>
			@else
				<span class="input-group-addon">{!! config('center.icons.phone') !!}</span>
			@endif
			{!! Form::text($field->name, @$row->{$field->name}, ['id'=>$field->name, 'class'=>'form-control ' . $field->type . ($field->required ? ' required' : '')]) !!}
		</div>
	</div>
</div>