@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center bravo-login-form-page bravo-login-page">
        <div class="col-md-5">
            <div class="bravo-form-login-wrap">
                @include('Layout::admin.message')
                <div class="error message-error"></div>
                <h4 class="form-title">{{ __('Login') }}</h4>
                @include('Layout::auth.login-form',['captcha_action'=>'login_normal'])
            </div>
        </div>
    </div>
</div>
@endsection
