@extends('center::login.template')

@section('title')
	@lang('center::site.welcome')
@endsection

@section('main')
	{!! Form::open(['action'=>'\LeftRight\Center\Controllers\LoginController@postIndex', 'class'=>'form-horizontal']) !!}
		
	<div class="modal show">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h2 class="modal-title">{{ trans('center::site.welcome') }}</h2>
				</div>
				<div class="modal-body">
					@include('center::login.notifications')
					<div class="form-group">
						<label class="col-md-3 control-label" for="email">{{ trans('center::users.email') }}</label>
						<div class="col-md-9">
							<input type="text" name="email" class="form-control required email" autofocus="autofocus">
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label" for="password">{{ trans('center::users.password') }}</label>
						<div class="col-md-9">
							<input type="password" name="password" class="form-control required">
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<a href="{{ URL::action('\LeftRight\Center\Controllers\LoginController@getReset') }}" class="btn btn-default">{{ trans('center::users.password_reset') }}</a>
					<input type="submit" class="btn btn-primary" value="{{ trans('center::site.login') }}">
				</div>
			</div>
		</div>
	</div>
		
	{!! Form::close() !!}
@endsection