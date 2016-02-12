<?php

require_once 'Setup.php';
require_once 'Router.php';
require_once 'HttpStatus.php';

// IDEA: move anonymous functions to classes or namespaces, allowing to write:
// Router::get('/user', User::getMany);


Router::get({{ resource.name }}, function ($req, $res) {
    ${{ resource.name }} = new Resource({{ phpArray(resource) }}); // FIXME: better to have the configuration in one single file, and access the resource-specific data from PHP
    ${{ resource.name }}->getMany($req, $res);
    $res->send();
});


Router::post({{ resource.name }}, function ($req, $res) {
    ${{ resource.name }} = new Resource({{ phpArray(resource) }});
    ${{ resource.name }}->create($req, $res);
    $res->send();
});