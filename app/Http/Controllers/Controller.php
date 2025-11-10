<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Campaign API",
    description: "API documentation for Campaign management system"
)]
#[OA\Server(
    url: "http://localhost:8080/api",
    description: "Local development server"
)]
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
