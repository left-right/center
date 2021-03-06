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
					<h1 class="modal-title">@lang('center::site.welcome')</h1>
				</div>
				<div class="modal-body">
					@include('center::notifications')
					<div class="form-group">
						<label class="col-md-3 control-label" for="email">@lang('center::site.email')</label>
						<div class="col-md-9">
							{!! Form::email('email', null, ['class'=>'form-control required email', 'autofocus']) !!}
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label" for="password">@lang('center::site.password')</label>
						<div class="col-md-9">
							{!! Form::password('password', ['class'=>'form-control required']) !!}
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<a href="{{ URL::action('\LeftRight\Center\Controllers\LoginController@getReset') }}" class="btn btn-default">{{ trans('center::site.password_reset') }}</a>
					<input type="submit" class="btn btn-primary" value="{{ trans('center::site.login') }}">
				</div>
			</div>
		</div>
	</div>
		
	{!! Form::close() !!}
@endsection