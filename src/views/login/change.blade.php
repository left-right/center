@extends('center::login.template')

@section('title')
	@lang('center::users.password_reset')
@endsection

@section('main')
	{!! Form::open(['action'=>'\LeftRight\Center\Controllers\LoginController@postChange', 'class'=>'form-horizontal']) !!}
	{!! Form::hidden('token', $token) !!}
	{!! Form::hidden('email', $email) !!}
		
	<div class="modal show">
		<div class="modal-dialog">
		    <div class="modal-content">
				<div class="modal-header">
					<h3 class="modal-title">@lang('center::users.password_change')</h3>
				</div>
				<div class="modal-body">
					<div class="form-group">
						<label class="col-md-3 control-label" for="email">@lang('center::users.password')</label>
				    	<div class="col-md-9">
				    		<input type="password" name="password" class="form-control required" autofocus>
				    	</div>
					</div>
			    </div>
			    <div class="modal-footer">
			    	<input type="submit" class="btn btn-primary" value="@lang('center::users.password_change')">
			    </div>
			</div>
		</div>
	</div>
		
	{!! Form::close() !!}
@endsection