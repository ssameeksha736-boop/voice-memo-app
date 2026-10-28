<?php

require_once __DIR__ . '/../models/User.php';

class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect(Auth::dashboardRoute(Auth::role()));
        }
        $errors = [];
        $old = [];
        require __DIR__ . '/../views/auth/login.php';
    }

    public function login(): void
    {
        Csrf::requireValid();

        // str_field() guards against an array-typed field (e.g. email[]=x) --
        // a raw trim($_POST['email'] ?? '') would throw an uncaught TypeError
        // here, before Csrf::requireValid()'s protections are even relevant,
        // since this is the one endpoint a fully unauthenticated client can post to.
        $email = trim(str_field($_POST, 'email'));
        $password = str_field($_POST, 'password');

        $v = new Validator();
        $v->required($_POST, 'email', 'Email')->email($_POST, 'email', 'Email')
          ->required($_POST, 'password', 'Password');

        if ($v->fails()) {
            $errors = $v->errors();
            $old = $_POST;
            require __DIR__ . '/../views/auth/login.php';
            return;
        }

        if (!Auth::attempt($email, $password)) {
            $errors = ['login' => 'Invalid email or password, or the account is inactive.'];
            $old = $_POST;
            require __DIR__ . '/../views/auth/login.php';
            return;
        }

        redirect(Auth::dashboardRoute(Auth::role()));
    }

    public function logout(): void
    {
        Csrf::requireValid();
        Auth::logout();
        redirect('login');
    }
}
