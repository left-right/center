@extends('center::login.template')

@section('title')
	@lang('center::site.password_reset')
@endsection

@section('main')
	{!! Form::open(['action'=>'\LeftRight\Center\Controllers\LoginController@postReset', 'class'=>'form-horizontal']) !!}
		
	<div class="modal show">
		<div class="modal-dialog">
		    <div class="modal-content">
				<div class="modal-header">
					<h1 class="modal-title">@lang('center::site.password_reset')</h1>
				</div>
				<div class="modal-body">
					@include('center::notifications')
					<div class="form-group">
						<label class="col-md-3 control-label" for="email">@lang('center::site.email')</label>
				    	<div class="col-md-9">
				    		<input type="text" name="email" class="form-control required email" autofocus>
				    	</div>
					</div>
			    </div>
			    <div class="modal-footer">
			    	<a href="{{ URL::action('\LeftRight\Center\Controllers\TableController@index') }}" class="btn btn-default">@lang('center::site.cancel')</a>
			    	<input type="submit" class="btn btn-primary" value="@lang('center::site.password_reset')">
			    </div>
			</div>
		</div>
	</div>
		
	{!! Form::close() !!}
@endsection