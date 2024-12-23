<?php

use CodeIgniter\Router\RouteCollection;

// Middleware Login
$this->auth = ['filter' => 'auth'];
$this->noauth = ['filter' => 'noauth'];

/**
 * @var RouteCollection $routes
 */
$routes->add('/', 'User::viewLogin', $this->auth);


// Login
$routes->group('login', function ($routes) {
    $routes->add('', 'User::viewLogin', $this->auth);
    $routes->add('auth', 'User::loginAuth', $this->auth);
});
// Routes Master User
$routes->group('user', function ($routes) {
    $routes->add('', 'User::index', $this->noauth);
    $routes->add('table', 'User::datatable', $this->noauth);
    $routes->add('add', 'User::addData', $this->noauth);
    $routes->add('form', 'User::forms', $this->noauth);
    $routes->add('form/(:any)', 'User::forms/$1', $this->noauth);
    $routes->add('update', 'User::updateData', $this->noauth);
    $routes->add('delete', 'User::deleteData', $this->noauth);
});

//document ROUTES
$routes->group('document', function ($routes) {
    $routes->add('', 'document::index', $this->noauth);
    $routes->add('table', 'document::datatable', $this->noauth);
    $routes->add('add', 'document::addData', $this->noauth);
    $routes->add('form', 'document::forms', $this->noauth);
    $routes->add('form/(:any)', 'document::forms/$1', $this->noauth);
    $routes->add('update', 'document::updateData', $this->noauth);
    $routes->add('delete', 'document::deleteData', $this->noauth);
});


// -------------------------------------------------------->
// Log Out
$routes->add('logout', 'User::logOut');
