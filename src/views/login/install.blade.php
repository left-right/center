@extends('center::login.template')

@section('title')
	@lang('center::site.welcome')
@endsection

@section('main')
	{{ Form::open(['action'=>'LoginController@postIndex', 'class'=>'form-horizontal']) }}

	<div class="modal show">
		<div class="modal-dialog">
		    <div class="modal-content">
				<div class="modal-header">
					<h3 class="modal-title">@lang('center::site.welcome')</h3>
				</div>
				<div class="modal-body">
					<div class="form-group">
						<label class="col-md-3 control-label" for="name">@lang('center::users.plural_name')</label>
				    	<div class="col-md-9">
				    		{{ Form::text('name', null, ['class'=>'form-control required', 'autofocus']) }}
				    	</div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label" for="email">@lang('center::users.plural_email')</label>
				    	<div class="col-md-9">
				    		{{ Form::text('email', null, ['class'=>'form-control required email']) }}
				    	</div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label" for="password">@lang('center::users.plural_password')</label>
				    	<div class="col-md-9">
				    		{{ Form::password('password', ['class'=>'form-control required']) }}
				    	</div>
					</div>
			    </div>
			    <div class="modal-footer">
				    {{ Form::submit(trans('center::site.login'), ['class'=>'btn btn-primary']) }}
			    </div>
			</div>
		</div>
	</div>

	{{ Form::close() }}
@endsection