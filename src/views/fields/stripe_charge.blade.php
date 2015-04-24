@if (isset($row->{$field->name}))

<div class="form-group field-{{ $field->type }} @if ($errors->has($field->name)) has-error @endif">
	<label class="control-label col-sm-2">{{ $field->title }}</label>
	<div class="col-sm-10">
		<p class="form-control-static">
			<a href="https://dashboard.stripe.com/payments/{{ $row->{$field->name} }}" target="_blank">
				<i class="glyphicon glyphicon-new-window"></i>
				https://dashboard.stripe.com/payments/{{ $row->{$field->name} }}
			</a>
		</p>
		{!! Form::hidden($field->name, $row->{$field->name}) !!}
	</div>
</div>

@endif