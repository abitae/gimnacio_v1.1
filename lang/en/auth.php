<?php

return [

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    'inactive_user' => 'Your account is inactive.',
    'sucursal_not_assigned' => 'The selected branch is not assigned to your user.',

    'login' => [
        'title' => 'Log in to your account',
        'description' => 'Enter your email and password below to log in',
        'email_label' => 'Email address',
        'password_label' => 'Password',
        'placeholder_email' => 'email@example.com',
        'forgot_password' => 'Forgot your password?',
        'remember_me' => 'Remember me',
        'submit' => 'Log in',
        'no_account' => 'Don\'t have an account?',
        'sign_up' => 'Sign up',
    ],

    'register' => [
        'title' => 'Create an account',
        'description' => 'Enter your details below to create your account',
        'name' => 'Name',
        'full_name_placeholder' => 'Full name',
        'email_label' => 'Email address',
        'password_label' => 'Password',
        'confirm_password_label' => 'Confirm password',
        'submit' => 'Create account',
        'have_account' => 'Already have an account?',
        'log_in' => 'Log in',
    ],

    'forgot_password' => [
        'title' => 'Forgot password',
        'description' => 'Enter your email to receive a password reset link',
        'email_label' => 'Email address',
        'submit' => 'Email password reset link',
        'or_return' => 'Or, return to',
        'log_in' => 'log in',
    ],

    'reset_password' => [
        'title' => 'Reset password',
        'description' => 'Please enter your new password below',
        'email_label' => 'Email',
        'password_label' => 'Password',
        'confirm_password_label' => 'Confirm password',
        'submit' => 'Reset password',
    ],

    'confirm_password' => [
        'title' => 'Confirm password',
        'description' => 'This is a secure area of the application. Please confirm your password before continuing.',
        'password_label' => 'Password',
        'submit' => 'Confirm',
    ],

    'verify_email' => [
        'intro' => 'Please verify your email address by clicking on the link we just emailed to you.',
        'sent' => 'A new verification link has been sent to the email address you provided during registration.',
        'resend' => 'Resend verification email',
        'log_out' => 'Log out',
    ],

    'two_factor' => [
        'auth_code_title' => 'Authentication Code',
        'auth_code_description' => 'Enter the authentication code provided by your authenticator application.',
        'recovery_title' => 'Recovery Code',
        'recovery_description' => 'Please confirm access to your account by entering one of your emergency recovery codes.',
        'otp_label' => 'OTP Code',
        'continue' => 'Continue',
        'or_you_can' => 'or you can',
        'login_with_recovery' => 'login using a recovery code',
        'login_with_auth_code' => 'login using an authentication code',
    ],

];
