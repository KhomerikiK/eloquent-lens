<?php

namespace EloquentLens\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use EloquentLens\Services\ModelParser;

class DashboardController extends Controller
{
    public function __construct(
        protected ModelParser $parser
    ) {}

    public function index()
    {
        return view('eloquent-lens::dashboard', [
            'apiUrl' => route('eloquent-lens.api.models'),
        ]);
    }

    public function models()
    {
        return response()->json($this->parser->parse());
    }
}
